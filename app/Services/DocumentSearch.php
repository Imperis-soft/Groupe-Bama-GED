<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

/**
 * Recherche plein texte des documents, adaptée au moteur de base de données :
 * - PostgreSQL : index GIN to_tsvector('french') + websearch_to_tsquery, classement ts_rank_cd ;
 * - MySQL      : index FULLTEXT (title, reference, content_text) en mode booléen avec préfixes ;
 * - autres     : LIKE mot par mot (SQLite des tests, MySQL sans index).
 *
 * Tous les mots doivent être présents (ET). Une expression entre guillemets est cherchée telle quelle.
 * Le titre et la référence pèsent plus que le contenu dans le score de pertinence.
 * Fournit aussi les extraits surlignés (sans HTML : segments texte / mot trouvé).
 */
class DocumentSearch
{
    public const MAX_TERMS = 10;

    // Longueur du contenu lu pour construire l'extrait (caractères)
    public const EXCERPT_SOURCE_CHARS = 100000;

    // Correspondance 1 caractère → 1 caractère pour comparer sans accents ni casse
    private const FOLD = [
        'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'å' => 'a', 'æ' => 'a',
        'ç' => 'c', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'î' => 'i', 'ï' => 'i', 'í' => 'i', 'ì' => 'i', 'ñ' => 'n',
        'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'œ' => 'o', 'ø' => 'o',
        'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u', 'ÿ' => 'y', 'ý' => 'y',
        '’' => "'", '‘' => "'",
    ];

    private static ?bool $mysqlFulltext = null;
    private static ?array $listColumns = null;

    /**
     * Découpe la saisie en termes : mots isolés et "expressions entre guillemets".
     * Les termes d'un seul caractère sont ignorés.
     */
    public static function terms(?string $query): array
    {
        $query = trim((string) $query);
        if ($query === '') {
            return [];
        }

        preg_match_all('/"([^"]+)"|(\S+)/u', $query, $matches, PREG_SET_ORDER);
        $terms = [];
        foreach ($matches as $match) {
            $term = trim($match[1] !== '' ? $match[1] : $match[2], " \t\n\r\0\x0B\"'.,;:!?()[]{}");
            if (mb_strlen($term) >= 2 && !in_array(mb_strtolower($term), array_map('mb_strtolower', $terms), true)) {
                $terms[] = $term;
            }
        }

        return array_slice($terms, 0, self::MAX_TERMS);
    }

    /**
     * Filtre la requête sur les termes et ajoute la colonne de pertinence « search_score ».
     * Le contenu complet n'est pas chargé : seul son début (search_excerpt_source) sert à l'extrait.
     */
    public function apply(Builder $query, ?string $text, bool $withScore = true): Builder
    {
        $terms = self::terms($text);
        if (!$terms) {
            return $query;
        }

        $driver = $query->getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            $this->applyPostgres($query, $text, $terms, $withScore);
        } elseif (in_array($driver, ['mysql', 'mariadb'], true) && $this->hasMysqlFulltext()) {
            $this->applyMysql($query, $terms, $withScore);
        } else {
            $this->applyLike($query, $terms, $withScore, $driver === 'pgsql' ? 'ILIKE' : 'LIKE');
        }

        return $query;
    }

    // Colonnes des documents sans le texte intégral (lourd), plus le début du contenu pour l'extrait
    public function selectListColumns(Builder $query): Builder
    {
        self::$listColumns ??= array_values(array_diff(Schema::getColumnListing('documents'), ['content_text', 'search_vector']));

        return $query
            ->select(array_map(fn ($c) => "documents.{$c}", self::$listColumns))
            ->selectRaw('SUBSTR(documents.content_text, 1, ' . self::EXCERPT_SOURCE_CHARS . ') as search_excerpt_source');
    }

    // --- Moteurs ---------------------------------------------------------

    private function applyPostgres(Builder $query, string $text, array $terms, bool $withScore): void
    {
        // Même expression que l'index GIN documents_search_vector_gin (sinon l'index n'est pas utilisé)
        $ts = "to_tsvector('french', coalesce(title,'') || ' ' || coalesce(reference,'') || ' ' || coalesce(content_text,'') || ' ' || coalesce((metadata->>'original_name'),''))";
        $tsQuery = "websearch_to_tsquery('french', ?)";
        $search = $this->websearchText($terms);

        $query->where(function ($q) use ($ts, $tsQuery, $search, $terms) {
            $q->whereRaw("{$ts} @@ {$tsQuery}", [$search])
              // Références et fragments de mots (ex. « TST-4K », « fact ») : tous les termes dans le titre ou la référence
              ->orWhere(function ($w) use ($terms) {
                  foreach ($terms as $term) {
                      $like = '%' . self::escapeLike($term) . '%';
                      $w->where(fn ($t) => $t->whereRaw("documents.title ILIKE ? ESCAPE '!'", [$like])
                                             ->orWhereRaw("documents.reference ILIKE ? ESCAPE '!'", [$like]));
                  }
              });
        });

        if ($withScore) {
            [$boostSql, $boostBindings] = $this->boostSql($terms, 'ILIKE');
            $query->selectRaw("(ts_rank_cd({$ts}, {$tsQuery}) * 10 + {$boostSql}) as search_score", array_merge([$search], $boostBindings));
        }
    }

    private function applyMysql(Builder $query, array $terms, bool $withScore): void
    {
        $match = 'MATCH(documents.title, documents.reference, documents.content_text)';

        foreach ($terms as $term) {
            $boolean = $this->mysqlBoolean($term);
            $like = '%' . self::escapeLike($term) . '%';
            $query->where(function ($q) use ($match, $boolean, $like) {
                if ($boolean !== null) {
                    $q->whereRaw("{$match} AGAINST (? IN BOOLEAN MODE)", [$boolean]);
                } else {
                    // Mots trop courts pour l'index FULLTEXT (moins de 3 lettres)
                    $q->whereRaw("documents.content_text LIKE ? ESCAPE '!'", [$like]);
                }
                $q->orWhereRaw("documents.title LIKE ? ESCAPE '!'", [$like])
                  ->orWhereRaw("documents.reference LIKE ? ESCAPE '!'", [$like]);
            });
        }

        if ($withScore) {
            [$boostSql, $boostBindings] = $this->boostSql($terms, 'LIKE');
            $query->selectRaw("({$match} AGAINST (?) + {$boostSql}) as search_score", array_merge([implode(' ', $terms)], $boostBindings));
        }
    }

    private function applyLike(Builder $query, array $terms, bool $withScore, string $like): void
    {
        foreach ($terms as $term) {
            $pattern = '%' . self::escapeLike($term) . '%';
            $query->where(function ($q) use ($pattern, $like) {
                $q->whereRaw("documents.title {$like} ? ESCAPE '!'", [$pattern])
                  ->orWhereRaw("documents.reference {$like} ? ESCAPE '!'", [$pattern])
                  ->orWhereRaw("documents.content_text {$like} ? ESCAPE '!'", [$pattern]);
            });
        }

        if ($withScore) {
            [$boostSql, $boostBindings] = $this->boostSql($terms, $like);
            $contentSql = [];
            $contentBindings = [];
            foreach ($terms as $term) {
                $contentSql[] = "(CASE WHEN documents.content_text {$like} ? ESCAPE '!' THEN 1 ELSE 0 END)";
                $contentBindings[] = '%' . self::escapeLike($term) . '%';
            }
            $query->selectRaw('(' . implode(' + ', $contentSql) . " + {$boostSql}) as search_score", array_merge($contentBindings, $boostBindings));
        }
    }

    // Bonus de pertinence : terme dans le titre (3), dans la référence (2), titre commençant par la saisie (2)
    private function boostSql(array $terms, string $like): array
    {
        $parts = [];
        $bindings = [];
        foreach ($terms as $term) {
            $pattern = '%' . self::escapeLike($term) . '%';
            $parts[] = "(CASE WHEN documents.title {$like} ? ESCAPE '!' THEN 3 ELSE 0 END)";
            $parts[] = "(CASE WHEN documents.reference {$like} ? ESCAPE '!' THEN 2 ELSE 0 END)";
            array_push($bindings, $pattern, $pattern);
        }
        $parts[] = "(CASE WHEN documents.title {$like} ? ESCAPE '!' THEN 2 ELSE 0 END)";
        $bindings[] = self::escapeLike(implode(' ', $terms)) . '%';

        return ['(' . implode(' + ', $parts) . ')', $bindings];
    }

    // Saisie pour websearch_to_tsquery : expressions remises entre guillemets
    private function websearchText(array $terms): string
    {
        return implode(' ', array_map(fn ($t) => str_contains($t, ' ') ? '"' . str_replace('"', '', $t) . '"' : $t, $terms));
    }

    // Expression MySQL en mode booléen pour un terme (null si aucun mot indexable)
    private function mysqlBoolean(string $term): ?string
    {
        $words = array_values(array_filter(
            preg_split('/[^\p{L}\p{N}]+/u', $term, -1, PREG_SPLIT_NO_EMPTY),
            fn ($w) => mb_strlen($w) >= 3
        ));
        if (!$words) {
            return null;
        }

        return str_contains($term, ' ')
            ? '+"' . implode(' ', $words) . '"'
            : implode(' ', array_map(fn ($w) => '+' . $w . '*', $words));
    }

    private function hasMysqlFulltext(): bool
    {
        return self::$mysqlFulltext ??= Schema::hasIndex('documents', 'documents_fulltext_search');
    }

    // Échappement des jokers LIKE avec « ! » (même syntaxe sur MySQL, PostgreSQL et SQLite)
    public static function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }

    // --- Surlignage --------------------------------------------------------

    // Texte en minuscules sans accents, même nombre de caractères que l'original
    private static function fold(string $text): string
    {
        return strtr(mb_strtolower($text), self::FOLD);
    }

    /**
     * Découpe un texte en segments [['text' => …, 'hit' => bool]] autour des termes trouvés.
     */
    public static function highlight(?string $text, array $terms): array
    {
        $text = (string) $text;
        if ($text === '' || !$terms) {
            return $text === '' ? [] : [['text' => $text, 'hit' => false]];
        }

        $folded = self::fold($text);
        $ranges = [];
        foreach ($terms as $term) {
            $needle = self::fold($term);
            $length = mb_strlen($needle);
            $offset = 0;
            while ($length && ($pos = mb_strpos($folded, $needle, $offset)) !== false) {
                $ranges[] = [$pos, $pos + $length];
                $offset = $pos + $length;
            }
        }
        if (!$ranges) {
            return [['text' => $text, 'hit' => false]];
        }

        // Fusion des zones qui se chevauchent
        usort($ranges, fn ($a, $b) => $a[0] <=> $b[0]);
        $merged = [array_shift($ranges)];
        foreach ($ranges as [$start, $end]) {
            $last = &$merged[count($merged) - 1];
            if ($start <= $last[1]) {
                $last[1] = max($last[1], $end);
            } else {
                $merged[] = [$start, $end];
            }
            unset($last);
        }

        $segments = [];
        $cursor = 0;
        foreach ($merged as [$start, $end]) {
            if ($start > $cursor) {
                $segments[] = ['text' => mb_substr($text, $cursor, $start - $cursor), 'hit' => false];
            }
            $segments[] = ['text' => mb_substr($text, $start, $end - $start), 'hit' => true];
            $cursor = $end;
        }
        if ($cursor < mb_strlen($text)) {
            $segments[] = ['text' => mb_substr($text, $cursor), 'hit' => false];
        }

        return $segments;
    }

    /**
     * Extrait du contenu centré sur la première occurrence d'un terme, surligné.
     * Sans occurrence dans le contenu : le début du texte, sans surlignage. Null si pas de contenu.
     */
    public static function snippet(?string $content, array $terms, int $length = 220): ?array
    {
        $content = trim(preg_replace('/\s+/u', ' ', (string) $content) ?? '');
        if ($content === '') {
            return null;
        }

        $folded = self::fold($content);
        $first = null;
        foreach ($terms as $term) {
            $pos = mb_strpos($folded, self::fold($term));
            if ($pos !== false && ($first === null || $pos < $first)) {
                $first = $pos;
            }
        }

        $total = mb_strlen($content);
        $start = $first === null ? 0 : max(0, $first - intdiv($length, 3));
        // Commencer sur un début de mot
        if ($start > 0 && ($space = mb_strpos($content, ' ', $start)) !== false && $space < ($first ?? $start) ) {
            $start = $space + 1;
        }
        $excerpt = mb_substr($content, $start, $length);
        if ($start + $length < $total && ($cut = mb_strrpos($excerpt, ' ')) !== false && $cut > $length * 0.6) {
            $excerpt = mb_substr($excerpt, 0, $cut);
        }

        $segments = $first === null ? [['text' => $excerpt, 'hit' => false]] : self::highlight($excerpt, $terms);
        if ($start > 0) {
            array_unshift($segments, ['text' => '… ', 'hit' => false]);
        }
        if ($start + mb_strlen($excerpt) < $total) {
            $segments[] = ['text' => ' …', 'hit' => false];
        }

        return $segments;
    }
}
