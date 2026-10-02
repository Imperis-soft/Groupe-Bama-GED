<?php

namespace Tests\Feature;

use App\Models\ApprovalStep;
use App\Models\ApprovalTemplate;
use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentAuditLog;
use App\Models\DocumentVersion;
use App\Models\GedNotification;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Role;
use App\Models\SignatureRequest;
use App\Models\Subscription;
use App\Models\User;
use App\Services\DocumentArchivalService;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Cycle de vie strict : rien ne contourne le circuit d'approbation et de signature, tout est tracé.
 */
class DocumentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private const SIGNATURE = 'data:image/png;base64,iVBORw0KGgo=';

    private Organization $org;
    private User $author;
    private User $approver;

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
        $this->approver = $this->userWithRole('viewer');
    }

    protected function tearDown(): void
    {
        Tenant::clear();
        parent::tearDown();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['organization_id' => $this->org->id]);
        $user->roles()->attach(Role::where('name', $role)->first());
        return $user;
    }

    private function category(array $rule = []): Category
    {
        return Category::create(['organization_id' => $this->org->id, 'name' => 'Contrats ' . fake()->unique()->word(), 'slug' => fake()->unique()->slug(2)] + $rule);
    }

    private function doc(?Category $category = null, string $status = 'draft'): Document
    {
        $ref = 'TST-' . strtoupper(fake()->unique()->bothify('??####'));
        $path = $this->org->documentsDir() . "{$ref}.docx";
        Storage::disk('s3')->put($path, "contenu {$ref}");

        $doc = Document::create([
            'organization_id' => $this->org->id,
            'reference'   => $ref,
            'title'       => 'Contrat ' . $ref,
            'file_path'   => $path,
            'version'     => 1,
            'status'      => $status,
            'checksum'    => hash('sha256', "contenu {$ref}"),
            'creator_id'  => $this->author->id,
            'category_id' => $category?->id,
        ]);
        DocumentVersion::create(['document_id' => $doc->id, 'version_number' => 1, 'file_path' => $path, 'checksum' => $doc->checksum, 'created_by' => $this->author->id]);

        return $doc;
    }

    private function launch(Document $doc, array $approvers)
    {
        return $this->actingAs($this->author)->post("/documents/{$doc->id}/approval/setup", ['approvers' => array_map(fn ($u) => $u->id, $approvers)]);
    }

    private function approveAll(Document $doc): void
    {
        foreach ($doc->approvalSteps()->where('status', 'pending')->get() as $step) {
            $this->actingAs($step->approver)->post("/documents/{$doc->id}/approval/{$step->id}/approve");
        }
    }

    private function logged(Document $doc, string $action): bool
    {
        return DocumentAuditLog::withoutGlobalScopes()->where('document_id', $doc->id)->where('action', $action)->exists();
    }

    private function notifications(User $user, string $type): int
    {
        return GedNotification::where('user_id', $user->id)->where('type', $type)->count();
    }

    // --- Création ---

    public function test_the_circuit_imposed_by_the_category_starts_on_creation(): void
    {
        $category = $this->category(['requires_approval' => true]);
        ApprovalTemplate::create(['organization_id' => $this->org->id, 'name' => 'Contrats', 'category_id' => $category->id,
            'steps' => [['type' => 'user', 'user_id' => $this->approver->id]]]);

        $this->actingAs($this->author)->postJson('/documents/create', [
            'title' => 'Contrat', 'category_id' => $category->id, 'status' => 'approved',
            'import_mode' => 1, 'import_file' => UploadedFile::fake()->createWithContent('contrat.txt', 'Contrat de prestation'),
        ])->assertCreated()->assertJsonPath('workflow', null);

        $doc = Document::firstOrFail();
        $this->assertSame('review', $doc->status);
        $this->assertSame($this->approver->id, $doc->approvalSteps()->value('approver_id'));
        $this->assertSame(1, $this->notifications($this->approver, 'approval_needed'));
        $this->assertTrue($this->logged($doc, 'approval_setup'));
    }

    public function test_the_rule_is_inherited_from_the_parent_category(): void
    {
        $parent = $this->category(['requires_approval' => true, 'requires_signature' => true]);
        $child = Category::create(['organization_id' => $this->org->id, 'name' => 'Baux', 'slug' => 'baux', 'parent_id' => $parent->id, 'requires_signature' => false]);

        $this->assertSame(['approval' => true, 'signature' => false], $child->workflowRule());
    }

    public function test_a_missing_circuit_is_reported_instead_of_ignored(): void
    {
        $category = $this->category(['requires_approval' => true]);

        $this->actingAs($this->author)->postJson('/documents/create', [
            'title' => 'Contrat', 'category_id' => $category->id,
            'import_mode' => 1, 'import_file' => UploadedFile::fake()->createWithContent('contrat.txt', 'Contrat de bail'),
        ])->assertCreated()->assertJsonPath('workflow', fn ($message) => str_contains($message, 'pas de modèle de circuit'));

        $this->assertSame('draft', Document::firstOrFail()->status);
    }

    // --- Lancement ---

    public function test_the_author_cannot_approve_their_own_document(): void
    {
        $doc = $this->doc();

        $this->launch($doc, [$this->author])->assertSessionHas('error');

        $this->assertSame(0, ApprovalStep::count());
        $this->assertSame('draft', $doc->fresh()->status);
    }

    public function test_a_running_circuit_cannot_be_silently_replaced(): void
    {
        $doc = $this->doc();
        $this->launch($doc, [$this->approver]);

        $this->launch($doc, [$this->userWithRole('viewer')])->assertSessionHas('error');

        $this->assertSame(1, ApprovalStep::count());
    }

    // --- Contenu gelé ---

    public function test_content_is_frozen_while_in_a_circuit(): void
    {
        $doc = $this->doc();
        $this->launch($doc, [$this->approver]);

        $this->actingAs($this->author)->post("/documents/{$doc->id}/upload-version", ['file' => UploadedFile::fake()->createWithContent('v2.docx', 'v2')])
            ->assertSessionHas('error');
        $this->actingAs($this->author)->post("/documents/{$doc->id}/versions/1/restore")->assertSessionHas('error');
        $this->call('PUT', "/webdav/{$doc->id}", [], [], [], ['PHP_AUTH_USER' => $this->author->email, 'PHP_AUTH_PW' => 'password'], 'v2')
            ->assertStatus(423);

        $this->actingAs($this->author);
        $this->expectException(\LogicException::class);
        app(DocumentArchivalService::class)->createVersion($doc->fresh(), $doc->file_path);
    }

    public function test_the_category_cannot_change_during_a_circuit(): void
    {
        $doc = $this->doc();
        $this->launch($doc, [$this->approver]);
        $other = $this->category();

        $this->actingAs($this->author)->put("/documents/{$doc->id}", ['title' => 'X', 'category_id' => $other->id])->assertSessionHasErrors('category_id');
        $this->actingAs($this->author)->post('/documents/bulk', ['action' => 'move_category', 'document_ids' => [$doc->id], 'category_id' => $other->id]);

        $this->assertNull($doc->fresh()->category_id);
    }

    public function test_withdrawing_requires_a_reason_and_is_traced(): void
    {
        $doc = $this->doc();
        $this->launch($doc, [$this->approver]);

        $this->actingAs($this->author)->post("/documents/{$doc->id}/workflow/withdraw", [])->assertSessionHasErrors('reason');
        $this->actingAs($this->author)->post("/documents/{$doc->id}/workflow/withdraw", ['reason' => 'Erreur dans le montant'])->assertSessionHas('success');

        $this->assertSame('draft', $doc->fresh()->status);
        $this->assertSame('skipped', ApprovalStep::firstOrFail()->status);
        $this->assertTrue($this->logged($doc, 'workflow_withdrawn'));
        $this->assertSame(1, $this->notifications($this->approver, 'workflow_withdrawn'));
    }

    // --- Décisions ---

    public function test_each_approval_records_the_exact_content_approved(): void
    {
        $doc = $this->doc();
        $this->launch($doc, [$this->approver]);
        $this->approveAll($doc);

        $step = ApprovalStep::firstOrFail();
        $this->assertSame($doc->checksum, $step->document_checksum);
        $this->assertSame(1, $step->document_version);
        $this->assertSame('approved', $doc->fresh()->status);
        $this->assertTrue($doc->fresh()->hasValidApproval());
    }

    public function test_an_admin_can_only_force_a_step_with_a_reason_and_it_is_flagged(): void
    {
        $admin = $this->userWithRole('admin');
        $doc = $this->doc();
        $this->launch($doc, [$this->approver]);
        $step = ApprovalStep::firstOrFail();

        $this->actingAs($admin)->post("/documents/{$doc->id}/approval/{$step->id}/approve")->assertSessionHas('error');
        $this->assertTrue($step->fresh()->isPending());

        $this->actingAs($admin)->post("/documents/{$doc->id}/approval/{$step->id}/approve", ['force_reason' => 'Approbateur en congé maladie']);

        $step->refresh();
        $this->assertTrue($step->isApproved());
        $this->assertSame($admin->id, $step->forced_by_id);
        $this->assertSame('Approbateur en congé maladie', $step->force_reason);
        $this->assertTrue($this->logged($doc, 'step_forced'));
        $this->assertSame(1, $this->notifications($this->approver, 'approval_forced'));
    }

    public function test_an_admin_cannot_force_a_step_on_their_own_document(): void
    {
        $admin = $this->userWithRole('admin');
        $doc = $this->doc();
        $doc->update(['creator_id' => $admin->id]);
        $this->actingAs($admin)->post("/documents/{$doc->id}/approval/setup", ['approvers' => [$this->approver->id]]);
        $step = ApprovalStep::firstOrFail();

        $this->actingAs($admin)->post("/documents/{$doc->id}/approval/{$step->id}/approve", ['force_reason' => 'Je valide moi-même'])->assertSessionHas('error');

        $this->assertTrue($step->fresh()->isPending());
    }

    public function test_bulk_approval_no_longer_exists(): void
    {
        $doc = $this->doc();

        $this->actingAs($this->userWithRole('admin'))->post('/documents/bulk', ['action' => 'approve', 'document_ids' => [$doc->id]])->assertSessionHasErrors('action');

        $this->assertSame('draft', $doc->fresh()->status);
    }

    // --- Après approbation ---

    public function test_a_new_version_of_an_approved_document_sends_it_back_to_draft(): void
    {
        $doc = $this->doc();
        $this->launch($doc, [$this->approver]);
        $this->approveAll($doc);

        $this->actingAs($this->author)->post("/documents/{$doc->id}/upload-version", ['file' => UploadedFile::fake()->createWithContent('v2.docx', 'nouveau contenu')])
            ->assertSessionHas('success');

        $doc->refresh();
        $this->assertSame('draft', $doc->status);
        $this->assertFalse($doc->hasValidApproval());
        $this->assertTrue($this->logged($doc, 'workflow_reset'));
        // L'approbation reste dans l'historique, attachée à la version 1
        $this->assertSame(1, ApprovalStep::firstOrFail()->document_version);
    }

    // --- Signatures ---

    public function test_signatures_wait_for_approval_when_the_category_requires_it(): void
    {
        $doc = $this->doc($this->category(['requires_approval' => true, 'requires_signature' => true]));
        $signer = $this->userWithRole('viewer');

        $this->actingAs($this->author)->post("/documents/{$doc->id}/signature-requests", ['signers' => [$signer->id]])->assertSessionHas('error');
        $this->assertSame(0, SignatureRequest::count());

        $this->launch($doc, [$this->approver]);
        $this->actingAs($this->author)->post("/documents/{$doc->id}/signature-requests", ['signers' => [$signer->id]])->assertSessionHas('error');

        $this->approveAll($doc);
        $this->assertSame(1, $this->notifications($this->author, 'document_approved'));
        $this->actingAs($this->author)->post("/documents/{$doc->id}/signature-requests", ['signers' => [$signer->id]])->assertSessionHas('success');
        $this->assertSame('signing', $doc->fresh()->status);
    }

    public function test_only_requested_people_can_sign_and_the_last_signature_completes_the_document(): void
    {
        $doc = $this->doc();
        [$first, $second] = [$this->userWithRole('viewer'), $this->userWithRole('viewer')];
        $this->actingAs($this->author)->post("/documents/{$doc->id}/signature-requests", ['signers' => [$first->id, $second->id]]);

        // Un lecteur non sollicité ne signe pas
        $intruder = $this->userWithRole('admin');
        $this->actingAs($intruder)->postJson("/documents/{$doc->id}/signatures", ['signature_data' => self::SIGNATURE])->assertForbidden();

        $this->actingAs($first)->postJson("/documents/{$doc->id}/signatures", ['signature_data' => self::SIGNATURE])->assertOk();
        $this->assertSame('signing', $doc->fresh()->status);

        $this->actingAs($second)->postJson("/documents/{$doc->id}/signatures", ['signature_data' => self::SIGNATURE])->assertOk();
        $this->assertSame('signed', $doc->fresh()->status);
        $this->assertTrue($this->logged($doc, 'fully_signed'));
        $this->assertSame(1, $this->notifications($this->author, 'document_signed'));

        // Une seconde signature n'est plus possible
        $this->actingAs($first)->postJson("/documents/{$doc->id}/signatures", ['signature_data' => self::SIGNATURE])->assertForbidden();
    }

    public function test_a_declined_signature_closes_the_round(): void
    {
        $doc = $this->doc();
        $this->launch($doc, [$this->approver]);
        $this->approveAll($doc);
        [$first, $second] = [$this->userWithRole('viewer'), $this->userWithRole('viewer')];
        $this->actingAs($this->author)->post("/documents/{$doc->id}/signature-requests", ['signers' => [$first->id, $second->id]]);

        $request = SignatureRequest::where('user_id', $first->id)->firstOrFail();
        $this->actingAs($first)->post("/documents/{$doc->id}/signature-requests/{$request->id}/decline", ['reason' => 'Clause 4 incorrecte']);

        $this->assertSame('approved', $doc->fresh()->status); // l'approbation porte toujours sur ce contenu
        $this->assertSame('cancelled', SignatureRequest::where('user_id', $second->id)->value('status'));
        $this->assertTrue($this->logged($doc, 'signature_declined'));
        $this->assertSame(1, $this->notifications($this->author, 'signature_declined'));
    }

    public function test_cancelling_a_request_is_traced_and_can_complete_the_round(): void
    {
        $doc = $this->doc();
        [$first, $second] = [$this->userWithRole('viewer'), $this->userWithRole('viewer')];
        $this->actingAs($this->author)->post("/documents/{$doc->id}/signature-requests", ['signers' => [$first->id, $second->id]]);
        $this->actingAs($first)->postJson("/documents/{$doc->id}/signatures", ['signature_data' => self::SIGNATURE]);

        $request = SignatureRequest::where('user_id', $second->id)->firstOrFail();
        $this->actingAs($this->author)->delete("/documents/{$doc->id}/signature-requests/{$request->id}");

        $this->assertTrue($this->logged($doc, 'signature_cancelled'));
        $this->assertSame('signed', $doc->fresh()->status);
    }

    public function test_overdue_signatures_are_reminded(): void
    {
        $doc = $this->doc();
        $signer = $this->userWithRole('viewer');
        $this->actingAs($this->author)->post("/documents/{$doc->id}/signature-requests", ['signers' => [$signer->id], 'due_days' => 1]);

        $this->travel(3)->days();
        $this->artisan('approvals:remind')->expectsOutput('0 relance(s) de validation, 1 relance(s) de signature envoyée(s).');

        $this->assertSame(2, $this->notifications($signer, 'signature_requested'));
        $this->assertSame(1, $this->notifications($this->author, 'signature_overdue'));
    }

    // --- Archivage ---

    public function test_archiving_waits_for_the_end_of_the_required_circuit(): void
    {
        $doc = $this->doc($this->category(['requires_signature' => true]));
        $signer = $this->userWithRole('viewer');

        $this->actingAs($this->author)->post("/documents/{$doc->id}/archive")->assertSessionHas('error');

        $this->actingAs($this->author)->post("/documents/{$doc->id}/signature-requests", ['signers' => [$signer->id]]);
        $this->actingAs($this->author)->post("/documents/{$doc->id}/archive")->assertSessionHas('error');

        $this->actingAs($signer)->postJson("/documents/{$doc->id}/signatures", ['signature_data' => self::SIGNATURE]);
        $this->actingAs($this->author)->post("/documents/{$doc->id}/archive")->assertSessionHas('success');
        $this->assertSame('archived', $doc->fresh()->status);
    }

    // --- Pages ---

    public function test_pages_render_at_every_stage_of_the_circuit(): void
    {
        $category = $this->category(['requires_approval' => true, 'requires_signature' => true]);
        $doc = $this->doc($category);
        $signer = $this->userWithRole('viewer');
        $pages = fn () => ["/documents/{$doc->id}", "/documents/{$doc->id}/approval", "/documents/{$doc->id}/signatures", "/documents/{$doc->id}/versions"];

        $check = function (User $user) use ($pages) {
            foreach ($pages() as $url) {
                $this->actingAs($user)->get($url)->assertOk();
            }
        };

        $check($this->author);                                   // brouillon
        $this->launch($doc, [$this->approver]);
        $check($this->author);                                   // en approbation
        $check($this->approver);
        $this->actingAs($this->author)->get("/documents/{$doc->id}")->assertSee('Retirer du circuit')->assertDontSee('Nouvelle version');
        $this->approveAll($doc);
        $this->actingAs($this->author)->post("/documents/{$doc->id}/signature-requests", ['signers' => [$signer->id]]);
        $check($signer);                                         // en signature
        $this->actingAs($signer)->get("/documents/{$doc->id}/signatures")->assertSee('Votre signature');
        $this->actingAs($this->author)->get("/documents/{$doc->id}/signatures")->assertSee('Aucune signature ne vous est demandée');

        $this->actingAs($this->userWithRole('admin'))->get("/categories/{$category->id}/edit")->assertOk()->assertSee('Circuit obligatoire');
    }

    // --- Copie officielle ---

    public function test_an_official_copy_with_certificate_is_issued_on_approval(): void
    {
        $doc = $this->doc();
        $this->launch($doc, [$this->approver]);
        $this->approveAll($doc);

        $doc->refresh();
        $this->assertNotNull($doc->official_copy_path);
        $this->assertSame(1, $doc->official_copy_version);
        $pdf = Storage::disk('s3')->get($doc->official_copy_path);
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertSame(hash('sha256', $pdf), $doc->official_copy_checksum);
        $this->assertTrue($this->logged($doc, 'official_copy_created'));

        $this->actingAs($this->approver)->get("/documents/{$doc->id}/official-copy")->assertOk()->assertDownload();
        $this->actingAs($this->userWithRole('viewer'))->get("/documents/{$doc->id}/official-copy")->assertForbidden();
    }

    public function test_when_signatures_are_required_the_official_copy_waits_for_them(): void
    {
        $doc = $this->doc($this->category(['requires_signature' => true]));
        $this->launch($doc, [$this->approver]);
        $this->approveAll($doc);
        $this->assertNull($doc->fresh()->official_copy_path);

        $signer = $this->userWithRole('viewer');
        $this->actingAs($this->author)->post("/documents/{$doc->id}/signature-requests", ['signers' => [$signer->id]]);
        $this->actingAs($signer)->postJson("/documents/{$doc->id}/signatures", ['signature_data' => self::SIGNATURE]);

        $this->assertStringEndsWith('_signed.pdf', $doc->fresh()->official_copy_path);
    }

    public function test_a_new_version_withdraws_the_official_copy(): void
    {
        $doc = $this->doc();
        $this->launch($doc, [$this->approver]);
        $this->approveAll($doc);
        $path = $doc->fresh()->official_copy_path;

        $this->actingAs($this->author)->post("/documents/{$doc->id}/upload-version", ['file' => UploadedFile::fake()->createWithContent('v2.docx', 'nouveau contenu')]);

        $this->assertNull($doc->fresh()->official_copy_path);
        $this->actingAs($this->author)->get("/documents/{$doc->id}/official-copy")->assertNotFound();
        Storage::disk('s3')->assertExists($path); // conservée dans l'historique
    }
}
