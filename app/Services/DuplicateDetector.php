<?php

namespace App\Services;

use App\Models\Document;
use App\Support\ContentFingerprint;
use Illuminate\Database\Eloquent\Builder;

/**
 * Recherche, dans toute l'entreprise, un document existant identique à un fichier importé :
 *  1. même fichier (empreinte SHA-256 d'une version)                     → bloquant
 *  2. même texte (après normalisation)                                    → bloquant
 *  3. texte quasi identique (rescan avec erreurs d'OCR…) et mêmes nombres → bloquant
 *  4. texte très proche (même modèle, quelques valeurs différentes)       → simple avertissement
 *
 * Le résultat ne dit rien de plus que ce que l'utilisateur a le droit de voir :
 * un document confidentiel est signalé sans titre ni lien.
 */
class DuplicateDetector
{
    // Écart maximal (bits sur 64) entre deux simhash pour comparer finement les textes
    private const SIMHASH_RADIUS = 22;
    private const MAX_CANDIDATES = 15;

    /**
     * @return array{document: Document, level: string, score: float, blocking: bool}|null
     */
    public function find(int $organizationId, ?string $checksum, string $text): ?array
    {
        if ($checksum && ($document = $this->documents($organizationId)
                ->where(fn ($q) => $q->where('checksum', $checksum)
                    ->orWhereHas('versions', fn ($v) => $v->where('checksum', $checksum)))
                ->first())) {
            return $this->match($document, 'file', 1.0);
        }

        $tokens = ContentFingerprint::tokens($text);
        if (!ContentFingerprint::isComparable($tokens)) {
            return null;
        }

        if ($document = $this->documents($organizationId)->where('content_hash', ContentFingerprint::hash($tokens))->first()) {
            return $this->match($document, 'content', 1.0);
        }

        return $this->nearDuplicate($organizationId, $tokens);
    }

    private function nearDuplicate(int $organizationId, array $tokens): ?array
    {
        $simhash = ContentFingerprint::simhash($tokens);

        // Préfiltre : documents dont l'empreinte de similarité est proche
        $candidates = [];
        foreach ($this->documents($organizationId)->whereNotNull('content_simhash')->pluck('content_simhash', 'id') as $id => $other) {
            $distance = ContentFingerprint::hammingDistance($simhash, (int) $other);
            if ($distance <= self::SIMHASH_RADIUS) {
                $candidates[$id] = $distance;
            }
        }
        if ($candidates === []) {
            return null;
        }
        asort($candidates);
        $ids = array_slice(array_keys($candidates), 0, self::MAX_CANDIDATES);

        // Comparaison fine du texte
        $best = null;
        foreach ($this->documents($organizationId)->whereIn('id', $ids)->get() as $document) {
            $other = ContentFingerprint::tokens((string) $document->content_text);
            $score = ContentFingerprint::similarity($tokens, $other);
            if ($score < config('ged.duplicates.warn_threshold', 0.60) || ($best && $score <= $best['score'])) {
                continue;
            }

            $same = $score >= config('ged.duplicates.block_threshold', 0.75)
                && ContentFingerprint::numbersSimilarity($tokens, $other) >= config('ged.duplicates.numbers_threshold', 0.80);
            $best  = $this->match($document, $same ? 'same' : 'similar', $score);
        }

        return $best;
    }

    // Documents de l'entreprise (hors corbeille), y compris ceux que l'utilisateur ne voit pas
    private function documents(int $organizationId): Builder
    {
        return Document::withoutGlobalScope('organization')->where('organization_id', $organizationId);
    }

    private function match(Document $document, string $level, float $score): array
    {
        return [
            'document' => $document,
            'level'    => $level,
            'score'    => round($score, 2),
            'blocking' => $level !== 'similar',
        ];
    }

    /**
     * Réponse destinée à l'utilisateur (message + infos du document existant s'il peut le voir).
     */
    public function describe(array $match): array
    {
        $document = $match['document'];
        $visible  = $document->canView();
        $percent  = (int) round($match['score'] * 100);
        $name     = $visible ? "« {$document->title} » ({$document->reference})" : 'un document confidentiel de votre entreprise';

        $message = match ($match['level']) {
            'file'    => "Ce document existe déjà : {$name}. C'est exactement le même fichier.",
            'content' => "Ce document existe déjà : {$name}. Le contenu est identique, même si le fichier est différent.",
            'same'    => "Ce document existe déjà : {$name}. Le contenu correspond à {$percent} % (rescan ou autre format du même document).",
            default   => "Un document très proche existe déjà : {$name} ({$percent} % de contenu commun). Vérifiez qu'il ne s'agit pas du même avant d'importer.",
        };
        if (!$visible) {
            $message .= ' Rapprochez-vous de votre administrateur.';
        }

        return [
            'message'   => $message,
            'duplicate' => [
                'id'        => $visible ? $document->id : null,
                'title'     => $visible ? $document->title : null,
                'reference' => $visible ? $document->reference : null,
                'url'       => $visible ? route('documents.show', $document) : null,
                'level'     => $match['level'],
                'score'     => $match['score'],
                'blocking'  => $match['blocking'],
            ],
        ];
    }
}
