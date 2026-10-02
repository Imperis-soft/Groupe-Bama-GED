<?php

namespace Tests\Feature;

use App\Models\ApprovalStep;
use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\GedNotification;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Role;
use App\Models\SignatureRequest;
use App\Models\Subscription;
use App\Models\User;
use App\Services\InboxService;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InboxTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

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

    private function documentFor(User $creator): Document
    {
        $ref = 'TST-' . strtoupper(fake()->unique()->bothify('??####'));
        $path = $this->org->documentsDir() . "{$ref}.docx";
        Storage::disk('s3')->put($path, 'contenu');

        return Document::create([
            'organization_id' => $this->org->id,
            'reference'  => $ref,
            'title'      => 'Doc ' . $ref,
            'file_path'  => $path,
            'version'    => 1,
            'status'     => 'draft',
            'creator_id' => $creator->id,
        ]);
    }

    private function inboxCount(User $user): int
    {
        return Tenant::run($this->org, fn () => app(InboxService::class)->count($user));
    }

    public function test_approvals_appear_only_when_it_is_the_approvers_turn(): void
    {
        $owner  = $this->userWithRole('editor');
        $first  = $this->userWithRole('viewer');
        $second = $this->userWithRole('viewer');
        $doc    = $this->documentFor($owner);

        $this->actingAs($owner)->post("/documents/{$doc->id}/approval/setup", ['approvers' => [$first->id, $second->id]]);

        $this->assertSame(1, $this->inboxCount($first));
        $this->assertSame(0, $this->inboxCount($second));

        $this->actingAs($second)->get('/inbox')->assertOk()
            ->assertDontSee($doc->title)
            ->assertSee('1 approbation(s) vous seront soumises');

        $this->actingAs($first)->get('/inbox')->assertOk()->assertSee($doc->title);

        $step1 = ApprovalStep::where('approver_id', $first->id)->first();
        $this->actingAs($first)->post("/documents/{$doc->id}/approval/{$step1->id}/approve");

        $this->assertSame(0, $this->inboxCount($first));
        $this->assertSame(1, $this->inboxCount($second));
    }

    public function test_rejected_document_appears_as_to_fix_for_its_creator(): void
    {
        $owner    = $this->userWithRole('editor');
        $approver = $this->userWithRole('viewer');
        $doc      = $this->documentFor($owner);

        $this->actingAs($owner)->post("/documents/{$doc->id}/approval/setup", ['approvers' => [$approver->id]]);
        $step = ApprovalStep::first();
        $this->actingAs($approver)->post("/documents/{$doc->id}/approval/{$step->id}/reject", ['reason' => 'Montant erroné']);

        $this->assertSame(1, $this->inboxCount($owner));
        $this->actingAs($owner)->get('/inbox')->assertOk()->assertSee('Montant erroné');

        // Resoumis : il quitte la liste
        $this->actingAs($owner)->post("/documents/{$doc->id}/approval/setup", ['approvers' => [$approver->id]]);
        $this->assertSame(0, $this->inboxCount($owner));
    }

    public function test_signature_request_flow(): void
    {
        $owner  = $this->userWithRole('editor');
        $signer = $this->userWithRole('viewer');
        $doc    = $this->documentFor($owner);

        // Le signataire ne voit pas encore le document
        $this->assertFalse($doc->canView($signer->id));

        $this->actingAs($owner)
            ->post("/documents/{$doc->id}/signature-requests", ['signers' => [$signer->id], 'message' => 'Merci', 'due_days' => 3])
            ->assertSessionHas('success');

        // Demander deux fois ne crée pas de doublon
        $this->actingAs($owner)->post("/documents/{$doc->id}/signature-requests", ['signers' => [$signer->id]]);
        $this->assertSame(1, SignatureRequest::count());

        $request = SignatureRequest::first();
        $this->assertTrue($doc->fresh()->canView($signer->id));
        $this->assertSame(1, $this->inboxCount($signer));
        $this->assertTrue(GedNotification::where('user_id', $signer->id)->where('type', 'signature_requested')->exists());

        $this->actingAs($signer)->get("/documents/{$doc->id}/signatures")->assertOk()->assertSee('vous demande de signer');

        $this->actingAs($signer)
            ->postJson("/documents/{$doc->id}/signatures", ['signature_data' => 'data:image/png;base64,iVBORw0KGgo='])
            ->assertOk();

        $request->refresh();
        $this->assertSame('signed', $request->status);
        $this->assertNotNull($request->signature_id);
        $this->assertSame(0, $this->inboxCount($signer));
        $this->assertTrue(GedNotification::where('user_id', $owner->id)->where('type', 'signature_completed')->exists());
    }

    public function test_signer_can_decline_with_reason(): void
    {
        $owner  = $this->userWithRole('editor');
        $signer = $this->userWithRole('viewer');
        $doc    = $this->documentFor($owner);
        $this->actingAs($owner)->post("/documents/{$doc->id}/signature-requests", ['signers' => [$signer->id]]);
        $request = SignatureRequest::first();

        // Un tiers ne peut pas refuser à sa place
        $this->actingAs($this->userWithRole('viewer'))
            ->post("/documents/{$doc->id}/signature-requests/{$request->id}/decline", ['reason' => 'non'])
            ->assertForbidden();

        $this->actingAs($signer)
            ->post("/documents/{$doc->id}/signature-requests/{$request->id}/decline", [])
            ->assertSessionHasErrors('reason');

        $this->actingAs($signer)
            ->post("/documents/{$doc->id}/signature-requests/{$request->id}/decline", ['reason' => 'Clause 4 à revoir']);

        $this->assertSame('declined', $request->fresh()->status);
        $this->assertTrue(GedNotification::where('user_id', $owner->id)->where('type', 'signature_declined')->exists());
        $this->actingAs($owner)->get('/inbox')->assertOk()->assertSee('Clause 4 à revoir');
    }

    public function test_only_manager_can_request_or_cancel_signatures(): void
    {
        $owner  = $this->userWithRole('editor');
        $other  = $this->userWithRole('editor');
        $signer = $this->userWithRole('viewer');
        $doc    = $this->documentFor($owner);

        $this->actingAs($other)
            ->post("/documents/{$doc->id}/signature-requests", ['signers' => [$signer->id]])
            ->assertForbidden();

        $this->actingAs($owner)->post("/documents/{$doc->id}/signature-requests", ['signers' => [$signer->id]]);
        $request = SignatureRequest::first();

        $this->actingAs($other)->delete("/documents/{$doc->id}/signature-requests/{$request->id}")->assertForbidden();
        $this->actingAs($owner)->delete("/documents/{$doc->id}/signature-requests/{$request->id}")->assertRedirect();

        $this->assertSame('cancelled', $request->fresh()->status);
        // Demande annulée : plus d'accès ni d'élément à traiter
        $this->assertFalse($doc->fresh()->canView($signer->id));
        $this->assertSame(0, $this->inboxCount($signer));
    }

    public function test_new_share_counts_until_document_is_opened(): void
    {
        $owner  = $this->userWithRole('editor');
        $reader = $this->userWithRole('viewer');
        $doc    = $this->documentFor($owner);
        DocumentShare::create([
            'document_id' => $doc->id, 'shared_by' => $owner->id, 'shared_with' => $reader->id,
            'access_level' => 'view', 'is_active' => true, 'message' => 'Pour info',
        ]);

        $this->assertSame(1, $this->inboxCount($reader));
        $this->actingAs($reader)->get('/inbox')->assertOk()->assertSee('Pour info')->assertSee('Nouveau');

        $this->actingAs($reader)->get("/documents/{$doc->id}")->assertOk();
        $this->assertSame(0, $this->inboxCount($reader));
    }

    public function test_dashboard_and_document_page_point_to_pending_actions(): void
    {
        $owner    = $this->userWithRole('admin');
        $approver = $this->userWithRole('viewer');
        $doc      = $this->documentFor($owner);
        $this->actingAs($owner)->post("/documents/{$doc->id}/approval/setup", ['approvers' => [$approver->id]]);

        $this->actingAs($approver)->get('/dashboard')->assertOk()->assertSee('1 élément(s) attendent votre action');
        $this->actingAs($approver)->get("/documents/{$doc->id}")->assertOk()->assertSee('Votre approbation est attendue');
        $this->actingAs($owner)->get('/dashboard')->assertOk();
    }
}
