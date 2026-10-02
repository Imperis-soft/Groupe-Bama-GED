<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Document;
use App\Models\EliminationRecord;
use App\Models\Organization;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Fin de conservation : décisions sur les documents arrivés au terme de leur durée de conservation.
 * - éliminer : uniquement après validation d'un administrateur, avec procès-verbal (PDF) conservé ;
 * - prolonger : la durée de conservation est allongée ;
 * - conserver définitivement.
 * Aucune de ces décisions n'est prise automatiquement.
 */
class RetentionService
{
    public function __construct(private DocumentArchivalService $archival) {}

    /**
     * Élimine les documents (seulement ceux qui le peuvent) et retourne le procès-verbal.
     * Le PDF est produit et enregistré AVANT toute suppression : sans PV, rien n'est détruit.
     */
    public function eliminate(Collection $documents, string $reason, User $approver): EliminationRecord
    {
        $documents = $documents->filter(fn (Document $d) => $d->isEliminable())->values();
        if ($documents->isEmpty()) {
            throw new RuntimeException('Aucun des documents sélectionnés ne peut être éliminé.');
        }
        $organization = Organization::findOrFail($documents->first()->organization_id);
        if ($documents->contains(fn (Document $d) => $d->organization_id !== $organization->id)) {
            throw new RuntimeException('Les documents doivent appartenir à la même entreprise.');
        }

        $record = DB::transaction(function () use ($documents, $reason, $approver, $organization) {
            $year  = now()->year;
            $count = EliminationRecord::withoutGlobalScopes()->where('organization_id', $organization->id)
                ->where('number', 'like', "PV-{$year}-%")->lockForUpdate()->count();

            return EliminationRecord::create([
                'organization_id'  => $organization->id,
                'number'           => sprintf('PV-%d-%04d', $year, $count + 1),
                'approved_by'      => $approver->id,
                'approved_by_name' => $approver->full_name,
                'reason'           => $reason,
                'documents'        => $documents->map(fn (Document $d) => $this->snapshot($d))->all(),
                'documents_count'  => $documents->count(),
            ]);
        });

        $pdf  = $this->renderPdf($record, $organization);
        $path = $organization->eliminationRecordsDir() . $record->number . '.pdf';
        if (!$organization->disk()->put($path, $pdf)) {
            throw new RuntimeException("Le procès-verbal {$record->number} n'a pas pu être enregistré : aucun document n'a été détruit.");
        }
        $record->update(['pdf_path' => $path, 'pdf_checksum' => hash('sha256', $pdf)]);

        foreach ($documents as $document) {
            $this->archival->logAction($document, 'eliminated', "Éliminé en fin de conservation — {$record->number} : {$reason}");
            $this->archival->deleteStoredFiles($document);
            $document->forceDelete();
        }

        return $record;
    }

    public function extend(Collection $documents, int $years, string $reason): int
    {
        foreach ($documents as $document) {
            $old = $document->retention_until?->toDateString();
            $document->update(['retention_years' => (int) $document->retention_years + $years]);
            $this->archival->logAction($document, 'retention_extended', "Conservation prolongée de {$years} an(s) : {$reason}",
                ['retention_until' => $old], ['retention_until' => $document->retention_until?->toDateString()]);
        }

        return $documents->count();
    }

    public function keepPermanently(Collection $documents, string $reason): int
    {
        foreach ($documents as $document) {
            $document->update(['retention_permanent' => true]);
            $this->archival->logAction($document, 'retention_permanent', "Conservation définitive décidée : {$reason}");
        }

        return $documents->count();
    }

    // Recalcule la fin de conservation (après un changement de règle de catégorie, ou pour l'existant)
    public function recompute(iterable $documents): int
    {
        $count = 0;
        foreach ($documents as $document) {
            $until = $document->computeRetentionUntil()?->toDateString();
            if ($until !== $document->retention_until?->toDateString()) {
                $document->retention_until = $until;
                $document->saveQuietly();
                $count++;
            }
        }

        return $count;
    }

    // Documents d'une catégorie et de ses sous-catégories (après modification de sa règle)
    public function recomputeCategory(Category $category): int
    {
        $ids = collect([$category->id])->merge($category->allChildren()->pluck('id'));

        return $this->recompute(Document::withoutGlobalScopes()->withTrashed()->whereIn('category_id', $ids)->cursor());
    }

    private function snapshot(Document $document): array
    {
        $rule = $document->retentionRule();

        return [
            'reference'       => $document->reference,
            'title'           => $document->title,
            'category'        => $document->category?->name,
            'created_at'      => $document->created_at?->toDateString(),
            'archived_at'     => $document->archived_at?->toDateString(),
            'retention_years' => (int) $document->retention_years,
            'trigger'         => Category::RETENTION_TRIGGERS_SHORT[$rule['trigger']] ?? $rule['trigger'],
            'retention_until' => $document->retention_until?->toDateString(),
            'disposition'     => Category::FINAL_DISPOSITIONS[$document->finalDisposition()] ?? $document->finalDisposition(),
            'versions'        => $document->versions()->count(),
            'checksum'        => $document->checksum ?: $document->versions()->latest('version_number')->value('checksum'),
        ];
    }

    private function renderPdf(EliminationRecord $record, Organization $organization): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('pdf.elimination-record', [
            'record'       => $record,
            'organization' => $organization,
            'logo'         => 'data:image/png;base64,' . base64_encode((string) @file_get_contents(public_path('images/logo-ged-96.png'))),
        ])->render());
        $dompdf->setPaper('A4');
        $dompdf->render();

        return $dompdf->output();
    }
}
