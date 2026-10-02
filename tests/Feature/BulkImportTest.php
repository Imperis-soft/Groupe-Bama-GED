<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentVersion;
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

class BulkImportTest extends TestCase
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

    private function importJson(User $user, string $name, string $content, array $extra = [])
    {
        return $this->actingAs($user)->postJson('/documents/create', array_merge([
            'title'       => pathinfo($name, PATHINFO_FILENAME),
            'import_mode' => 1,
            'import_file' => UploadedFile::fake()->createWithContent($name, $content),
        ], $extra));
    }

    public function test_json_import_returns_the_created_document(): void
    {
        $editor = $this->userWithRole('editor');

        $response = $this->importJson($editor, 'facture-001.txt', 'Facture 001 Sotelma')->assertCreated();

        $doc = Document::firstOrFail();
        $response->assertJson([
            'id'        => $doc->id,
            'reference' => $doc->reference,
            'title'     => 'facture-001',
            'url'       => route('documents.show', $doc),
        ]);
        // Empreinte calculée sur le fichier envoyé (sans relire le stockage)
        $this->assertSame(hash('sha256', 'Facture 001 Sotelma'), DocumentVersion::first()->checksum);
    }

    public function test_duplicate_file_is_blocked_and_only_an_admin_can_force_it(): void
    {
        $editor = $this->userWithRole('editor');
        $this->importJson($editor, 'contrat.txt', 'Contrat de bail')->assertCreated();
        $original = Document::firstOrFail();

        $this->importJson($editor, 'contrat-copie.txt', 'Contrat de bail')
            ->assertStatus(409)
            ->assertJsonPath('duplicate.id', $original->id)
            ->assertJsonPath('duplicate.reference', $original->reference)
            ->assertJsonPath('duplicate.level', 'file')
            ->assertJsonPath('duplicate.blocking', true);

        // Doublon avéré : un éditeur ne peut pas passer outre
        $this->importJson($editor, 'contrat-copie.txt', 'Contrat de bail', ['allow_duplicate' => 1])->assertStatus(409);
        $this->assertSame(1, Document::count());

        $this->importJson($this->userWithRole('admin'), 'contrat-copie.txt', 'Contrat de bail', ['allow_duplicate' => 1])->assertCreated();
        $this->assertSame(2, Document::count());
    }

    public function test_duplicates_the_user_cannot_see_are_blocked_without_being_revealed(): void
    {
        $this->importJson($this->userWithRole('editor'), 'secret.txt', 'Contenu RH')->assertCreated();

        // Un autre éditeur ne voit pas le premier document : il est prévenu sans titre ni lien
        $response = $this->importJson($this->userWithRole('editor'), 'secret.txt', 'Contenu RH')
            ->assertStatus(409)
            ->assertJsonPath('duplicate.id', null)
            ->assertJsonPath('duplicate.title', null)
            ->assertJsonPath('duplicate.url', null);
        $this->assertStringNotContainsString(Document::firstOrFail()->reference, $response->json('message'));
        $this->assertSame(1, Document::count());
    }

    public function test_json_errors_are_explicit(): void
    {
        $editor = $this->userWithRole('editor');

        $this->importJson($editor, 'outil.exe', 'MZ')
            ->assertStatus(422)->assertJsonValidationErrors('import_file');

        // Catégorie réservée à un autre service
        $compta = Category::create(['organization_id' => $this->org->id, 'name' => 'Compta', 'slug' => 'compta']);
        $department = Department::create(['organization_id' => $this->org->id, 'name' => 'Compta']);
        $department->categories()->sync([$compta->id => ['access_level' => 'edit']]);

        $this->importJson($editor, 'facture.txt', 'Facture', ['category_id' => $compta->id])
            ->assertStatus(422)->assertJsonValidationErrors('category_id');

        $this->assertSame(0, Document::count());
    }

    public function test_nobody_can_choose_the_status_at_import(): void
    {
        // Le statut n'évolue que par le circuit : même un administrateur dépose un brouillon
        $this->importJson($this->userWithRole('editor'), 'pv.txt', 'PV', ['status' => 'approved'])->assertCreated();
        $this->importJson($this->userWithRole('admin'), 'pv2.txt', 'PV 2', ['status' => 'approved'])->assertCreated();

        $this->assertSame(['draft'], Document::pluck('status')->unique()->values()->all());
    }

    public function test_shared_settings_are_applied(): void
    {
        $editor = $this->userWithRole('editor');
        $category = Category::create(['organization_id' => $this->org->id, 'name' => 'Factures', 'slug' => 'factures']);

        $this->importJson($editor, 'f1.txt', 'Un', [
            'category_id' => $category->id, 'is_confidential' => 1, 'tags' => 'factures, 2026',
        ])->assertCreated();

        $doc = Document::firstOrFail();
        $this->assertSame($category->id, $doc->category_id);
        $this->assertSame('draft', $doc->status);
        $this->assertTrue($doc->is_confidential);
        $this->assertSame(['factures', '2026'], $doc->tags);
    }

    public function test_importer_is_shown_only_to_roles_that_can_import(): void
    {
        $this->actingAs($this->userWithRole('editor'))->get('/documents')
            ->assertOk()->assertSee('bulkImport(', false);

        $this->actingAs($this->userWithRole('viewer'))->get('/documents')
            ->assertOk()->assertDontSee('bulkImport(', false);
    }
}
