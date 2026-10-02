<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Services\DocumentSearch;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SearchTest extends TestCase
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
        $this->editor = $this->userWithRole('editor');
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

    private function doc(string $title, ?string $content = null, array $extra = []): Document
    {
        $ref = 'TST-' . strtoupper(fake()->unique()->bothify('??####'));

        return Document::create(array_merge([
            'organization_id' => $this->org->id,
            'reference'    => $ref,
            'title'        => $title,
            'file_path'    => $this->org->documentsDir() . "{$ref}.txt",
            'version'      => 1,
            'status'       => 'draft',
            'creator_id'   => $this->editor->id,
            'content_text' => $content,
        ], $extra));
    }

    private function searchIds(string $q, array $params = []): array
    {
        return collect($this->actingAs($this->editor)
            ->getJson('/api/documents/search?' . http_build_query(array_merge(['q' => $q], $params)))
            ->assertOk()->json('data'))->pluck('id')->all();
    }

    public function test_terms_and_highlighting(): void
    {
        $this->assertSame(['facture', 'bon de commande'], DocumentSearch::terms('facture "bon de commande" a facture'));
        $this->assertSame([], DocumentSearch::terms('   '));

        // Sans accents ni casse
        $segments = DocumentSearch::highlight('Réseau électrique de Kayes', ['reseau', 'ELECTRIQUE']);
        $this->assertSame([
            ['text' => 'Réseau', 'hit' => true],
            ['text' => ' ', 'hit' => false],
            ['text' => 'électrique', 'hit' => true],
            ['text' => ' de Kayes', 'hit' => false],
        ], $segments);

        $snippet = DocumentSearch::snippet(str_repeat('Lorem ipsum dolor sit amet. ', 40) . 'Le montant TTC est de 150 000 FCFA. ' . str_repeat('Fin du texte. ', 40), ['montant']);
        $this->assertSame('… ', $snippet[0]['text']);
        $this->assertSame(' …', end($snippet)['text']);
        $this->assertContains(['text' => 'montant', 'hit' => true], $snippet);
    }

    public function test_all_words_must_match_in_any_field(): void
    {
        $both    = $this->doc('Facture Sotelma', 'Prestation de maintenance réseau');
        $split   = $this->doc('Facture 2026-14', 'Client : Sotelma, Bamako');
        $partial = $this->doc('Facture Orange', 'Abonnement');

        $ids = $this->searchIds('facture sotelma');
        $this->assertEqualsCanonicalizing([$both->id, $split->id], $ids);
        $this->assertNotContains($partial->id, $ids);

        // Expression exacte
        $this->assertSame([$both->id], $this->searchIds('"maintenance réseau"'));
    }

    public function test_title_matches_rank_above_content_matches(): void
    {
        $content = $this->doc('Note interne', 'Rappel concernant le contrat de bail du siège');
        $title   = $this->doc('Contrat de bail — siège');

        $this->assertSame([$title->id, $content->id], $this->searchIds('contrat bail'));
        // Tri explicite par titre
        $this->assertSame([$title->id, $content->id], $this->searchIds('contrat bail', ['sort' => 'title']));
    }

    public function test_like_wildcards_are_escaped(): void
    {
        $this->doc('Rapport annuel');
        $match = $this->doc('Remise 100% fournisseur');

        $this->assertSame([$match->id], $this->searchIds('100%'));
        $this->assertSame([], $this->searchIds('__'));
    }

    public function test_api_is_light_paginated_and_respects_visibility(): void
    {
        $mine = $this->doc('Budget prévisionnel', str_repeat('Texte très long du budget prévisionnel. ', 5000));
        $other = $this->userWithRole('editor');
        $this->doc('Budget confidentiel RH', 'budget', ['creator_id' => $other->id]);

        $response = $this->actingAs($this->editor)->getJson('/api/documents/search?q=budget')->assertOk();

        $this->assertSame(1, $response->json('total'));
        $this->assertSame(1, $response->json('last_page'));
        $this->assertSame('relevance', $response->json('sort'));
        $item = $response->json('data.0');
        $this->assertSame($mine->id, $item['id']);
        $this->assertArrayNotHasKey('content_text', $item);
        $this->assertArrayNotHasKey('search_excerpt_source', $item);
        $this->assertTrue(collect($item['snippet'])->contains('hit', true));
        $this->assertLessThan(5000, strlen($response->getContent()));

        $this->actingAs($this->editor)->getJson('/api/documents/search?sort=pirate')->assertStatus(422);
    }

    public function test_filters_combine_with_text(): void
    {
        $category = Category::create(['organization_id' => $this->org->id, 'name' => 'Factures', 'slug' => 'factures']);
        $hit = $this->doc('Facture Orange', null, ['category_id' => $category->id, 'tags' => ['telecom', '2026']]);
        $this->doc('Facture Orange 2025', null, ['tags' => ['telecom']]);

        $this->assertSame([$hit->id], $this->searchIds('facture', ['category' => $category->id]));
        $this->assertSame([$hit->id], $this->searchIds('', ['tags' => 'telecom, 2026']));
    }

    public function test_quick_search_returns_documents_and_categories(): void
    {
        Category::create(['organization_id' => $this->org->id, 'name' => 'Contrats fournisseurs', 'slug' => 'contrats']);
        $doc = $this->doc('Contrat de maintenance', 'Durée : 3 ans');

        $response = $this->actingAs($this->editor)->getJson('/api/search/quick?q=contrat')->assertOk();

        $response->assertJsonPath('documents.0.id', $doc->id)
                 ->assertJsonPath('total', 1)
                 ->assertJsonPath('categories.0.name.0', ['text' => 'Contrat', 'hit' => true]);
        $this->assertStringContainsString('advanced-search?q=contrat', $response->json('more_url'));

        $this->actingAs($this->editor)->getJson('/api/search/quick?q=')->assertOk()->assertJsonPath('total', 0);
    }

    public function test_document_list_highlights_matches(): void
    {
        $this->doc('Procès-verbal du conseil', 'Le conseil approuve le budget 2027.');

        $this->actingAs($this->editor)->get('/documents?q=budget')
            ->assertOk()
            ->assertSee('<mark class="bg-amber-100 text-slate-900 rounded-sm px-0.5">budget</mark>', false)
            ->assertSee('Pertinence');
    }

    public function test_document_api_never_returns_full_text(): void
    {
        $doc = $this->doc('Long', str_repeat('a', 2000));

        $response = $this->actingAs($this->editor)->getJson("/api/documents/{$doc->id}")->assertOk();
        $this->assertArrayNotHasKey('content_text', $response->json());
        $this->assertSame(500, mb_strlen($response->json('excerpt')));
    }

    public function test_command_palette_is_available_on_every_page(): void
    {
        $this->actingAs($this->editor)->get('/dashboard')
            ->assertOk()->assertSee('commandPalette(', false)->assertSee('api\\/search\\/quick', false);
    }
}
