<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Role;
use App\Models\SavedFilter;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentFiltersTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private User $me;

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

    private function doc(string $title, array $extra = [], string $ext = 'pdf'): Document
    {
        $ref = 'TST-' . strtoupper(fake()->unique()->bothify('??####'));

        $document = Document::create(array_merge([
            'organization_id' => $this->org->id,
            'reference'  => $ref,
            'title'      => $title,
            'file_path'  => $this->org->documentsDir() . "{$ref}.{$ext}",
            'version'    => 1,
            'status'     => 'draft',
            'creator_id' => $this->me->id,
        ], $extra));
        // created_at n'est pas modifiable à la création (horodatage automatique)
        if (isset($extra['created_at'])) {
            $document->forceFill(['created_at' => $extra['created_at']])->saveQuietly();
        }

        return $document;
    }

    // Titres listés pour l'utilisateur avec ces paramètres
    private function titles(array $params, ?User $user = null): array
    {
        $response = $this->actingAs($user ?? $this->me)->get(route('documents.index', $params))->assertOk();

        return collect($response->viewData('documents')->items())->pluck('title')->sort()->values()->all();
    }

    public function test_scopes(): void
    {
        $other = $this->userWithRole('editor');
        $this->doc('Le mien');
        $shared = $this->doc('Partagé', ['creator_id' => $other->id]);
        DocumentShare::create(['document_id' => $shared->id, 'shared_by' => $other->id, 'shared_with' => $this->me->id, 'access_level' => 'view', 'is_active' => true]);
        $fav = $this->doc('Favori');
        $fav->favoritedBy()->attach($this->me->id);

        $rh = $this->category('RH');
        $this->doc('Note RH', ['creator_id' => $other->id, 'category_id' => $rh->id]);
        $department = Department::create(['organization_id' => $this->org->id, 'name' => 'RH']);
        $department->users()->sync([$this->me->id]);
        $department->categories()->sync([$rh->id => ['access_level' => 'view']]);

        $this->assertSame(['Favori', 'Le mien'], $this->titles(['scope' => 'mine']));
        $this->assertSame(['Partagé'], $this->titles(['scope' => 'shared']));
        $this->assertSame(['Favori'], $this->titles(['scope' => 'favorites']));
        $this->assertSame(['Note RH'], $this->titles(['scope' => 'department']));
        $this->assertCount(4, $this->titles([]));

        // L'onglet « Mes services » n'est proposé qu'aux membres d'un service
        $this->actingAs($this->me)->get('/documents')->assertSee('Mes services');
        $this->actingAs($this->userWithRole('editor'))->get('/documents')->assertDontSee('Mes services');
    }

    public function test_category_filter_includes_subcategories_by_default(): void
    {
        $compta = $this->category('Comptabilité');
        $factures = $this->category('Factures', $compta);
        $this->doc('Bilan', ['category_id' => $compta->id]);
        $this->doc('Facture 12', ['category_id' => $factures->id]);
        $this->doc('Hors catégorie');

        $this->assertSame(['Bilan', 'Facture 12'], $this->titles(['category' => $compta->id]));
        $this->assertSame(['Bilan'], $this->titles(['category' => $compta->id, 'subcats' => '0']));
    }

    public function test_field_filters(): void
    {
        $other = $this->userWithRole('admin');
        $this->doc('Contrat', ['status' => 'approved', 'tags' => ['juridique', '2026'], 'expires_at' => now()->addDays(10)]);
        $this->doc('Tableau', ['is_confidential' => true, 'created_at' => now()->subMonths(2)], 'xlsx');
        $this->doc('Ancien', ['creator_id' => $other->id, 'expires_at' => now()->subDay()]);

        $admin = $other;
        $this->assertSame(['Contrat'], $this->titles(['status' => 'approved']));
        $this->assertSame(['Contrat', 'Tableau'], $this->titles(['creator' => 'me']));
        $this->assertSame(['Tableau'], $this->titles(['type' => 'excel']));
        $this->assertSame(['Contrat'], $this->titles(['tags' => 'juridique, 2026']));
        $this->assertSame(['Tableau'], $this->titles(['confidential' => '1']));
        $this->assertSame(['Tableau'], $this->titles(['date_to' => now()->subMonth()->format('Y-m-d')]));
        $this->assertSame(['Contrat'], $this->titles(['expiry' => 'soon'], $admin));
        $this->assertSame(['Ancien'], $this->titles(['expiry' => 'expired'], $admin));
    }

    public function test_invalid_values_are_ignored(): void
    {
        $this->doc('Un');
        $this->doc('Deux');

        $this->assertCount(2, $this->titles(['status' => 'pirate', 'type' => 'exe', 'date_from' => 'hier', 'sort' => 'relevance', 'per_page' => 9999, 'category' => 'abc']));
    }

    public function test_active_filters_are_listed_with_remove_links(): void
    {
        $cat = $this->category('Factures');
        $this->doc('Facture', ['category_id' => $cat->id, 'status' => 'review']);

        $response = $this->actingAs($this->me)->get(route('documents.index', ['category' => $cat->id, 'status' => 'review', 'tags' => 'a, b']))->assertOk();
        $response->assertSee('Dossier : Factures')->assertSee('Statut : En approbation')->assertSee('Mot-clé : a')->assertSee('Tout effacer');
        // Retirer le statut conserve les autres filtres
        $response->assertSee(e(route('documents.index', ['category' => $cat->id, 'tags' => 'a, b'])), false);
    }

    public function test_saved_views_are_sanitized_personal_and_removable(): void
    {
        $this->actingAs($this->me)
            ->post('/saved-filters', ['name' => 'À valider', 'query' => 'status=review&type=pdf&pirate=1&creator=me'])
            ->assertRedirect();

        $view = SavedFilter::firstOrFail();
        $this->assertSame(['status' => 'review', 'creator' => 'me', 'type' => 'pdf'], $view->params);
        $this->assertSame($this->me->id, $view->user_id);
        $this->assertFalse($view->is_shared); // un éditeur ne peut pas partager

        // Ouverte : la vue est signalée comme active
        $this->actingAs($this->me)->get($view->url())->assertOk()->assertSee('· À valider', false);

        // Nom en double, filtres vides
        $this->actingAs($this->me)->post('/saved-filters', ['name' => 'À valider', 'query' => 'status=draft'])->assertSessionHasErrors('name');
        $this->actingAs($this->me)->post('/saved-filters', ['name' => 'Vide', 'query' => 'pirate=1'])->assertSessionHas('error');

        // Vue privée : invisible et non supprimable par un collègue
        $colleague = $this->userWithRole('editor');
        $this->actingAs($colleague)->get('/documents')->assertDontSee('À valider');
        $this->actingAs($colleague)->delete("/saved-filters/{$view->id}")->assertForbidden();

        $this->actingAs($this->me)->delete("/saved-filters/{$view->id}")->assertRedirect('/documents');
        $this->assertSame(0, SavedFilter::count());
    }

    public function test_admin_can_share_a_view_with_the_organization(): void
    {
        $admin = $this->userWithRole('admin');
        $this->actingAs($admin)->post('/saved-filters', ['name' => 'Contrats expirant', 'query' => 'expiry=soon', 'is_shared' => 1]);

        $view = SavedFilter::firstOrFail();
        $this->assertTrue($view->is_shared);
        $this->actingAs($this->me)->get('/documents')->assertSee('Contrats expirant');
        $this->actingAs($this->me)->delete("/saved-filters/{$view->id}")->assertForbidden();
    }

    public function test_saved_views_are_limited_per_user(): void
    {
        for ($i = 1; $i <= SavedFilter::MAX_PER_USER; $i++) {
            SavedFilter::create(['organization_id' => $this->org->id, 'user_id' => $this->me->id, 'name' => "Vue {$i}", 'params' => ['status' => 'draft']]);
        }

        $this->actingAs($this->me)->post('/saved-filters', ['name' => 'Une de trop', 'query' => 'status=review'])->assertSessionHas('error');
        $this->assertSame(SavedFilter::MAX_PER_USER, SavedFilter::count());
    }

    public function test_per_page_and_empty_state(): void
    {
        foreach (range(1, 20) as $i) {
            $this->doc("Doc {$i}");
        }

        $this->assertCount(15, $this->titles([]));
        $this->assertCount(20, $this->titles(['per_page' => 30]));

        $this->actingAs($this->me)->get('/documents?status=archived')->assertOk()->assertSee('Aucun document ne correspond à ces critères');
    }
}
