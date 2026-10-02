<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FolderTest extends TestCase
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

    private function category(string $name, ?Category $parent = null, array $extra = []): Category
    {
        return Category::create([
            'organization_id' => $this->org->id,
            'name'      => $name,
            'slug'      => str($name)->slug() . '-' . uniqid(),
            'parent_id' => $parent?->id,
        ] + $extra);
    }

    private function folders(): \Illuminate\Support\Collection
    {
        return Category::withoutGlobalScopes()->where('organization_id', $this->org->id)->get();
    }

    public function test_editor_creates_subfolder_that_inherits_access_and_retention(): void
    {
        $editor = $this->userWithRole('editor');
        $outsider = $this->userWithRole('editor');
        $parent = $this->category('Finance', null, ['default_retention_years' => 10]);
        $department = Department::create(['organization_id' => $this->org->id, 'name' => 'Compta']);
        $department->users()->sync([$editor->id]);
        $department->categories()->sync([$parent->id => ['access_level' => 'edit']]);

        $this->actingAs($editor)->post('/folders', ['name' => '  Factures   2025 ', 'parent_id' => $parent->id])
            ->assertRedirect();

        $folder = $this->folders()->firstWhere('name', 'Factures 2025');
        $this->assertNotNull($folder);
        $this->assertSame($parent->id, $folder->parent_id);
        $this->assertSame($editor->id, $folder->created_by);
        $this->assertSame(10, $folder->retentionRule()['years']);

        // Droits hérités : le service garde l'accès, un éditeur d'un autre service n'y range rien
        $this->assertSame('edit', $editor->fresh()->categoryAccessLevel($folder->id));
        $this->assertFalse($outsider->canFileInCategory($folder->id));
    }

    public function test_only_admin_creates_root_folders(): void
    {
        $this->actingAs($this->userWithRole('editor'))->post('/folders', ['name' => 'Racine'])->assertForbidden();
        $this->actingAs($this->userWithRole('admin'))->post('/folders', ['name' => 'Racine'])->assertRedirect();

        $this->assertTrue($this->folders()->contains('name', 'Racine'));
    }

    public function test_viewer_and_editor_without_rights_cannot_create_folders(): void
    {
        $restricted = $this->category('RH');
        $department = Department::create(['organization_id' => $this->org->id, 'name' => 'RH']);
        $department->categories()->sync([$restricted->id => ['access_level' => 'edit']]);

        $this->actingAs($this->userWithRole('viewer'))->post('/folders', ['name' => 'X', 'parent_id' => $restricted->id])->assertForbidden();
        $this->actingAs($this->userWithRole('editor'))->post('/folders', ['name' => 'X', 'parent_id' => $restricted->id])->assertForbidden();
    }

    public function test_same_name_allowed_in_different_parents_but_not_in_same_parent(): void
    {
        $admin = $this->userWithRole('admin');
        $a = $this->category('A');
        $b = $this->category('B');

        $this->actingAs($admin)->post('/folders', ['name' => '2025', 'parent_id' => $a->id])->assertRedirect();
        $this->actingAs($admin)->post('/folders', ['name' => '2025', 'parent_id' => $b->id])->assertRedirect();
        $this->actingAs($admin)->post('/folders', ['name' => '2025', 'parent_id' => $a->id])
            ->assertSessionHasErrorsIn('folder', 'name');

        $this->assertSame(2, $this->folders()->where('name', '2025')->count());
        $this->assertSame(2, $this->folders()->where('name', '2025')->pluck('slug')->unique()->count());
    }

    public function test_creator_renames_and_deletes_only_empty_folder(): void
    {
        $editor = $this->userWithRole('editor');
        $other = $this->userWithRole('editor');
        $parent = $this->category('Projets');
        $folder = $this->category('Brouillon', $parent, ['created_by' => $editor->id]);

        $this->actingAs($other)->put("/folders/{$folder->id}", ['name' => 'Pirate'])->assertForbidden();
        $this->actingAs($editor)->put("/folders/{$folder->id}", ['name' => 'Chantier'])->assertRedirect();
        $this->assertSame('Chantier', $folder->fresh()->name);

        // Un document à la corbeille bloque la suppression
        $document = Document::create([
            'organization_id' => $this->org->id,
            'reference'       => 'TST-DOS001',
            'title'           => 'Note',
            'file_path'       => $this->org->documentsDir() . 'TST-DOS001.docx',
            'version'         => 1,
            'status'          => 'draft',
            'creator_id'      => $editor->id,
            'category_id'     => $folder->id,
        ]);
        $document->delete();
        $this->actingAs($editor)->delete("/folders/{$folder->id}");
        $this->assertNotNull($folder->fresh());

        $document->forceDelete();
        $this->actingAs($editor)->delete("/folders/{$folder->id}")->assertRedirect();
        $this->assertNull($folder->fresh());
    }

    public function test_explorer_shows_folder_tiles_and_new_folder_button(): void
    {
        $admin = $this->userWithRole('admin');
        $parent = $this->category('Juridique');
        $this->category('Contrats', $parent);

        $this->actingAs($admin)->get("/documents?category={$parent->id}")
            ->assertOk()
            ->assertSee('Contrats')
            ->assertSee('Nouveau dossier');
    }
}
