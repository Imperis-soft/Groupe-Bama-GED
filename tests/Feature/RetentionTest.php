<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentAuditLog;
use App\Models\EliminationRecord;
use App\Models\GedNotification;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Archivage, lot 3 : fin de conservation calculée, sort final, élimination validée avec procès-verbal.
 */
class RetentionTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');

        $this->org = $this->organization('Test SA', 'TST');
        foreach (['admin', 'editor', 'viewer'] as $name) {
            Role::create(['name' => $name, 'display_name' => ucfirst($name)]);
        }
        $this->admin = $this->user('admin');
    }

    protected function tearDown(): void
    {
        Tenant::clear();
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function organization(string $name, string $prefix): Organization
    {
        $org = Organization::create(['name' => $name, 'reference_prefix' => $prefix]);
        Subscription::create([
            'organization_id' => $org->id,
            'plan_id'         => Plan::where('slug', 'entreprise')->value('id'),
            'starts_at'       => today()->subYears(20),
            'ends_at'         => today()->addYear(),
        ]);
        return $org;
    }

    private function user(string $role, ?Organization $org = null): User
    {
        $user = User::factory()->create(['organization_id' => ($org ?? $this->org)->id]);
        $user->roles()->attach(Role::where('name', $role)->first());
        return $user;
    }

    private function category(string $name, ?int $years, string $trigger = 'created', string $disposition = 'destroy', ?Category $parent = null): Category
    {
        return Category::create([
            'organization_id' => $this->org->id, 'name' => $name, 'slug' => str($name)->slug(), 'parent_id' => $parent?->id,
            'default_retention_years' => $years, 'retention_trigger' => $trigger, 'final_disposition' => $disposition,
        ]);
    }

    // Document déposé à une date donnée (dans le passé)
    private function document(?Category $category, string $createdAt, array $attributes = [], ?Organization $org = null): Document
    {
        $org ??= $this->org;
        $ref  = 'TST-' . strtoupper(fake()->unique()->bothify('??####'));
        $path = $org->documentsDir() . "{$ref}.pdf";
        Storage::disk('s3')->put($path, "%PDF contenu {$ref}");

        Carbon::setTestNow($createdAt);
        $document = Document::create($attributes + [
            'organization_id' => $org->id,
            'reference'       => $ref,
            'title'           => 'Pièce ' . $ref,
            'file_path'       => $path,
            'checksum'        => hash('sha256', "%PDF contenu {$ref}"),
            'creator_id'      => $this->admin->id,
            'category_id'     => $category?->id,
            'retention_years' => $category?->retentionRule()['years'] ?? 5,
        ]);
        Carbon::setTestNow();

        return $document->fresh();
    }

    public function test_end_of_retention_depends_on_the_category_starting_point(): void
    {
        $created = $this->document($this->category('Courrier', 2), '2020-03-15');
        $fiscal  = $this->document($this->category('Factures', 10, 'fiscal_year_end'), '2020-03-15');
        $due     = $this->document($this->category('Contrats', 5, 'due_date'), '2020-03-15');
        $closed  = $this->document($this->category('Dossiers', 3, 'archived'), '2020-03-15');

        $this->assertSame('2022-03-15', $created->retention_until->toDateString());
        $this->assertSame('2030-12-31', $fiscal->retention_until->toDateString());
        // Pas encore d'échéance / pas encore archivé : le délai n'a pas commencé
        $this->assertNull($due->retention_until);
        $this->assertNull($closed->retention_until);

        $due->update(['expires_at' => '2024-06-30']);
        $this->assertSame('2029-06-30', $due->fresh()->retention_until->toDateString());

        $this->actingAs($this->admin)->post("/documents/{$closed->id}/archive")->assertRedirect();
        $this->assertSame(today()->addYears(3)->toDateString(), $closed->fresh()->retention_until->toDateString());
    }

    public function test_sub_category_inherits_the_parent_rule(): void
    {
        $parent = $this->category('Comptabilité', 10, 'fiscal_year_end', 'destroy');
        $child  = $this->category('Notes de frais', null, 'created', 'keep', $parent);

        $this->assertSame(['years' => 10, 'trigger' => 'fiscal_year_end', 'disposition' => 'destroy'], $child->retentionRule());
        $this->assertSame('2031-12-31', $this->document($child, '2021-05-02')->retention_until->toDateString());
    }

    public function test_admin_eliminates_due_documents_with_a_record(): void
    {
        $category = $this->category('Courrier', 2);
        $doc  = $this->document($category, '2020-01-10');
        $kept = $this->document($category, today()->toDateString());

        $this->actingAs($this->admin)->get('/retention')->assertOk()->assertSee($doc->reference)->assertDontSee($kept->reference);

        $this->post('/retention/eliminate', ['documents' => [$doc->id], 'reason' => 'Durée légale échue, aucun litige'])
            ->assertSessionHasErrors('confirm');
        $this->assertNotNull(Document::find($doc->id));

        $this->post('/retention/eliminate', ['documents' => [$doc->id], 'reason' => 'Durée légale échue, aucun litige', 'confirm' => 1])
            ->assertRedirect(route('retention.index', ['tab' => 'records']));

        // Détruit : base et fichiers
        $this->assertDatabaseMissing('documents', ['id' => $doc->id]);
        Storage::disk('s3')->assertMissing($doc->file_path);

        // Procès-verbal : numéroté, PDF enregistré dans l'espace de l'entreprise, instantané complet
        $record = EliminationRecord::firstOrFail();
        $this->assertSame('PV-' . now()->year . '-0001', $record->number);
        $this->assertSame($this->admin->full_name, $record->approved_by_name);
        $this->assertSame($doc->reference, $record->documents[0]['reference']);
        $this->assertSame($doc->checksum, $record->documents[0]['checksum']);
        $this->assertStringStartsWith($this->org->storagePrefix() . '/proces-verbaux/', $record->pdf_path);
        $pdf = Storage::disk('s3')->get($record->pdf_path);
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertSame(hash('sha256', $pdf), $record->pdf_checksum);

        // La trace survit
        $this->assertStringContainsString($record->number, DocumentAuditLog::withoutGlobalScopes()
            ->where('document_reference', $doc->reference)->where('action', 'eliminated')->value('description'));

        $this->get(route('retention.records.pdf', $record))->assertOk()->assertDownload($record->number . '.pdf');
        $this->get('/retention?tab=records')->assertOk()->assertSee($record->number);
    }

    public function test_frozen_kept_or_not_yet_due_documents_are_never_eliminated(): void
    {
        $held    = $this->document($this->category('Contentieux', 1), '2015-01-01', ['legal_hold' => true, 'legal_hold_reason' => 'Procès en cours']);
        $keep    = $this->document($this->category('Statuts', 1, 'created', 'keep'), '2015-01-01');
        $recent  = $this->document($this->category('Courrier', 5), today()->toDateString());
        $ids     = [$held->id, $keep->id, $recent->id];

        $this->actingAs($this->admin)->post('/retention/eliminate', ['documents' => $ids, 'reason' => 'Tentative de nettoyage', 'confirm' => 1])
            ->assertSessionHas('error');

        $this->assertSame(3, Document::whereIn('id', $ids)->count());
        $this->assertSame(0, EliminationRecord::count());
    }

    public function test_only_admins_decide(): void
    {
        $doc = $this->document($this->category('Courrier', 1), '2015-01-01');
        $editor = $this->user('editor');

        $this->actingAs($editor)->get('/retention')->assertForbidden();
        $this->post('/retention/eliminate', ['documents' => [$doc->id], 'reason' => 'Je fais le ménage', 'confirm' => 1])->assertForbidden();
        $this->assertNotNull(Document::find($doc->id));
    }

    public function test_extend_and_keep_permanently(): void
    {
        $category = $this->category('Courrier', 2);
        $extended = $this->document($category, '2020-01-10');
        $kept     = $this->document($category, '2020-01-10');

        $this->actingAs($this->admin)->post('/retention/extend', ['documents' => [$extended->id], 'years' => 5, 'reason' => 'Contrôle fiscal annoncé'])->assertRedirect();
        $this->assertSame('2027-01-10', $extended->fresh()->retention_until->toDateString());

        $this->post('/retention/keep', ['documents' => [$kept->id], 'reason' => 'Valeur historique'])->assertRedirect();
        $this->assertTrue($kept->fresh()->retention_permanent);
        $this->assertNull($kept->fresh()->retention_until);
        $this->assertFalse($kept->fresh()->isEliminable());

        $this->assertSame(0, Document::retentionDue()->count());
        $this->assertTrue(DocumentAuditLog::withoutGlobalScopes()->where('action', 'retention_extended')->exists());
        $this->assertTrue(DocumentAuditLog::withoutGlobalScopes()->where('action', 'retention_permanent')->exists());
    }

    public function test_changing_a_category_rule_recomputes_its_documents(): void
    {
        $category = $this->category('Courrier', 2);
        $doc = $this->document($category, '2020-01-10');
        Tenant::set($this->org);

        $this->actingAs($this->admin)->put("/categories/{$category->id}", [
            'name' => 'Courrier', 'slug' => 'courrier', 'default_retention_years' => 10,
            'retention_trigger' => 'fiscal_year_end', 'final_disposition' => 'destroy',
        ])->assertRedirect();
        // Durée du document inchangée (fixée au dépôt), point de départ appliqué
        $this->assertSame('2022-12-31', $doc->fresh()->retention_until->toDateString());

        $this->put("/categories/{$category->id}", [
            'name' => 'Courrier', 'slug' => 'courrier', 'default_retention_years' => 10,
            'retention_trigger' => 'fiscal_year_end', 'final_disposition' => 'destroy', 'apply_retention_to_existing' => 1,
        ])->assertRedirect();
        $this->assertSame('2030-12-31', $doc->fresh()->retention_until->toDateString());
    }

    public function test_records_cannot_be_altered_or_seen_by_another_organization(): void
    {
        $doc = $this->document($this->category('Courrier', 1), '2015-01-01');
        $this->actingAs($this->admin)->post('/retention/eliminate', ['documents' => [$doc->id], 'reason' => 'Durée échue depuis longtemps', 'confirm' => 1]);
        $record = EliminationRecord::firstOrFail();

        try {
            $record->update(['reason' => 'Autre motif']);
            $this->fail('Le procès-verbal a pu être modifié.');
        } catch (\LogicException) {}
        try {
            $record->delete();
            $this->fail('Le procès-verbal a pu être supprimé.');
        } catch (\LogicException) {}

        $other = $this->organization('Autre SARL', 'AUT');
        Tenant::set($other);
        $this->actingAs($this->user('admin', $other))->get(route('retention.records.pdf', $record))->assertNotFound();
    }

    public function test_weekly_review_only_notifies_admins_and_destroys_nothing(): void
    {
        $doc = $this->document($this->category('Courrier', 1), '2015-01-01');
        $editor = $this->user('editor');

        $this->artisan('documents:retention-review')->assertSuccessful();
        $this->artisan('schedule:list')->expectsOutputToContain('documents:retention-review')->assertSuccessful();

        $this->assertTrue(GedNotification::where('user_id', $this->admin->id)->where('type', 'retention_due')->exists());
        $this->assertFalse(GedNotification::where('user_id', $editor->id)->exists());
        $this->assertNotNull(Document::find($doc->id));
    }

    public function test_recompute_command_fills_existing_documents(): void
    {
        $doc = $this->document($this->category('Courrier', 2), '2020-01-10');
        Document::whereKey($doc->id)->update(['retention_until' => null]);

        $this->artisan('documents:retention-recompute')->expectsOutputToContain('1 date(s)')->assertSuccessful();
        $this->assertSame('2022-01-10', $doc->fresh()->retention_until->toDateString());
    }
}
