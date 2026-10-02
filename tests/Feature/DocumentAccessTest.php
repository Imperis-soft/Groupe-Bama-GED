<?php

namespace Tests\Feature;

use App\Models\ApprovalStep;
use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentAccessTest extends TestCase
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

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['organization_id' => $this->org->id]);
        $user->roles()->attach(Role::where('name', $role)->first());
        return $user;
    }

    private function documentFor(User $creator): Document
    {
        $ref = 'TST-' . strtoupper(fake()->unique()->bothify('??####'));
        $path = \App\Models\Organization::find($creator->organization_id)->documentsDir() . "{$ref}.docx";
        Storage::disk('s3')->put($path, 'contenu');

        return Document::create([
            'organization_id' => $creator->organization_id,
            'reference'  => $ref,
            'title'      => 'Doc ' . $ref,
            'file_path'  => $path,
            'version'    => 1,
            'status'     => 'draft',
            'creator_id' => $creator->id,
        ]);
    }

    private function shareWith(Document $document, User $user, string $level): DocumentShare
    {
        return DocumentShare::create([
            'document_id'  => $document->id,
            'shared_by'    => $document->creator_id,
            'shared_with'  => $user->id,
            'access_level' => $level,
            'is_active'    => true,
        ]);
    }

    public function test_viewer_cannot_create_documents(): void
    {
        $this->actingAs($this->userWithRole('viewer'))
            ->post('/documents/create', ['title' => 'Test'])
            ->assertForbidden();
    }

    public function test_cannot_revoke_share_of_another_document(): void
    {
        $owner = $this->userWithRole('editor');
        $other = $this->userWithRole('editor');
        $doc   = $this->documentFor($owner);
        $share = $this->shareWith($doc, $this->userWithRole('viewer'), 'view');
        $otherDoc = $this->documentFor($other);

        // Mauvais document dans l'URL
        $this->actingAs($other)->delete("/documents/{$otherDoc->id}/shares/{$share->id}")->assertNotFound();
        // Bon document mais pas propriétaire
        $this->actingAs($other)->delete("/documents/{$doc->id}/shares/{$share->id}")->assertForbidden();

        $this->assertTrue($share->fresh()->is_active);
    }

    public function test_view_share_cannot_escalate_to_edit(): void
    {
        $owner  = $this->userWithRole('editor');
        $reader = $this->userWithRole('viewer');
        $doc    = $this->documentFor($owner);
        $this->shareWith($doc, $reader, 'view');

        $this->actingAs($reader)
            ->post("/documents/{$doc->id}/shares", [
                'shared_with'  => $this->userWithRole('viewer')->id,
                'access_level' => 'edit',
            ])
            ->assertSessionHasErrors('access_level');

        $this->actingAs($reader)
            ->post("/documents/{$doc->id}/shares", ['access_level' => 'view', 'generate_link' => 1])
            ->assertSessionHasErrors('generate_link');
    }

    public function test_approval_steps_must_be_decided_in_order(): void
    {
        $owner = $this->userWithRole('editor');
        $first = $this->userWithRole('viewer');
        $second = $this->userWithRole('viewer');
        $doc = $this->documentFor($owner);

        $this->actingAs($owner)
            ->post("/documents/{$doc->id}/approval/setup", ['approvers' => [$first->id, $second->id]])
            ->assertRedirect();

        $step1 = ApprovalStep::where('approver_id', $first->id)->first();
        $step2 = ApprovalStep::where('approver_id', $second->id)->first();

        // L'approbateur voit le document sans partage explicite
        $this->assertTrue($doc->fresh()->canView($second->id));

        $this->actingAs($second)->post("/documents/{$doc->id}/approval/{$step2->id}/approve");
        $this->assertSame('pending', $step2->fresh()->status);

        $this->actingAs($first)->post("/documents/{$doc->id}/approval/{$step1->id}/approve");
        $this->actingAs($second)->post("/documents/{$doc->id}/approval/{$step2->id}/approve");
        $this->assertSame('approved', $doc->fresh()->status);

        // Une étape décidée ne peut pas être revotée
        $this->actingAs($first)->post("/documents/{$doc->id}/approval/{$step1->id}/reject", ['reason' => 'non']);
        $this->assertSame('approved', $step1->fresh()->status);
    }

    public function test_only_manager_can_setup_approval(): void
    {
        $owner  = $this->userWithRole('editor');
        $editor = $this->userWithRole('editor');
        $doc    = $this->documentFor($owner);
        $this->shareWith($doc, $editor, 'edit');

        $this->actingAs($editor)
            ->post("/documents/{$doc->id}/approval/setup", ['approvers' => [$editor->id]])
            ->assertForbidden();
    }

    public function test_status_cannot_be_set_through_the_edit_form(): void
    {
        $owner = $this->userWithRole('editor');
        $doc   = $this->documentFor($owner);

        $this->actingAs($owner)->put("/documents/{$doc->id}", ['title' => 'X', 'status' => 'approved']);
        $this->actingAs($this->userWithRole('admin'))->put("/documents/{$doc->id}", ['title' => 'Y', 'status' => 'approved']);

        $this->assertSame('draft', $doc->fresh()->status);
    }

    public function test_restore_version_requires_edit_right(): void
    {
        $owner  = $this->userWithRole('editor');
        $reader = $this->userWithRole('viewer');
        $doc    = $this->documentFor($owner);
        $this->shareWith($doc, $reader, 'view');

        $this->actingAs($reader)->post("/documents/{$doc->id}/versions/1/restore")->assertForbidden();
        $this->actingAs($reader)->post("/documents/{$doc->id}/archive")->assertForbidden();
    }

    public function test_trash_restore_limited_to_owner(): void
    {
        $owner = $this->userWithRole('editor');
        $doc   = $this->documentFor($owner);
        $doc->delete();

        $this->actingAs($this->userWithRole('editor'))->post("/trash/{$doc->id}/restore")->assertForbidden();
        $this->actingAs($owner)->post("/trash/{$doc->id}/restore")->assertRedirect();
        $this->assertNull($doc->fresh()->deleted_at);
    }

    public function test_webdav_put_requires_edit_right(): void
    {
        $owner  = $this->userWithRole('editor');
        $reader = $this->userWithRole('viewer');
        $doc    = $this->documentFor($owner);
        $this->shareWith($doc, $reader, 'view');

        $auth = fn (User $u) => ['PHP_AUTH_USER' => $u->email, 'PHP_AUTH_PW' => 'password'];

        $this->call('PUT', "/webdav/{$doc->id}", [], [], [], $auth($reader), 'pirate')->assertForbidden();
        $this->call('GET', "/webdav/{$doc->id}", [], [], [], $auth($this->userWithRole('viewer')))->assertForbidden();

        $this->call('PUT', "/webdav/{$doc->id}", [], [], [], $auth($owner), 'nouveau')->assertNoContent();
        $this->assertSame(2, $doc->fresh()->version);
        // Nouvelle version rangée dans le dossier de l'entreprise
        Storage::disk('s3')->assertExists($this->org->documentsDir() . "{$doc->reference}_v2.docx");
        Storage::disk('s3')->assertExists($this->org->documentsDir() . "{$doc->reference}.docx");
    }

    public function test_password_reset_token_expires(): void
    {
        $user = User::factory()->create();
        DB::table('password_reset_tokens')->insert([
            'email'      => $user->email,
            'token'      => Hash::make('tok'),
            'created_at' => now()->subMinutes(61),
        ]);

        $this->post('/reset-password', [
            'email' => $user->email, 'token' => 'tok',
            'password' => 'nouveaupass', 'password_confirmation' => 'nouveaupass',
        ])->assertSessionHasErrors('token');

        $this->assertFalse(Hash::check('nouveaupass', $user->fresh()->password));
    }

    public function test_forgot_password_does_not_reveal_unknown_email(): void
    {
        $this->post('/forgot-password', ['email' => 'inconnu@example.com'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');
    }

    public function test_documents_past_their_due_date_are_never_deleted(): void
    {
        $doc = $this->documentFor($this->userWithRole('editor'));
        $doc->update(['expires_at' => now()->subYear()]);

        $this->artisan('documents:cleanup-expired')->assertSuccessful();
        $this->artisan('documents:purge-trash', ['--days' => 0])->assertSuccessful();

        $this->assertNotSoftDeleted($doc);
        Storage::disk('s3')->assertExists($doc->file_path);
    }
}
