<?php

namespace Tests\Feature;

use App\Jobs\IndexDocumentText;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Services\DocumentArchivalService;
use App\Services\DocumentConverter;
use App\Support\FileType;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileFormatsTest extends TestCase
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

    // Archive ZIP en mémoire (format Office Open XML / OpenDocument)
    private function zipBytes(array $files): string
    {
        $path = tempnam(sys_get_temp_dir(), 'test_zip_');
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::OVERWRITE);
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();
        $bytes = file_get_contents($path);
        unlink($path);

        return $bytes;
    }

    private function xlsx(): string
    {
        return $this->zipBytes([
            '[Content_Types].xml'   => '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>',
            'xl/workbook.xml'       => '<workbook/>',
            'xl/sharedStrings.xml'  => '<sst><si><t>Budget prévisionnel</t></si><si><t>Fournisseur Sotelma</t></si></sst>',
            'xl/worksheets/sheet1.xml' => '<worksheet><sheetData><row><c t="s"><v>0</v></c></row></sheetData></worksheet>',
        ]);
    }

    private function import(string $name, string $content, array $extra = [])
    {
        return $this->actingAs($this->editor)->post('/documents/create', array_merge([
            'title'       => 'Import ' . $name,
            'import_mode' => 1,
            'import_file' => UploadedFile::fake()->createWithContent($name, $content),
        ], $extra));
    }

    public function test_registry_describes_formats(): void
    {
        $this->assertSame('excel', FileType::fromExtension('XLSX')->family);
        $this->assertSame('fa-file-powerpoint', FileType::fromPath('docs/a.pptx')->icon());
        $this->assertSame('sheet', FileType::fromExtension('csv')->previewMode);
        $this->assertSame('other', FileType::fromExtension('exe')->family);
        $this->assertTrue(FileType::fromExtension('doc')->isCompatibleWith(FileType::fromExtension('docx')));
        $this->assertFalse(FileType::fromExtension('pdf')->isCompatibleWith(FileType::fromExtension('docx')));
        $this->assertStringContainsString('.xlsx', FileType::acceptAttribute('excel'));
        $this->assertStringNotContainsString('.pdf', FileType::acceptAttribute('excel'));
    }

    public function test_excel_import_is_stored_indexed_and_previewed(): void
    {
        $this->import('budget.xlsx', $this->xlsx())->assertSessionHasNoErrors();

        $doc = Document::where('title', 'Import budget.xlsx')->firstOrFail();
        $this->assertStringEndsWith('.xlsx', $doc->file_path);
        Storage::disk('s3')->assertExists($doc->file_path);
        // Texte extrait des cellules pour la recherche
        $this->assertStringContainsString('Fournisseur Sotelma', $doc->content_text);

        $this->actingAs($this->editor)->get("/documents/{$doc->id}/preview")
            ->assertOk()->assertSee('sheetPreview', false)->assertSee('xlsx.full.min.js', false);

        $this->actingAs($this->editor)->get("/documents/{$doc->id}/stream")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_image_csv_and_text_imports(): void
    {
        $this->import('scan.png', UploadedFile::fake()->image('scan.png')->getContent())->assertSessionHasNoErrors();
        $this->import('export.csv', "Nom;Montant\nOrange Mali;150000\n")->assertSessionHasNoErrors();
        $this->import('notes.txt', "Compte rendu de réunion\n")->assertSessionHasNoErrors();

        $image = Document::where('title', 'Import scan.png')->firstOrFail();
        $this->actingAs($this->editor)->get("/documents/{$image->id}/preview")->assertOk()->assertSee('cursor-zoom-in', false);

        $csv = Document::where('title', 'Import export.csv')->firstOrFail();
        $this->assertStringContainsString('Orange Mali', $csv->content_text);

        $txt = Document::where('title', 'Import notes.txt')->firstOrFail();
        $this->assertStringContainsString('Compte rendu', $txt->content_text);
    }

    public function test_dangerous_or_mismatched_files_are_rejected(): void
    {
        $this->import('outil.exe', 'MZ binaire')->assertSessionHasErrors('import_file');
        $this->import('page.php', '<?php echo 1;')->assertSessionHasErrors('import_file');
        // Extension acceptée mais contenu HTML / incohérent
        $this->import('facture.pdf', '<html><script>alert(1)</script></html>')->assertSessionHasErrors('import_file');
        $this->import('photo.jpg', 'ceci n est pas une image')->assertSessionHasErrors('import_file');

        $this->assertSame(0, Document::count());
    }

    public function test_new_version_must_stay_in_the_same_family(): void
    {
        $this->import('budget.xlsx', $this->xlsx());
        $doc = Document::firstOrFail();

        $this->actingAs($this->editor)
            ->post("/documents/{$doc->id}/upload-version", ['file' => UploadedFile::fake()->createWithContent('budget.pdf', "%PDF-1.4\n%%EOF")])
            ->assertSessionHasErrors('file');

        $this->actingAs($this->editor)
            ->post("/documents/{$doc->id}/upload-version", ['file' => UploadedFile::fake()->createWithContent('budget-v2.csv', "Poste;Montant\nLoyer;500000\n")])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $doc->fresh()->version);
        $this->assertStringEndsWith('.csv', $doc->fresh()->file_path);
    }

    public function test_powerpoint_and_opendocument_text_is_indexed(): void
    {
        $this->import('vente.pptx', $this->zipBytes([
            'ppt/slides/slide1.xml' => '<p:sld><a:p><a:t>Plan commercial 2027</a:t></a:p></p:sld>',
            'ppt/slides/slide2.xml' => '<p:sld><a:p><a:t>Objectifs Kayes</a:t></a:p></p:sld>',
        ]));
        $this->import('statuts.odt', $this->zipBytes([
            'mimetype'    => 'application/vnd.oasis.opendocument.text',
            'content.xml' => '<office:document-content><text:p>Statuts de la SARL</text:p></office:document-content>',
        ]));

        $pptx = Document::where('title', 'Import vente.pptx')->firstOrFail();
        $this->assertStringContainsString('Plan commercial 2027', $pptx->content_text);
        $this->assertStringContainsString('Objectifs Kayes', $pptx->content_text);
        $this->assertStringContainsString('Statuts de la SARL', Document::where('title', 'Import statuts.odt')->value('content_text'));
    }

    public function test_zip_archive_contents_are_listed(): void
    {
        $this->import('dossier.zip', $this->zipBytes([
            'contrats/contrat-bail.pdf' => 'x',
            'factures/facture-001.pdf'  => 'y',
        ]));
        $doc = Document::firstOrFail();

        $this->assertStringContainsString('contrat bail', $doc->content_text);

        $this->actingAs($this->editor)->getJson("/documents/{$doc->id}/preview/archive")
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonFragment(['name' => 'factures/facture-001.pdf']);
    }

    public function test_converted_preview_depends_on_libreoffice(): void
    {
        $this->import('vente.pptx', $this->zipBytes(['ppt/slides/slide1.xml' => '<a:t>Slide</a:t>']));
        $doc = Document::firstOrFail();

        // Sans LibreOffice : message clair et téléchargement proposé
        $this->mock(DocumentConverter::class, function ($mock) {
            $mock->shouldReceive('canConvertToPdf')->andReturn(false);
            $mock->shouldReceive('pdfPreview')->andReturn(null);
        });
        $this->actingAs($this->editor)->get("/documents/{$doc->id}/preview")
            ->assertOk()->assertSee('nécessite le convertisseur LibreOffice');
        $this->actingAs($this->editor)->get("/documents/{$doc->id}/preview/pdf")->assertStatus(503);

        // Avec LibreOffice : le PDF en cache est servi
        $cached = 'previews/test.pdf';
        $doc->disk()->put($cached, '%PDF-1.4 apercu');
        $this->mock(DocumentConverter::class, function ($mock) use ($cached) {
            $mock->shouldReceive('canConvertToPdf')->andReturn(true);
            $mock->shouldReceive('pdfPreview')->andReturn($cached);
        });
        $this->actingAs($this->editor)->get("/documents/{$doc->id}/preview")
            ->assertOk()->assertSee(route('documents.preview.pdf', $doc), false);
        $this->actingAs($this->editor)->get("/documents/{$doc->id}/preview/pdf")
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_cached_previews_are_deleted_with_the_document(): void
    {
        $this->import('vente.pptx', $this->zipBytes(['ppt/slides/slide1.xml' => '<a:t>Slide</a:t>']));
        $doc = Document::firstOrFail();
        $preview = app(DocumentConverter::class)->previewPath($doc);
        $doc->disk()->put($preview, '%PDF');

        app(DocumentArchivalService::class)->deleteStoredFiles($doc);

        $this->assertFalse($doc->disk()->exists($preview));
        $this->assertFalse($doc->disk()->exists($doc->file_path));
    }

    public function test_indexed_text_is_bounded(): void
    {
        config(['ged.max_indexed_chars' => 100]);
        $this->import('long.txt', str_repeat('mot ', 1000));

        $this->assertSame(100, mb_strlen(Document::firstOrFail()->content_text));
    }
}
