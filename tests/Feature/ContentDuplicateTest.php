<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Services\DocumentConverter;
use App\Support\ContentFingerprint;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

/**
 * Doublons détectés sur le contenu des documents (pas seulement le fichier ou le titre).
 */
class ContentDuplicateTest extends TestCase
{
    use RefreshDatabase;

    private const CONTRACT = "CONTRAT DE PRESTATION DE SERVICES. Entre les soussignés : la société Sotelma SA, au capital de 50 000 000 FCFA, "
        . "dont le siège est à Bamako, représentée par son directeur général, ci-après dénommée le Client, et la société Imperis Group, "
        . "ci-après dénommée le Prestataire. Article 1 : Objet. Le présent contrat a pour objet la mise en place d'une solution de gestion "
        . "électronique de documents, la formation des utilisateurs et l'assistance technique pendant toute la durée du contrat. "
        . "Article 2 : Durée. Le contrat est conclu pour une durée de 24 mois à compter de sa signature, renouvelable par tacite reconduction. "
        . "Article 3 : Prix. Le montant total de la prestation s'élève à 12 500 000 FCFA hors taxes, payable en trois échéances. "
        . "Article 4 : Confidentialité. Chaque partie s'engage à ne pas divulguer les informations reçues de l'autre partie. "
        . "Fait à Bamako, le 15 mars 2026, en deux exemplaires originaux.";

    private Organization $org;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');

        $this->org = $this->organization('Test SA');
        foreach (['admin', 'editor', 'viewer'] as $name) {
            Role::create(['name' => $name, 'display_name' => ucfirst($name)]);
        }
    }

    protected function tearDown(): void
    {
        Tenant::clear();
        parent::tearDown();
    }

    private function organization(string $name): Organization
    {
        $org = Organization::create(['name' => $name, 'reference_prefix' => strtoupper(substr($name, 0, 3))]);
        Subscription::create([
            'organization_id' => $org->id,
            'plan_id'         => Plan::where('slug', 'entreprise')->value('id'),
            'starts_at'       => today(),
            'ends_at'         => today()->addYear(),
        ]);
        return $org;
    }

    private function user(string $role = 'editor', ?Organization $org = null): User
    {
        $user = User::factory()->create(['organization_id' => ($org ?? $this->org)->id]);
        $user->roles()->attach(Role::where('name', $role)->first());
        return $user;
    }

    private function import(User $user, UploadedFile $file, array $extra = [])
    {
        return $this->actingAs($user)->postJson('/documents/create', array_merge([
            'title'       => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'import_mode' => 1,
            'import_file' => $file,
        ], $extra));
    }

    private function txt(string $name, string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $content);
    }

    private function docx(string $name, string $content): UploadedFile
    {
        $word = new PhpWord();
        $section = $word->addSection();
        foreach (preg_split('/(?<=\.) /', $content) as $paragraph) {
            $section->addText($paragraph);
        }
        $path = tempnam(sys_get_temp_dir(), 'docx_');
        IOFactory::createWriter($word, 'Word2007')->save($path);

        return UploadedFile::fake()->createWithContent($name, file_get_contents($path));
    }

    // Texte relu par OCR : quelques lettres mal reconnues, accents perdus, coupures de lignes
    private function ocrNoise(string $text): string
    {
        $words = explode(' ', $text);
        foreach ($words as $i => $word) {
            if ($i % 17 === 5) {
                $words[$i] = strtr($word, ['e' => 'c', 'o' => '0', 'l' => '1']);
            }
        }

        return wordwrap(\Illuminate\Support\Str::ascii(implode(' ', $words)), 60, "\n");
    }

    public function test_same_content_in_another_format_is_detected(): void
    {
        $editor = $this->user();
        $this->import($editor, $this->docx('contrat-sotelma.docx', self::CONTRACT))->assertCreated();
        $original = Document::firstOrFail();
        $this->assertNotNull($original->content_hash);

        // Même texte, autre fichier, autre nom, autre titre
        $this->import($editor, $this->txt('scan-2026.txt', self::CONTRACT), ['title' => 'Document reçu'])
            ->assertStatus(409)
            ->assertJsonPath('duplicate.id', $original->id)
            ->assertJsonPath('duplicate.level', 'content')
            ->assertJsonPath('duplicate.blocking', true);

        $this->assertSame(1, Document::count());
    }

    public function test_a_rescanned_document_with_ocr_errors_is_detected(): void
    {
        $editor = $this->user();
        $this->import($editor, $this->txt('contrat.txt', self::CONTRACT))->assertCreated();

        $response = $this->import($editor, $this->txt('rescan.txt', $this->ocrNoise(self::CONTRACT)))
            ->assertStatus(409)
            ->assertJsonPath('duplicate.level', 'same')
            ->assertJsonPath('duplicate.blocking', true);

        $this->assertGreaterThanOrEqual(0.75, $response->json('duplicate.score'));
        $this->assertStringContainsString('existe déjà', $response->json('message'));
    }

    public function test_same_template_with_other_amounts_is_only_a_warning(): void
    {
        $editor = $this->user();
        $this->import($editor, $this->txt('contrat.txt', self::CONTRACT))->assertCreated();

        // Même modèle de contrat, mais autre client, autres montants et dates
        $other = strtr(self::CONTRACT, [
            '50 000 000' => '20 000 000', '24 mois' => '36 mois', '12 500 000' => '8 750 000',
            '15 mars 2026' => '2 juin 2026', 'Sotelma SA' => 'Orange Mali SA', 'trois' => 'quatre',
        ]);
        $this->import($editor, $this->txt('contrat-orange.txt', $other))
            ->assertStatus(409)
            ->assertJsonPath('duplicate.level', 'similar')
            ->assertJsonPath('duplicate.blocking', false);

        // Simple ressemblance : l'utilisateur peut confirmer
        $this->import($editor, $this->txt('contrat-orange.txt', $other), ['allow_duplicate' => 1])->assertCreated();
        $this->assertSame(2, Document::count());
    }

    public function test_unrelated_documents_and_short_texts_are_not_flagged(): void
    {
        $editor = $this->user();
        $this->import($editor, $this->txt('contrat.txt', self::CONTRACT))->assertCreated();

        $minutes = "PROCÈS-VERBAL DE LA RÉUNION DU COMITÉ DE DIRECTION. Étaient présents le directeur financier, la responsable des "
            . "ressources humaines et le chef du service informatique. Ordre du jour : budget du second semestre, recrutement de deux "
            . "techniciens, renouvellement du parc informatique. Le comité approuve le budget présenté et charge la direction des "
            . "ressources humaines de publier les offres d'emploi avant la fin du mois. La séance est levée à midi.";
        $this->import($editor, $this->txt('pv.txt', $minutes))->assertCreated();

        // Textes trop courts pour une comparaison fiable : seul le fichier identique compte
        $this->import($editor, $this->txt('note-a.txt', 'Réunion lundi 10h'))->assertCreated();
        $this->import($editor, $this->txt('note-b.txt', 'Réunion lundi 10h.'))->assertCreated();

        $this->assertSame(4, Document::count());
    }

    public function test_documents_of_another_organization_are_never_compared(): void
    {
        $this->import($this->user(), $this->txt('contrat.txt', self::CONTRACT))->assertCreated();

        $other = $this->organization('Autre SARL');
        $this->import($this->user('editor', $other), $this->txt('contrat.txt', self::CONTRACT))->assertCreated();

        $this->assertSame(2, Document::withoutGlobalScopes()->count());
    }

    public function test_a_scanned_pdf_is_read_by_ocr(): void
    {
        // PDF sans couche texte (scan) : le texte vient de l'OCR des pages
        $this->mock(DocumentConverter::class, function ($mock) {
            $mock->shouldReceive('pdfToText')->andReturn("\f");
            $mock->shouldReceive('ocrPdf')->andReturn($this->ocrNoise(self::CONTRACT));
        });

        $editor = $this->user();
        $this->import($editor, $this->txt('contrat.txt', self::CONTRACT))->assertCreated();

        $this->import($editor, UploadedFile::fake()->createWithContent('scan.pdf', "%PDF-1.4\n%%EOF\n"))
            ->assertStatus(409)
            ->assertJsonPath('duplicate.blocking', true);
    }

    public function test_fingerprint_tolerates_ocr_noise_but_separates_other_texts(): void
    {
        $original = ContentFingerprint::tokens(self::CONTRACT);
        $rescan   = ContentFingerprint::tokens($this->ocrNoise(self::CONTRACT));
        $other    = ContentFingerprint::tokens(str_repeat('Rapport annuel des activités du service comptable et financier. ', 6));

        $this->assertSame(ContentFingerprint::hash($original), ContentFingerprint::hash(ContentFingerprint::tokens(mb_strtoupper(self::CONTRACT))));
        $this->assertLessThan(
            ContentFingerprint::hammingDistance(ContentFingerprint::simhash($original), ContentFingerprint::simhash($other)),
            ContentFingerprint::hammingDistance(ContentFingerprint::simhash($original), ContentFingerprint::simhash($rescan))
        );
        $this->assertGreaterThan(0.75, ContentFingerprint::similarity($original, $rescan));
        $this->assertSame(1.0, ContentFingerprint::numbersSimilarity($original, $rescan));
        $this->assertLessThan(0.2, ContentFingerprint::similarity($original, $other));
    }
}
