<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentAuditLog;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Services\DocumentArchivalService;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Archivage, lot 1 : aucune destruction automatique, et la trace survit à la destruction.
 */
class ArchiveSafetyTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');

        $this->org = Organization::create(['name' => 'Test SA', 'reference_prefix' => 'TST']);
        Subscription::create([
            'organization_id' => $this->org->id,
            'plan_id'         => Plan::where('slug', 'entreprise')->value('id'),
            'starts_at'       => today(),
            'ends_at'         => today()->addYear(),
        ]);
        foreach (['admin', 'editor', 'viewer'] as $name) {
            Role::create(['name' => $name, 'display_name' => ucfirst($name)]);
        }
        $this->editor = User::factory()->create(['organization_id' => $this->org->id]);
        $this->editor->roles()->attach(Role::where('name', 'editor')->first());
    }

    protected function tearDown(): void
    {
        Tenant::clear();
        parent::tearDown();
    }

    private function document(array $attributes = []): Document
    {
        $ref  = 'TST-' . strtoupper(fake()->unique()->bothify('??####'));
        $path = $this->org->documentsDir() . "{$ref}.docx";
        Storage::disk('s3')->put($path, 'contenu');

        return Document::create($attributes + [
            'organization_id' => $this->org->id,
            'reference'       => $ref,
            'title'           => 'Contrat ' . $ref,
            'file_path'       => $path,
            'creator_id'      => $this->editor->id,
        ]);
    }

    public function test_no_destructive_task_is_scheduled(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('documents:notify-expiring')
            ->doesntExpectOutputToContain('documents:cleanup-expired')
            ->assertSuccessful();
    }

    public function test_purge_keeps_the_audit_trail_of_destroyed_documents(): void
    {
        $document = $this->document();
        $service  = app(DocumentArchivalService::class);
        $service->logAction($document, 'approved', 'Validé par la direction');
        $document->delete();
        DB::table('documents')->where('id', $document->id)->update(['deleted_at' => now()->subDays(40)]);

        $this->artisan('documents:purge-trash', ['--days' => 30])->assertSuccessful();

        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
        Storage::disk('s3')->assertMissing($document->file_path);

        // La trace reste : qui a validé, et la destruction elle-même
        $logs = DocumentAuditLog::withoutGlobalScopes()->where('document_reference', $document->reference)->get();
        $this->assertEqualsCanonicalizing(['approved', 'purged'], $logs->pluck('action')->all());
        $this->assertTrue($logs->every(fn ($log) => $log->document_id === null && $log->document_title === $document->title));
    }

    public function test_archived_documents_are_never_purged(): void
    {
        $document = $this->document(['status' => 'archived', 'archived_at' => now()]);
        $document->delete();
        DB::table('documents')->where('id', $document->id)->update(['deleted_at' => now()->subDays(40)]);

        $this->artisan('documents:purge-trash', ['--days' => 30])->assertSuccessful();

        $this->assertSoftDeleted('documents', ['id' => $document->id]);
        Storage::disk('s3')->assertExists($document->file_path);
    }

    public function test_audit_log_lines_cannot_be_altered(): void
    {
        $document = $this->document();
        app(DocumentArchivalService::class)->logAction($document, 'approved', 'Validé');
        $log = DocumentAuditLog::withoutGlobalScopes()->firstOrFail();

        $this->expectException(\LogicException::class);
        $log->update(['description' => 'Jamais validé']);
    }

    public function test_retention_never_becomes_a_due_date(): void
    {
        $category = Category::create(['organization_id' => $this->org->id, 'name' => 'Paie', 'slug' => 'paie', 'default_retention_years' => 10]);
        $document = $this->document(['category_id' => $category->id, 'retention_years' => 0]);

        app(DocumentArchivalService::class)->applyRetentionPolicy($document);

        $this->assertSame(10, $document->fresh()->retention_years);
        $this->assertNull($document->fresh()->expires_at);
    }

    public function test_activity_page_survives_a_destroyed_document(): void
    {
        $document = $this->document();
        Tenant::set($this->org);
        $this->actingAs($this->editor);
        app(DocumentArchivalService::class)->logAction($document, 'viewed', 'Consulté');
        $document->forceDelete();

        $this->get('/profile/activity')->assertOk()->assertSee($document->reference);
    }
}
