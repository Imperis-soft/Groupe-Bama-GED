<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentAuditLog;
use App\Models\GedNotification;
use App\Models\IntegrityCheck;
use App\Models\Organization;
use App\Models\OrganizationExport;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Services\DocumentArchivalService;
use App\Services\DocumentConverter;
use App\Support\AuditChain;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

/**
 * Archivage, lot 4 : journal chaîné, contrôle d'intégrité, export complet, copie PDF/A.
 */
class ArchiveIntegrityTest extends TestCase
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
        parent::tearDown();
    }

    private function organization(string $name, string $prefix): Organization
    {
        $org = Organization::create(['name' => $name, 'reference_prefix' => $prefix]);
        Subscription::create([
            'organization_id' => $org->id,
            'plan_id'         => Plan::where('slug', 'entreprise')->value('id'),
            'starts_at'       => today(),
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

    private function document(array $attributes = [], ?Organization $org = null, string $content = null): Document
    {
        $org ??= $this->org;
        $ref  = 'TST-' . strtoupper(fake()->unique()->bothify('??####'));
        $path = $org->documentsDir() . "{$ref}.docx";
        $content ??= "contenu {$ref}";
        Storage::disk('s3')->put($path, $content);

        return Document::create($attributes + [
            'organization_id' => $org->id,
            'reference'       => $ref,
            'title'           => 'Contrat ' . $ref,
            'file_path'       => $path,
            'checksum'        => hash('sha256', $content),
            'status'          => 'approved',
            'creator_id'      => $this->admin->id,
        ]);
    }

    private function log(Document $document, string $action): void
    {
        $this->actingAs($this->admin);
        app(DocumentArchivalService::class)->logAction($document, $action, "Action {$action}", ['a' => 1, 'b' => 'x'], ['z' => 2, 'y' => [3, 1]]);
    }

    // ---------- Journal chaîné ----------

    public function test_audit_log_is_sealed_in_a_chain_per_organization(): void
    {
        $document = $this->document();
        foreach (['created', 'viewed', 'approved'] as $action) {
            $this->log($document, $action);
        }
        $other = $this->organization('Autre', 'AUT');
        $this->log($this->document([], $other), 'created');

        $logs = DocumentAuditLog::withoutGlobalScopes()->where('organization_id', $this->org->id)->orderBy('sequence')->get();
        $this->assertSame([1, 2, 3], $logs->pluck('sequence')->map(fn ($s) => (int) $s)->all());
        $this->assertNull($logs[0]->previous_hash);
        $this->assertSame($logs[0]->hash, $logs[1]->previous_hash);
        $this->assertSame($this->admin->full_name, $logs[0]->user_name);
        $this->assertSame(1, (int) DocumentAuditLog::withoutGlobalScopes()->where('organization_id', $other->id)->value('sequence'));

        $this->assertSame([], AuditChain::verify($this->org->id));
        $this->assertSame([], AuditChain::verify($other->id));
    }

    public function test_tampering_with_the_audit_log_is_detected(): void
    {
        $document = $this->document();
        foreach (['created', 'approved', 'signed', 'viewed'] as $action) {
            $this->log($document, $action);
        }
        $rows = DB::table('document_audit_logs')->where('organization_id', $this->org->id)->orderBy('sequence')->get();

        // Modification directe en base
        DB::table('document_audit_logs')->where('id', $rows[1]->id)->update(['description' => 'Jamais approuvé']);
        $this->assertStringContainsString('contenu modifié', implode(' ', AuditChain::verify($this->org->id)));
        DB::table('document_audit_logs')->where('id', $rows[1]->id)->update(['description' => $rows[1]->description]);
        $this->assertSame([], AuditChain::verify($this->org->id));

        // Suppression d'une ligne au milieu
        DB::table('document_audit_logs')->where('id', $rows[2]->id)->delete();
        $this->assertStringContainsString('manquante', implode(' ', AuditChain::verify($this->org->id)));
    }

    public function test_deleting_the_latest_audit_lines_is_detected(): void
    {
        $document = $this->document();
        foreach (['created', 'approved', 'viewed'] as $action) {
            $this->log($document, $action);
        }
        DB::table('document_audit_logs')->where('organization_id', $this->org->id)->orderByDesc('sequence')->limit(1)->delete();

        $this->assertStringContainsString('Fin de journal', implode(' ', AuditChain::verify($this->org->id)));
    }

    public function test_chain_survives_document_purge_and_account_deletion(): void
    {
        $document = $this->document();
        $this->log($document, 'approved');
        $document->forceDelete();
        $this->admin->roles()->detach();
        DB::table('users')->where('id', $this->admin->id)->delete();

        $this->assertSame([], AuditChain::verify($this->org->id));
        $this->assertSame($this->admin->full_name, DocumentAuditLog::withoutGlobalScopes()->value('user_name'));
    }

    // ---------- Contrôle d'intégrité ----------

    public function test_integrity_check_detects_altered_and_missing_files(): void
    {
        $intact  = $this->document();
        $altered = $this->document();
        $missing = $this->document();
        $legacy  = $this->document(['checksum' => null]);

        Storage::disk('s3')->put($altered->file_path, 'contenu falsifié');
        Storage::disk('s3')->delete($missing->file_path);
        $editor = $this->user('editor');
        $super  = User::factory()->create(['is_super_admin' => true, 'organization_id' => null]);

        $this->artisan('documents:verify-integrity')->expectsOutputToContain('2 anomalie(s)')->assertSuccessful();

        $this->assertSame('ok', $intact->fresh()->integrity_status);
        $this->assertSame('altered', $altered->fresh()->integrity_status);
        $this->assertSame('missing', $missing->fresh()->integrity_status);
        // Fichier sans empreinte : empreinte de référence enregistrée
        $this->assertSame(hash('sha256', "contenu {$legacy->reference}"), $legacy->fresh()->checksum);

        $check = IntegrityCheck::withoutGlobalScopes()->latest('id')->firstOrFail();
        $this->assertSame(2, $check->files_failed);
        $this->assertSame(1, $check->baselines);
        $this->assertTrue(DocumentAuditLog::withoutGlobalScopes()->where('action', 'integrity_failed')->where('document_reference', $altered->reference)->exists());

        // Alerte : administrateurs de l'entreprise et super admin, pas les autres
        $this->assertTrue(GedNotification::where('user_id', $this->admin->id)->where('type', 'integrity_alert')->exists());
        $this->assertTrue(GedNotification::where('user_id', $super->id)->where('type', 'integrity_alert')->exists());
        $this->assertFalse(GedNotification::where('user_id', $editor->id)->exists());

        $this->artisan('schedule:list')->expectsOutputToContain('documents:verify-integrity')->assertSuccessful();
    }

    public function test_admin_runs_a_check_and_sees_the_result(): void
    {
        $this->document();
        Tenant::set($this->org);

        $this->actingAs($this->admin)->post('/retention/verify')->assertSessionHas('success');
        $this->get('/retention?tab=integrity')->assertOk()->assertSee('Tout est intact');
    }

    // ---------- Export complet ----------

    public function test_full_export_contains_documents_versions_metadata_audit_and_records(): void
    {
        $category = Category::create(['organization_id' => $this->org->id, 'name' => 'Juridique', 'slug' => 'juridique']);
        $sub = Category::create(['organization_id' => $this->org->id, 'name' => 'Contrats/Baux', 'slug' => 'baux', 'parent_id' => $category->id]);
        $document = $this->document(['category_id' => $sub->id, 'title' => 'Bail : siège']);
        $this->log($document, 'approved');
        Tenant::set($this->org);

        $this->actingAs($this->admin)->post('/retention/exports')->assertRedirect();

        $export = OrganizationExport::firstOrFail();
        $this->assertSame('done', $export->status, (string) $export->error);
        $this->assertSame(1, $export->documents_count);
        $this->assertStringStartsWith($this->org->storagePrefix() . '/exports/', $export->path);
        $this->assertTrue(GedNotification::where('user_id', $this->admin->id)->where('type', 'export_ready')->exists());

        $local = tempnam(sys_get_temp_dir(), 'zip');
        file_put_contents($local, Storage::disk('s3')->get($export->path));
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($local) === true);
        $names = collect(range(0, $zip->numFiles - 1))->map(fn ($i) => $zip->getNameIndex($i));

        $this->assertContains("documents/Juridique/Contrats-Baux/{$document->reference} - Bail - siège.docx", $names);
        $this->assertSame("contenu {$document->reference}", $zip->getFromName("documents/Juridique/Contrats-Baux/{$document->reference} - Bail - siège.docx"));
        $this->assertStringContainsString($document->reference, $zip->getFromName('documents.csv'));
        $this->assertStringContainsString($document->checksum, $zip->getFromName('documents.csv'));
        $this->assertStringContainsString('Approuvé', $zip->getFromName('journal-audit.csv'));
        $this->assertContains('LISEZMOI.txt', $names);
        $zip->close();
        @unlink($local);

        $this->get(route('retention.exports.download', $export))->assertOk()->assertDownload(basename($export->path));
    }

    public function test_export_is_private_to_its_organization_and_available_to_super_admin(): void
    {
        $this->document();
        $super = User::factory()->create(['is_super_admin' => true, 'organization_id' => null]);

        $this->actingAs($super)->post("/super-admin/organizations/{$this->org->id}/exports")->assertRedirect();
        $export = OrganizationExport::withoutGlobalScopes()->firstOrFail();
        $this->assertSame('done', $export->status, (string) $export->error);
        $this->get(route('super.organizations.exports.download', [$this->org, $export]))->assertOk();

        $other = $this->organization('Autre', 'AUT');
        Tenant::set($other);
        $this->actingAs($this->user('admin', $other))->get(route('retention.exports.download', $export))->assertNotFound();
        $this->actingAs($this->user('editor'))->post('/retention/exports')->assertForbidden();
    }

    // ---------- Copie PDF/A ----------

    public function test_archiving_creates_a_pdfa_copy_next_to_the_original(): void
    {
        $this->partialMock(DocumentConverter::class, fn ($mock) => $mock->shouldReceive('toPdfA')->andReturn('%PDF-1.7 PDF/A-2b'));
        $document = $this->document();
        Tenant::set($this->org);

        $this->actingAs($this->admin)->post("/documents/{$document->id}/archive")->assertRedirect();

        $document->refresh();
        $this->assertSame($this->org->documentsDir() . "archives/{$document->reference}_v1_pdfa.pdf", $document->archival_copy_path);
        $this->assertSame(hash('sha256', '%PDF-1.7 PDF/A-2b'), $document->archival_copy_checksum);
        Storage::disk('s3')->assertExists($document->archival_copy_path);
        Storage::disk('s3')->assertExists($document->file_path);
        $this->get(route('documents.archival-copy', $document))->assertOk();

        // Contrôlée comme les autres fichiers, et détruite avec le document
        Storage::disk('s3')->put($document->archival_copy_path, 'altéré');
        $this->artisan('documents:verify-integrity')->assertSuccessful();
        $this->assertSame('altered', $document->fresh()->integrity_status);

        app(DocumentArchivalService::class)->deleteStoredFiles($document);
        Storage::disk('s3')->assertMissing($document->archival_copy_path);
    }

    public function test_without_conversion_tool_the_original_is_archived_as_is(): void
    {
        $this->partialMock(DocumentConverter::class, fn ($mock) => $mock->shouldReceive('toPdfA')->andReturn(null));
        $document = $this->document();

        $this->actingAs($this->admin)->post("/documents/{$document->id}/archive")->assertRedirect();

        $this->assertTrue($document->fresh()->isArchived());
        $this->assertNull($document->fresh()->archival_copy_path);
        $this->assertTrue(DocumentAuditLog::withoutGlobalScopes()->where('action', 'archival_copy_skipped')->exists());
    }
}
