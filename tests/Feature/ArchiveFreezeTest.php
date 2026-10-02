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
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Archivage, lot 2 : un document archivé est figé ; la conservation de la catégorie s'applique à l'import.
 */
class ArchiveFreezeTest extends TestCase
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

    private function user(string $role): User
    {
        $user = User::factory()->create(['organization_id' => $this->org->id]);
        $user->roles()->attach(Role::where('name', $role)->first());
        return $user;
    }

    private function document(User $creator, array $attributes = []): Document
    {
        $ref  = 'TST-' . strtoupper(fake()->unique()->bothify('??####'));
        $path = $this->org->documentsDir() . "{$ref}.docx";
        Storage::disk('s3')->put($path, 'contenu ' . $ref);

        return Document::create($attributes + [
            'organization_id' => $this->org->id,
            'reference'       => $ref,
            'title'           => 'Contrat ' . $ref,
            'file_path'       => $path,
            'status'          => 'approved',
            'creator_id'      => $creator->id,
        ]);
    }

    private function archived(User $creator): Document
    {
        $document = $this->document($creator);
        $this->actingAs($creator)->post("/documents/{$document->id}/archive", ['reason' => 'Dossier clos'])->assertRedirect();
        return $document->fresh();
    }

    public function test_archiving_records_who_why_and_freezes_the_fingerprint(): void
    {
        $editor   = $this->user('editor');
        $document = $this->archived($editor);

        $this->assertTrue($document->isArchived());
        $this->assertSame($editor->id, $document->archived_by);
        $this->assertSame('Dossier clos', $document->archive_reason);
        $this->assertSame('approved', $document->status_before_archive);
        $this->assertSame(hash('sha256', 'contenu ' . $document->reference), $document->checksum);

        $log = DocumentAuditLog::withoutGlobalScopes()->where('action', 'archived')->firstOrFail();
        $this->assertSame($document->checksum, $log->new_values['checksum']);

        // Déjà archivé : pas de second archivage
        $this->post("/documents/{$document->id}/archive")->assertSessionHas('error');
    }

    public function test_an_archived_document_cannot_be_changed_by_anyone(): void
    {
        $admin    = $this->user('admin');
        $document = $this->archived($admin);

        $this->actingAs($admin);
        $this->get("/documents/{$document->id}/edit")->assertForbidden();
        $this->put("/documents/{$document->id}", ['title' => 'Modifié'])->assertForbidden();
        $this->post("/documents/{$document->id}/upload-version", ['file' => UploadedFile::fake()->createWithContent('v2.docx', 'v2')])->assertForbidden();
        $this->post("/documents/{$document->id}/versions/1/restore")->assertForbidden();
        $this->delete("/documents/{$document->id}")->assertSessionHas('error');
        $this->post("/documents/{$document->id}/signatures", ['signature_data' => 'data:image/png;base64,AAAA'])->assertStatus(422);
        $this->post('/documents/bulk', ['action' => 'delete', 'document_ids' => [$document->id]]);
        $this->post('/documents/bulk', ['action' => 'approve', 'document_ids' => [$document->id]]);

        $fresh = $document->fresh();
        $this->assertNotNull($fresh);
        $this->assertSame('archived', $fresh->status);
        $this->assertSame($document->title, $fresh->title);
        $this->assertSame(1, (int) $fresh->version);
        $this->assertSame(0, $fresh->signatures()->count());

        // La fiche s'affiche en lecture seule
        $this->get("/documents/{$document->id}")->assertOk()->assertSee('Document archivé — lecture seule')->assertSee('Désarchiver');
    }

    public function test_only_an_admin_can_unarchive_with_a_reason(): void
    {
        $editor   = $this->user('editor');
        $document = $this->archived($editor);

        $this->actingAs($editor)->post("/documents/{$document->id}/unarchive", ['reason' => 'Je veux modifier'])->assertForbidden();

        $admin = $this->user('admin');
        $this->actingAs($admin)->post("/documents/{$document->id}/unarchive", [])->assertSessionHasErrors('reason');
        $this->assertTrue($document->fresh()->isArchived());

        $this->post("/documents/{$document->id}/unarchive", ['reason' => 'Avenant à intégrer'])->assertSessionHas('success');

        $fresh = $document->fresh();
        $this->assertSame('approved', $fresh->status);
        $this->assertNull($fresh->archived_at);
        $this->assertTrue($fresh->canEdit($admin->id));
        $this->assertStringContainsString('Avenant à intégrer',
            DocumentAuditLog::withoutGlobalScopes()->where('action', 'unarchived')->value('description'));
    }

    public function test_import_uses_the_category_retention_period(): void
    {
        $editor = $this->user('editor');
        $parent = Category::create(['organization_id' => $this->org->id, 'name' => 'Comptabilité', 'slug' => 'compta', 'default_retention_years' => 10]);
        $child  = Category::create(['organization_id' => $this->org->id, 'name' => 'Factures', 'slug' => 'factures', 'parent_id' => $parent->id]);
        $free   = Category::create(['organization_id' => $this->org->id, 'name' => 'Divers', 'slug' => 'divers']);

        $import = fn (array $extra) => $this->actingAs($editor)->postJson('/documents/create', $extra + [
            'title'       => 'Doc ' . fake()->unique()->numberBetween(1, 9999),
            'import_mode' => 1,
            'import_file' => UploadedFile::fake()->createWithContent(fake()->unique()->lexify('????') . '.txt', fake()->unique()->sentence()),
        ])->assertCreated()->json('id');

        $this->assertSame(10, Document::find($import(['category_id' => $parent->id]))->retention_years);
        // Sous-catégorie sans règle : celle du parent
        $this->assertSame(10, Document::find($import(['category_id' => $child->id]))->retention_years);
        // Valeur saisie : prioritaire
        $this->assertSame(3, Document::find($import(['category_id' => $parent->id, 'retention_years' => 3]))->retention_years);
        // Aucune règle : durée par défaut de la plateforme
        $this->assertSame(config('ged.default_retention_years'), Document::find($import(['category_id' => $free->id]))->retention_years);
        // Jamais transformée en date d'échéance
        $this->assertSame(0, Document::whereNotNull('expires_at')->count());
    }
}
