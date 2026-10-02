<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentVerification;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Services\WordTemplate;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentVerificationTest extends TestCase
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

        $this->editor = User::factory()->create(['organization_id' => $this->org->id]);
        $this->editor->roles()->attach(Role::where('name', 'editor')->first());
    }

    protected function tearDown(): void
    {
        Tenant::clear();
        parent::tearDown();
    }

    // Dépôt d'un fichier Word mis en page avec le modèle de l'entreprise (QR code en pied de page)
    private function createDocument(array $extra = []): Document
    {
        $docx = app(WordTemplate::class)->create([
            'organization' => $this->org, 'reference' => 'TST-MODELE', 'title' => 'Note de service', 'version' => 1,
            'category' => null, 'author' => $this->editor->full_name, 'confidential' => false,
            'verification_code' => DocumentVerification::generateCode(),
        ]);

        $this->actingAs($this->editor)->post('/documents/create', array_merge([
            'title'       => 'Note de service',
            'import_file' => UploadedFile::fake()->createWithContent('note.docx', file_get_contents($docx)),
        ], $extra))->assertSessionHasNoErrors();
        @unlink($docx);
        Tenant::clear();

        return Document::where('title', 'Note de service')->firstOrFail();
    }

    // Contenu XML de toutes les parties du fichier Word
    private function docxParts(Document $doc): array
    {
        $path = tempnam(sys_get_temp_dir(), 'test_docx_');
        file_put_contents($path, Storage::disk('s3')->get($doc->file_path));
        $zip = new \ZipArchive();
        $zip->open($path);
        $parts = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $parts[$zip->getNameIndex($i)] = $zip->getFromIndex($i);
        }
        $zip->close();
        unlink($path);

        return $parts;
    }

    public function test_any_uploaded_file_gets_a_verification_code(): void
    {
        $this->actingAs($this->editor)->post('/documents/create', [
            'title'       => 'Présentation',
            'import_file' => UploadedFile::fake()->createWithContent('presentation.pptx', 'diapositives'),
        ])->assertSessionHasNoErrors();

        $doc  = Document::where('title', 'Présentation')->firstOrFail();
        $this->assertMatchesRegularExpression('/^[' . DocumentVerification::ALPHABET . ']{32}$/', $doc->verification->verification_code);
        $this->assertStringEndsWith('.pptx', $doc->file_path);
        // L'empreinte enregistrée est celle du fichier stocké
        $this->assertSame(hash('sha256', Storage::disk('s3')->get($doc->file_path)), $doc->versions()->first()->checksum);
    }

    public function test_a_document_can_no_longer_be_created_without_a_file(): void
    {
        $this->actingAs($this->editor)->post('/documents/create', ['title' => 'Note de service'])->assertSessionHasErrors('import_file');

        $this->assertSame(0, Document::count());
    }

    public function test_verification_page_states(): void
    {
        $doc  = $this->createDocument();
        $code = $doc->verification->verification_code;

        $this->get("/verify/{$code}")->assertOk()
            ->assertSee('DOCUMENT NON VALIDÉ')
            ->assertSee($doc->reference)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $doc->update(['status' => 'approved']);
        $this->get("/verify/{$code}")->assertOk()->assertSee('DOCUMENT AUTHENTIQUE');

        $doc->delete();
        $this->get("/verify/{$code}")->assertOk()->assertSee('DOCUMENT RETIRÉ');

        $this->get('/verify/INCONNU' . str_repeat('X', 25))->assertNotFound()->assertSee('DOCUMENT NON RECONNU');
    }

    public function test_confidential_document_hides_title_on_public_page(): void
    {
        $doc = $this->createDocument(['is_confidential' => 1]);

        $this->get('/verify/' . $doc->verification->verification_code)->assertOk()
            ->assertDontSee('Note de service')
            ->assertSee('Masqué (document confidentiel)');
    }

    public function test_manual_lookup_with_printed_short_code(): void
    {
        $doc   = $this->createDocument();
        $code  = $doc->verification->verification_code;
        $typed = strtolower(DocumentVerification::displayCode($code));

        $this->get('/verify?code=' . urlencode(" {$typed} "))->assertRedirect("/verify/{$code}");
        $this->get('/verify?code=ABCD-EFGH-JKMN')->assertNotFound()->assertSee('Aucun document ne correspond');
        $this->get('/verify')->assertOk()->assertSee('Vérifier un document');
    }

    public function test_result_page_shows_company_and_hides_internal_details(): void
    {
        $this->org->update(['address' => 'ACI 2000, Bamako', 'phone' => '+223 20 22 00 00', 'email' => 'contact@test.ml']);
        $doc = $this->createDocument(['expires_at' => now()->addYear()->toDateString()]);

        $this->get('/verify/' . $doc->verification->verification_code)->assertOk()
            ->assertSee('TEST SA')
            ->assertSee('ACI 2000, Bamako')
            ->assertSee('+223 20 22 00 00')
            ->assertSee('contact@test.ml')
            ->assertDontSee('Version en vigueur')
            ->assertDontSee('Échéance')
            ->assertDontSee('Aperçu')
            ->assertDontSee('Vous avez reçu le fichier')
            ->assertDontSee($doc->file_path)
            ->assertDontSee((string) $doc->minio_url);

        $this->get('/verify/' . $doc->verification->verification_code . '/preview')->assertNotFound();
    }

    public function test_qr_regeneration_keeps_written_content(): void
    {
        $doc = $this->createDocument();

        // L'utilisateur a rédigé son document
        $path = tempnam(sys_get_temp_dir(), 'test_docx_');
        file_put_contents($path, Storage::disk('s3')->get($doc->file_path));
        $zip = new \ZipArchive();
        $zip->open($path);
        $zip->addFromString('word/document.xml', str_replace('</w:body>', '<w:p><w:r><w:t>Texte rédigé</w:t></w:r></w:p></w:body>', $zip->getFromName('word/document.xml')));
        $zip->close();
        Storage::disk('s3')->put($doc->file_path, file_get_contents($path));
        unlink($path);

        $this->artisan('documents:regenerate-qrcodes', ['--id' => $doc->id])->assertSuccessful();

        $parts = $this->docxParts($doc);
        $this->assertStringContainsString('Texte rédigé', $parts['word/document.xml']);
        $this->assertSame(hash('sha256', Storage::disk('s3')->get($doc->file_path)), $doc->fresh()->checksum);
    }

    public function test_confidential_download_is_watermarked_without_losing_layout(): void
    {
        $doc = $this->createDocument(['is_confidential' => 1]);

        $response = $this->actingAs($this->editor)->get("/documents/{$doc->id}/download");
        $response->assertOk();

        $path = $response->baseResponse->getFile()->getPathname();
        $zip  = new \ZipArchive();
        $zip->open($path);
        $this->assertStringContainsString('CONFIDENTIEL — ' . $this->editor->full_name, $zip->getFromName('word/header1.xml'));
        $this->assertStringContainsString('TST-MODELE', $zip->getFromName('word/header1.xml')); // mise en page conservée
        $zip->close();

        $this->assertInstanceOf(WordTemplate::class, app(WordTemplate::class));
    }
}
