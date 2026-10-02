<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\GedNotification;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SystemEvent;
use App\Models\User;
use App\Services\IntegrityService;
use App\Services\SystemHealth;
use App\Support\Tenant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FailingTestJob implements ShouldQueue
{
    use Dispatchable;

    public function handle(): void
    {
        throw new \RuntimeException('Serveur de conversion indisponible');
    }
}

/**
 * Journal système : enregistrement automatique des problèmes, contrôles de santé, page super admin.
 */
class SystemEventsTest extends TestCase
{
    use RefreshDatabase;

    private User $super;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
        foreach (['admin', 'editor', 'viewer'] as $name) {
            Role::create(['name' => $name, 'display_name' => ucfirst($name)]);
        }
        $this->super = User::factory()->create(['is_super_admin' => true, 'organization_id' => null]);
    }

    protected function tearDown(): void
    {
        Tenant::clear();
        parent::tearDown();
    }

    private function organization(): Organization
    {
        $org = Organization::create(['name' => 'Test SA', 'reference_prefix' => 'TST']);
        Subscription::create(['organization_id' => $org->id, 'plan_id' => Plan::where('slug', 'entreprise')->value('id'),
            'starts_at' => today(), 'ends_at' => today()->addYear()]);
        return $org;
    }

    public function test_same_problem_is_counted_not_duplicated_and_reopens_if_it_returns(): void
    {
        SystemEvent::record('error', 'storage', 'MinIO injoignable');
        SystemEvent::record('error', 'storage', 'MinIO injoignable');
        $event = SystemEvent::firstOrFail();
        $this->assertSame(2, $event->occurrences);
        $this->assertSame(1, SystemEvent::count());

        $event->update(['resolved_at' => now()]);
        SystemEvent::record('error', 'storage', 'MinIO injoignable');
        $this->assertNull($event->fresh()->resolved_at);
        $this->assertSame(3, $event->fresh()->occurrences);
    }

    public function test_new_serious_problems_alert_super_admins_in_app(): void
    {
        SystemEvent::record('warning', 'mail', 'SMTP lent');
        $this->assertFalse(GedNotification::where('user_id', $this->super->id)->exists());

        $event = SystemEvent::record('critical', 'database', 'Base injoignable');
        $notification = GedNotification::where('user_id', $this->super->id)->where('type', 'system_event')->firstOrFail();
        $this->assertStringContainsString('Base injoignable', $notification->message);
        $this->assertStringContainsString("event={$event->id}", $notification->link);

        // Répétition : pas de nouvelle alerte
        SystemEvent::record('critical', 'database', 'Base injoignable');
        $this->assertSame(1, GedNotification::where('user_id', $this->super->id)->count());
    }

    public function test_reported_exceptions_are_recorded_with_context(): void
    {
        // La même erreur, au même endroit du code, deux fois
        foreach ([1, 2] as $attempt) {
            report(new \RuntimeException('Calcul impossible'));
        }

        $event = SystemEvent::where('category', 'application')->firstOrFail();
        $this->assertSame('error', $event->level);
        $this->assertStringContainsString('RuntimeException : Calcul impossible', $event->message);
        $this->assertSame(2, $event->occurrences);
        $this->assertStringContainsString('SystemEventsTest.php', $event->context['file']);
    }

    public function test_failed_background_tasks_are_recorded(): void
    {
        try {
            FailingTestJob::dispatch();
        } catch (\RuntimeException) {
        }

        $event = SystemEvent::where('category', 'queue')->firstOrFail();
        $this->assertStringContainsString('FailingTestJob', $event->message);
        $this->assertStringContainsString('Serveur de conversion indisponible', $event->message);
    }

    public function test_integrity_alert_is_recorded_then_resolved_when_fixed(): void
    {
        $org = $this->organization();
        $owner = User::factory()->create(['organization_id' => $org->id]);
        $path = $org->documentsDir() . 'TST-1.pdf';
        Storage::disk('s3')->put($path, 'original');
        Document::create(['organization_id' => $org->id, 'reference' => 'TST-1', 'title' => 'Doc', 'file_path' => $path,
            'checksum' => hash('sha256', 'original'), 'creator_id' => $owner->id]);

        Storage::disk('s3')->put($path, 'falsifié');
        app(IntegrityService::class)->check($org);
        $event = SystemEvent::where('category', 'integrity')->firstOrFail();
        $this->assertSame('critical', $event->level);
        $this->assertSame($org->id, $event->organization_id);
        $this->assertNull($event->resolved_at);

        Storage::disk('s3')->put($path, 'original');
        app(IntegrityService::class)->check($org);
        $this->assertNotNull($event->fresh()->resolved_at);
    }

    public function test_health_checks_detect_a_stuck_queue_and_a_stopped_scheduler(): void
    {
        config(['queue.default' => 'database']);
        DB::table('jobs')->insert(['queue' => 'default', 'payload' => '{}', 'attempts' => 0,
            'available_at' => now()->subMinutes(40)->timestamp, 'created_at' => now()->subMinutes(40)->timestamp]);
        Cache::forget(SystemHealth::HEARTBEAT_KEY);

        $this->artisan('system:health')->expectsOutputToContain('aucun processus ne les traite')->assertSuccessful();

        $queue = SystemEvent::where('category', 'queue')->firstOrFail();
        $this->assertSame('error', $queue->level);
        $this->assertTrue(SystemEvent::where('category', 'scheduler')->whereNull('resolved_at')->exists());

        // Problèmes disparus : résolus automatiquement
        DB::table('jobs')->delete();
        Cache::forever(SystemHealth::HEARTBEAT_KEY, time());
        $this->artisan('system:health')->assertSuccessful();
        $this->assertNotNull($queue->fresh()->resolved_at);
        $this->assertSame(0, SystemEvent::where('category', 'scheduler')->whereNull('resolved_at')->count());
    }

    public function test_scheduler_heartbeat_and_health_checks_are_scheduled(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('system:heartbeat')
            ->expectsOutputToContain('system:health')
            ->assertSuccessful();
    }

    public function test_super_admin_page_lists_health_and_problems(): void
    {
        $org = $this->organization();
        $event = SystemEvent::record('error', 'mail', 'Envoi d\'email impossible : connexion SMTP refusée', ['smtp' => 'mail.exemple.ml'], $org->id);
        SystemEvent::record('warning', 'storage', 'Lenteur MinIO');

        $this->actingAs($this->super)->get('/super-admin/system')->assertOk()
            ->assertSee('Base de données')
            ->assertSee('Planificateur (cron)')
            ->assertSee('connexion SMTP refusée')
            ->assertSee('Test SA')
            ->assertSee('mail.exemple.ml');

        $this->get('/super-admin/system?category=storage')->assertOk()->assertSee('Lenteur MinIO')->assertDontSee('connexion SMTP refusée');

        $this->post("/super-admin/system/{$event->id}/resolve")->assertRedirect();
        $this->assertSame($this->super->id, $event->fresh()->resolved_by);
        $this->get('/super-admin/system')->assertDontSee('connexion SMTP refusée');
        $this->get('/super-admin/system?status=resolved')->assertSee('connexion SMTP refusée');

        $this->post('/super-admin/system/resolve-all')->assertRedirect();
        $this->assertSame(0, SystemEvent::open()->count());
    }

    public function test_only_super_admin_sees_the_system_log(): void
    {
        $org = $this->organization();
        $admin = User::factory()->create(['organization_id' => $org->id]);
        $admin->roles()->attach(Role::where('name', 'admin')->first());

        $this->actingAs($admin)->get('/super-admin/system')->assertForbidden();
    }
}
