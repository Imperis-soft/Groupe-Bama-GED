<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Empreinte du contenu textuel d'un document, pour repérer un même document
 * importé deux fois sous une autre forme (rescan, Word puis PDF, autre nom…).
 *
 * - Texte canonique : minuscules, sans accents ni ponctuation, espaces compactés.
 * - hash    : SHA-256 du texte canonique (contenu strictement identique).
 * - simhash : empreinte 64 bits de similarité ; deux textes proches ont peu de bits différents.
 *             Sert de préfiltre rapide avant la comparaison fine (similarity()).
 */
class ContentFingerprint
{
    // Au-delà, le texte n'apporte plus rien à la comparaison (et coûte en mémoire)
    private const MAX_TOKENS = 60000;

    public static function canonical(string $text): string
    {
        $text = Str::ascii(mb_strtolower($text, 'UTF-8'));
        $text = preg_replace('/[^a-z0-9]+/', ' ', $text) ?? '';

        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }

    /** Mots significatifs : les caractères isolés (bruit d'OCR) sont ignorés, sauf les chiffres */
    public static function tokens(string $text): array
    {
        $tokens = array_filter(
            explode(' ', self::canonical($text)),
            fn ($t) => $t !== '' && (strlen($t) > 1 || ctype_digit($t))
        );

        return array_slice(array_values($tokens), 0, self::MAX_TOKENS);
    }

    // Texte assez long pour être comparé de façon fiable ?
    public static function isComparable(array $tokens): bool
    {
        return count($tokens) >= config('ged.duplicates.min_words', 25);
    }

    public static function hash(array $tokens): string
    {
        return hash('sha256', implode(' ', $tokens));
    }

    /** Paires de mots consécutifs (indexées par leur empreinte crc32) */
    public static function shingles(array $tokens): array
    {
        $set = [];
        for ($i = 0, $n = count($tokens) - 1; $i < $n; $i++) {
            $set[crc32($tokens[$i] . ' ' . $tokens[$i + 1])] = true;
        }
        if ($set === [] && $tokens !== []) {
            $set[crc32($tokens[0])] = true;
        }

        return $set;
    }

    public static function simhash(array $tokens): int
    {
        $weights = array_fill(0, 64, 0);
        foreach (array_keys(self::shingles($tokens)) as $shingle) {
            $h = unpack('J', hash('xxh64', (string) $shingle, true))[1];
            for ($bit = 0; $bit < 64; $bit++) {
                $weights[$bit] += (($h >> $bit) & 1) ? 1 : -1;
            }
        }

        $simhash = 0;
        foreach ($weights as $bit => $weight) {
            if ($weight > 0) {
                $simhash |= 1 << $bit;
            }
        }

        return $simhash;
    }

    public static function hammingDistance(int $a, int $b): int
    {
        $x = $a ^ $b;
        $count = 0;
        for ($bit = 0; $bit < 64; $bit++) {
            $count += ($x >> $bit) & 1;
        }

        return $count;
    }

    /** Similarité des textes (0 à 1) : part des paires de mots en commun (Jaccard) */
    public static function similarity(array $a, array $b): float
    {
        return self::jaccard(self::shingles($a), self::shingles($b));
    }

    /**
     * Similarité des nombres (montants, dates, références) : deux factures du même modèle
     * ont presque le même texte mais pas les mêmes chiffres. 1.0 s'il y a trop peu de nombres.
     */
    public static function numbersSimilarity(array $a, array $b): float
    {
        // Nombres purs uniquement : un mot mal lu par l'OCR (« c0ntrat ») n'en est pas un
        $numbers = fn (array $tokens) => array_fill_keys(array_filter($tokens, 'ctype_digit'), true);
        $na = $numbers($a);
        $nb = $numbers($b);

        return count($na + $nb) < 3 ? 1.0 : self::jaccard($na, $nb);
    }

    private static function jaccard(array $a, array $b): float
    {
        if ($a === [] && $b === []) {
            return 1.0;
        }
        $common = count(array_intersect_key($a, $b));

        return $common / (count($a) + count($b) - $common);
    }
}
