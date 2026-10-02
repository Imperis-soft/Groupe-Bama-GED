<?php

namespace Tests\Feature;

use App\Models\ApprovalStep;
use App\Models\ApprovalTemplate;
use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\GedNotification;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Services\ApprovalWorkflow;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private User $author;

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
        $this->author = $this->userWithRole('editor');
    }

    protected function tearDown(): void
    {
        Tenant::clear();
        parent::tearDown();
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['organization_id' => $this->org->id], $attributes));
        $user->roles()->attach(Role::where('name', $role)->first());
        return $user;
    }

    private function doc(?Category $category = null): Document
    {
        $ref = 'TST-' . strtoupper(fake()->unique()->bothify('??####'));
        $path = $this->org->documentsDir() . "{$ref}.pdf";
        Storage::disk('s3')->put($path, '%PDF');

        return Document::create([
            'organization_id' => $this->org->id,
            'reference'   => $ref,
            'title'       => 'Facture ' . $ref,
            'file_path'   => $path,
            'version'     => 1,
            'status'      => 'draft',
            'creator_id'  => $this->author->id,
            'category_id' => $category?->id,
        ]);
    }

    private function notifications(User $user, string $type): int
    {
        return GedNotification::where('user_id', $user->id)->where('type', $type)->count();
    }

    private function launch(Document $doc, array $approvers, array $extra = [])
    {
        return $this->actingAs($this->author)->post("/documents/{$doc->id}/approval/setup", array_merge(['approvers' => array_map(fn ($u) => $u->id, $approvers)], $extra));
    }

    public function test_approvers_are_notified_one_after_another_with_their_own_deadline(): void
    {
        $this->travelTo(now()->startOfDay()->addHours(9));
        $first = $this->userWithRole('viewer');
        $second = $this->userWithRole('viewer');
        $doc = $this->doc();

        $this->launch($doc, [$first, $second], ['step_due_days' => [2, 5]])->assertSessionHas('success');

        [$step1, $step2] = ApprovalStep::orderBy('step_order')->get()->all();
        $this->assertSame(1, $this->notifications($first, 'approval_needed'));
        $this->assertSame(0, $this->notifications($second, 'approval_needed'));
        $this->assertTrue($step1->due_at->isSameDay(now()->addDays(2)));
        $this->assertNull($step2->due_at); // le délai ne court pas encore

        $this->travel(1)->days();
        $this->actingAs($first)->post("/documents/{$doc->id}/approval/{$step1->id}/approve");

        $step2->refresh();
        $this->assertSame(1, $this->notifications($second, 'approval_needed'));
        $this->assertNotNull($step2->activated_at);
        $this->assertTrue($step2->due_at->isSameDay(now()->addDays(5)));

        $this->actingAs($second)->post("/documents/{$doc->id}/approval/{$step2->id}/approve");
        $this->assertSame('approved', $doc->fresh()->status);
        $this->assertSame(1, $this->notifications($this->author, 'document_approved'));
    }

    public function test_absent_approver_is_replaced_by_their_delegate(): void
    {
        $delegate = $this->userWithRole('viewer');
        $absent = $this->userWithRole('viewer', ['absent_from' => today()->subDay(), 'absent_until' => today()->addWeek(), 'delegate_id' => $delegate->id]);
        $doc = $this->doc();

        $this->launch($doc, [$absent]);

        $step = ApprovalStep::firstOrFail();
        $this->assertSame($delegate->id, $step->approver_id);
        $this->assertSame($absent->id, $step->delegated_from_id);
        $this->assertSame(1, $this->notifications($delegate, 'approval_needed'));
        $this->assertTrue($doc->fresh()->canView($delegate->id));

        $this->actingAs($this->userWithRole('viewer'))->post("/documents/{$doc->id}/approval/{$step->id}/approve")->assertForbidden();
        $this->actingAs($delegate)->post("/documents/{$doc->id}/approval/{$step->id}/approve");
        $this->assertSame('approved', $doc->fresh()->status);
    }

    public function test_declaring_an_absence_hands_over_active_validations(): void
    {
        $approver = $this->userWithRole('viewer');
        $delegate = $this->userWithRole('viewer');
        $doc = $this->doc();
        $this->launch($doc, [$approver]);

        $this->actingAs($approver)->put('/profile/absence', ['absent_from' => today()->format('Y-m-d'), 'absent_until' => today()->format('Y-m-d'), 'delegate_id' => $approver->id])
            ->assertSessionHasErrors('delegate_id');
        $this->actingAs($approver)->put('/profile/absence', ['absent_from' => today()->subWeek()->format('Y-m-d'), 'absent_until' => today()->subDay()->format('Y-m-d'), 'delegate_id' => $delegate->id])
            ->assertSessionHasErrors('absent_until');

        $this->actingAs($approver)->put('/profile/absence', [
            'absent_from' => today()->format('Y-m-d'), 'absent_until' => today()->addDays(3)->format('Y-m-d'), 'delegate_id' => $delegate->id,
        ])->assertSessionHas('success');

        $this->assertSame($delegate->id, ApprovalStep::firstOrFail()->approver_id);
        $this->assertSame(1, $this->notifications($delegate, 'approval_needed'));

        // Retour anticipé : l'absence est effacée
        $this->actingAs($approver)->put('/profile/absence', ['clear' => 1])->assertSessionHas('success');
        $this->assertFalse($approver->fresh()->isAbsent());
        $this->actingAs($approver)->get('/profile')->assertOk()->assertSee('Déclarer une absence');
    }

    public function test_overdue_steps_are_reminded_daily_up_to_the_limit(): void
    {
        $approver = $this->userWithRole('viewer');
        $doc = $this->doc();
        $this->launch($doc, [$approver], ['due_days' => 1]);

        // Pas encore en retard
        $this->artisan('approvals:remind')->assertSuccessful();
        $this->assertSame(0, $this->notifications($approver, 'approval_reminder'));

        $this->travel(3)->days();
        $this->artisan('approvals:remind')->expectsOutput('1 relance(s) de validation, 0 relance(s) de signature envoyée(s).');
        $this->assertSame(1, $this->notifications($approver, 'approval_reminder'));
        $this->assertSame(1, $this->notifications($this->author, 'approval_overdue'));

        // Une seule relance par jour
        $this->artisan('approvals:remind');
        $this->assertSame(1, $this->notifications($approver, 'approval_reminder'));

        foreach (range(1, 4) as $day) {
            $this->travel(1)->days();
            $this->artisan('approvals:remind');
        }
        $this->assertSame(ApprovalWorkflow::MAX_REMINDERS, $this->notifications($approver, 'approval_reminder'));
        $this->assertSame(1, $this->notifications($this->author, 'approval_overdue'));

        $this->actingAs($this->author)->get("/documents/{$doc->id}/approval")->assertOk()->assertSee('En retard')->assertSee('3 relance(s)');
    }

    public function test_manual_reminder_is_rate_limited_and_reserved_to_the_manager(): void
    {
        $first = $this->userWithRole('viewer');
        $second = $this->userWithRole('viewer');
        $doc = $this->doc();
        $this->launch($doc, [$first, $second]);
        [$step1, $step2] = ApprovalStep::orderBy('step_order')->get()->all();

        $this->actingAs($this->userWithRole('editor'))->post("/documents/{$doc->id}/approval/{$step1->id}/remind")->assertForbidden();
        $this->actingAs($this->author)->post("/documents/{$doc->id}/approval/{$step2->id}/remind")->assertSessionHas('error');

        $this->actingAs($this->author)->post("/documents/{$doc->id}/approval/{$step1->id}/remind")->assertSessionHas('success');
        $this->actingAs($this->author)->post("/documents/{$doc->id}/approval/{$step1->id}/remind")->assertSessionHas('error');
        $this->assertSame(1, $this->notifications($first, 'approval_reminder'));
    }

    public function test_templates_resolve_people_and_service_managers(): void
    {
        $admin = $this->userWithRole('admin');
        $headRh = $this->userWithRole('editor');
        $daf = $this->userWithRole('editor');
        $dg = $this->userWithRole('editor');

        $rh = Department::create(['name' => 'Ressources humaines']);
        $rh->setManager($this->org->id, $headRh->id);
        $rh->users()->attach($this->author->id);
        $compta = Department::create(['name' => 'Comptabilité']);
        $compta->setManager($this->org->id, $daf->id);
        $juridique = Department::create(['name' => 'Juridique']); // sans responsable

        $factures = Category::create(['organization_id' => $this->org->id, 'name' => 'Factures', 'slug' => 'factures']);

        // Création par l'administrateur (les champs inutiles au type sont ignorés)
        $this->actingAs($admin)->post('/approval-templates', [
            'name' => 'Factures', 'category_id' => $factures->id,
            'steps' => [
                ['type' => 'creator_manager', 'due_days' => 3, 'user_id' => $dg->id],
                ['type' => 'department_manager', 'department_id' => $compta->id, 'due_days' => 2],
                ['type' => 'user', 'user_id' => $dg->id],
            ],
        ])->assertRedirect('/approval-templates');

        $template = ApprovalTemplate::firstOrFail();
        $this->assertSame(['type' => 'creator_manager', 'due_days' => 3], $template->steps[0]);

        $doc = $this->doc($factures);
        $resolved = Tenant::run($this->org, fn () => app(ApprovalWorkflow::class)->resolveTemplate($template, $doc));
        $this->assertSame([$headRh->id, $daf->id, $dg->id], array_column($resolved['steps'], 'user_id'));
        $this->assertSame([3, 2, null], array_column($resolved['steps'], 'due_days'));
        $this->assertSame([], $resolved['errors']);

        // Service sans responsable : étape signalée
        $broken = ApprovalTemplate::create(['organization_id' => $this->org->id, 'name' => 'Juridique', 'steps' => [['type' => 'department_manager', 'department_id' => $juridique->id]]]);
        $this->assertStringContainsString('responsable non défini', Tenant::run($this->org, fn () => app(ApprovalWorkflow::class)->resolveTemplate($broken, $doc))['errors'][0]);

        // Proposé en priorité pour la catégorie du document
        $this->actingAs($this->author)->get("/documents/{$doc->id}/approval")->assertOk()->assertSee('Suggéré')->assertSee('Factures');

        // Lancement depuis le modèle : son nom figure dans le journal
        $this->launch($doc, [$headRh, $daf, $dg], ['template_id' => $template->id, 'step_due_days' => [3, 2, null]]);
        $this->assertTrue($doc->auditLogs()->where('description', 'like', '%« Factures »%')->exists());
        $this->assertSame(1, $this->notifications($headRh, 'approval_needed'));
    }

    public function test_template_management_is_validated_and_admin_only(): void
    {
        $this->actingAs($this->author)->get('/approval-templates')->assertForbidden();

        $admin = $this->userWithRole('admin');
        $this->actingAs($admin)->post('/approval-templates', ['name' => 'Vide', 'steps' => [['type' => 'user']]])
            ->assertSessionHasErrors('steps.0.user_id');
        $this->actingAs($admin)->post('/approval-templates', ['name' => 'Service', 'steps' => [['type' => 'department_manager']]])
            ->assertSessionHasErrors('steps.0.department_id');

        $this->actingAs($admin)->post('/approval-templates', ['name' => 'Direct', 'steps' => [['type' => 'user', 'user_id' => $admin->id]]]);
        $template = ApprovalTemplate::firstOrFail();
        $this->actingAs($admin)->get('/approval-templates')->assertOk()->assertSee('Direct');
        $this->actingAs($admin)->get("/approval-templates/{$template->id}/edit")->assertOk();
        $this->actingAs($admin)->delete("/approval-templates/{$template->id}")->assertRedirect();
        $this->assertSame(0, ApprovalTemplate::count());
    }
}
