<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Support\CategoryTree;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategoryTreeTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private User $me;
    private Category $compta;
    private Category $factures;
    private Category $fournisseurs;

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
        $this->me = $this->userWithRole('editor');

        // Comptabilité › Factures › Fournisseurs
        $this->compta = $this->category('Comptabilité');
        $this->factures = $this->category('Factures', $this->compta);
        $this->fournisseurs = $this->category('Fournisseurs', $this->factures);
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

    private function category(string $name, ?Category $parent = null): Category
    {
        return Category::create(['organization_id' => $this->org->id, 'name' => $name, 'slug' => str($name)->slug(), 'parent_id' => $parent?->id]);
    }

    private function doc(string $title, ?Category $category, ?User $creator = null): Document
    {
        $ref = 'TST-' . strtoupper(fake()->unique()->bothify('??####'));

        return Document::create([
            'organization_id' => $this->org->id,
            'reference'   => $ref,
            'title'       => $title,
            'file_path'   => $this->org->documentsDir() . "{$ref}.pdf",
            'version'     => 1,
            'status'      => 'draft',
            'creator_id'  => ($creator ?? $this->me)->id,
            'category_id' => $category?->id,
        ]);
    }

    // Arbre vu par un utilisateur (droits de visibilité appliqués)
    private function treeFor(User $user): CategoryTree
    {
        $this->actingAs($user);

        return Tenant::run($this->org, fn () => new CategoryTree());
    }

    public function test_counts_include_subfolders_and_only_visible_documents(): void
    {
        $this->doc('Bilan', $this->compta);
        $this->doc('Facture 1', $this->factures);
        $this->doc('Facture Sotelma', $this->fournisseurs);
        $this->doc('Hors dossier', null);
        $this->doc('Facture d\'un collègue', $this->fournisseurs, $this->userWithRole('editor'));

        $tree = $this->treeFor($this->me);
        $this->assertSame(1, $tree->get($this->compta->id)->documents_count);
        $this->assertSame(3, $tree->get($this->compta->id)->total_count);
        $this->assertSame(1, $tree->get($this->fournisseurs->id)->total_count);
        $this->assertSame(1, $tree->uncategorizedCount());
        $this->assertSame(2, $tree->get($this->fournisseurs->id)->depth);
        $this->assertSame([$this->compta->id], $tree->roots()->pluck('id')->all());

        // L'administrateur voit aussi le document du collègue
        $this->assertSame(4, $this->treeFor($this->userWithRole('admin'))->get($this->compta->id)->total_count);
    }

    public function test_path_label_and_descendants(): void
    {
        $tree = $this->treeFor($this->me);

        $this->assertSame(['Comptabilité', 'Factures', 'Fournisseurs'], array_map(fn ($c) => $c->name, $tree->path($this->fournisseurs->id)));
        $this->assertSame('Comptabilité › Factures', $tree->label($this->factures->id));
        $this->assertEqualsCanonicalizing([$this->factures->id, $this->fournisseurs->id], $tree->descendantIds($this->factures->id));
        $this->assertSame(['Comptabilité', 'Factures', 'Fournisseurs'], $tree->flat()->pluck('name')->all());
    }

    public function test_parent_loop_does_not_hang(): void
    {
        $a = $this->category('A');
        $b = $this->category('B', $a);
        DB::table('categories')->where('id', $a->id)->update(['parent_id' => $b->id]);

        $tree = $this->treeFor($this->me);
        $this->assertLessThanOrEqual(2, count($tree->path($a->id)));
        $this->assertCount(2, $tree->descendantIds($a->id));
        // Les deux restent visibles dans l'arbre pour pouvoir être corrigées
        $this->assertContains('A', $tree->flat()->pluck('name')->all());
        $this->assertContains('B', $tree->flat()->pluck('name')->all());
        $this->actingAs($this->me)->get('/documents')->assertOk();
    }

    public function test_a_category_cannot_be_moved_under_its_own_subfolder(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->put("/categories/{$this->compta->id}", ['name' => 'Comptabilité', 'slug' => 'comptabilite', 'parent_id' => $this->fournisseurs->id])
            ->assertSessionHasErrors('parent_id');
        $this->actingAs($admin)
            ->put("/categories/{$this->compta->id}", ['name' => 'Comptabilité', 'slug' => 'comptabilite', 'parent_id' => $this->compta->id])
            ->assertSessionHasErrors('parent_id');

        $this->assertNull($this->compta->fresh()->parent_id);
    }

    public function test_folder_panel_breadcrumb_and_subfolders(): void
    {
        $this->doc('Facture 1', $this->factures);

        $this->actingAs($this->me)->get('/documents')
            ->assertOk()->assertSee('folderTree(', false)->assertSee('Tous les documents')->assertSee('Fournisseurs');

        $this->actingAs($this->me)->get(route('documents.index', ['category' => $this->factures->id]))
            ->assertOk()
            ->assertSee("Fil d'Ariane", false)
            ->assertSeeInOrder(['Comptabilité', 'Factures', 'Fournisseurs'])
            // Le dossier ouvert est le titre de la page, pas un filtre à retirer…
            ->assertDontSee('Dossier : Comptabilité › Factures')
            ->assertSee('Rechercher dans « Factures »', false);

        // … sauf quand on cherche dedans : on peut alors le retirer pour chercher partout
        $this->actingAs($this->me)->get(route('documents.index', ['category' => $this->factures->id, 'q' => 'facture']))
            ->assertOk()->assertSee('Dossier : Comptabilité › Factures');
    }

    public function test_uncategorized_filter(): void
    {
        $this->doc('Classé', $this->compta);
        $this->doc('À classer', null);

        $titles = collect($this->actingAs($this->me)->get('/documents?category=none')->assertOk()->viewData('documents')->items())->pluck('title')->all();
        $this->assertSame(['À classer'], $titles);
        $this->actingAs($this->me)->get('/documents?category=none')->assertSee('Sans catégorie');
    }

    public function test_category_page_shows_full_path_and_visible_counts(): void
    {
        $this->doc('Facture d\'un collègue', $this->fournisseurs, $this->userWithRole('editor'));
        $this->doc('Ma facture', $this->fournisseurs);

        $response = $this->actingAs($this->me)->get("/categories/{$this->fournisseurs->id}")->assertOk();
        $response->assertSeeInOrder(['Catégories', 'Comptabilité', 'Factures', 'Fournisseurs']);

        // Le compteur du sous-dossier ne compte pas le document invisible
        $this->actingAs($this->me)->get("/categories/{$this->factures->id}")->assertOk()->assertSee('1 doc(s)');
    }

    public function test_document_page_links_to_each_folder_of_its_path(): void
    {
        $doc = $this->doc('Facture Sotelma', $this->fournisseurs);

        $this->actingAs($this->me)->get("/documents/{$doc->id}")
            ->assertOk()
            ->assertSee(route('documents.index', ['category' => $this->compta->id]), false)
            ->assertSee(route('documents.index', ['category' => $this->fournisseurs->id]), false);
    }

    public function test_dropping_documents_on_a_folder_moves_them(): void
    {
        $doc = $this->doc('À ranger', null);

        $this->actingAs($this->me)
            ->post('/documents/bulk', ['action' => 'move_category', 'document_ids' => [$doc->id], 'category_id' => $this->factures->id])
            ->assertRedirect();

        $this->assertSame($this->factures->id, $doc->fresh()->category_id);
        $this->actingAs($this->me)->get('/documents')->assertSee('draggable="true"', false);
        $this->actingAs($this->userWithRole('viewer'))->get('/documents')->assertDontSee('draggable="true"', false);
    }
}
