<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Chaînage du journal d'audit, entreprise par entreprise.
 *
 * Chaque ligne reçoit un numéro d'ordre (sequence), l'empreinte de la ligne précédente (previous_hash)
 * et sa propre empreinte (hash), calculée sur son contenu + previous_hash. La tête de chaîne (dernier
 * numéro et dernière empreinte) est aussi recopiée sur l'entreprise : supprimer les dernières lignes se voit.
 *
 * Le scellement se fait juste après l'insertion, sous verrou de l'entreprise, dans l'ordre des identifiants :
 * deux écritures simultanées ne peuvent pas créer de bifurcation.
 */
class AuditChain
{
    // Champs couverts par l'empreinte (document_id et user_id en sont exclus : ils passent à NULL à la purge / suppression de compte)
    private const FIELDS = ['organization_id', 'sequence', 'document_reference', 'document_title', 'user_name',
        'action', 'description', 'old_values', 'new_values', 'ip_address', 'user_agent', 'created_at', 'previous_hash'];

    public static function seal(?int $organizationId): int
    {
        return DB::transaction(function () use ($organizationId) {
            if ($organizationId) {
                DB::table('organizations')->where('id', $organizationId)->lockForUpdate()->first();
            }

            $last = self::rows($organizationId)->whereNotNull('sequence')->orderByDesc('sequence')->first(['sequence', 'hash']);
            $sequence = (int) ($last->sequence ?? 0);
            $previous = $last->hash ?? null;
            $sealed = 0;

            foreach (self::rows($organizationId)->whereNull('sequence')->orderBy('id')->get() as $row) {
                $row->sequence      = ++$sequence;
                $row->previous_hash = $previous;
                $previous           = self::hash($row);

                DB::table('document_audit_logs')->where('id', $row->id)
                    ->update(['sequence' => $row->sequence, 'previous_hash' => $row->previous_hash, 'hash' => $previous]);
                $sealed++;
            }

            if ($sealed && $organizationId) {
                DB::table('organizations')->where('id', $organizationId)
                    ->update(['audit_head_sequence' => $sequence, 'audit_head_hash' => $previous]);
            }

            return $sealed;
        });
    }

    /**
     * Vérifie la chaîne d'une entreprise. Retourne la liste des anomalies (vide si intacte).
     * @return array<int, string>
     */
    public static function verify(?int $organizationId): array
    {
        $problems = [];
        $expectedSequence = 1;
        $previous = null;

        foreach (self::rows($organizationId)->whereNotNull('sequence')->orderBy('sequence')->cursor() as $row) {
            if ((int) $row->sequence !== $expectedSequence) {
                $problems[] = "Ligne(s) manquante(s) avant le n°{$row->sequence} (attendu n°{$expectedSequence}).";
            }
            if ($row->previous_hash !== $previous) {
                $problems[] = "N°{$row->sequence} : le lien avec la ligne précédente est rompu.";
            }
            if (!hash_equals((string) $row->hash, self::hash($row))) {
                $problems[] = "N°{$row->sequence} (« {$row->action} », {$row->created_at}) : contenu modifié après coup.";
            }
            $expectedSequence = (int) $row->sequence + 1;
            $previous = $row->hash;
        }

        if ($organizationId) {
            $head = DB::table('organizations')->where('id', $organizationId)->first(['audit_head_sequence', 'audit_head_hash']);
            if ($head && $head->audit_head_sequence && ((int) $head->audit_head_sequence !== $expectedSequence - 1 || $head->audit_head_hash !== $previous)) {
                $problems[] = "Fin de journal : la dernière ligne connue (n°{$head->audit_head_sequence}) ne correspond plus — des lignes récentes ont été supprimées ou ajoutées.";
            }
        }

        return $problems;
    }

    private static function rows(?int $organizationId)
    {
        return DB::table('document_audit_logs')->when(
            $organizationId,
            fn ($q) => $q->where('organization_id', $organizationId),
            fn ($q) => $q->whereNull('organization_id')
        );
    }

    private static function hash(object $row): string
    {
        $data = [];
        foreach (self::FIELDS as $field) {
            $value = $row->{$field} ?? null;
            $data[$field] = match ($field) {
                'old_values', 'new_values' => self::canonicalJson($value),
                'created_at'               => $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : null,
                'organization_id', 'sequence' => $value === null ? null : (int) $value,
                default                    => $value === null ? null : (string) $value,
            };
        }

        return hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    // JSON avec clés triées : MySQL réordonne les clés des colonnes JSON
    private static function canonicalJson($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $decoded = is_string($value) ? json_decode($value, true) : $value;
        $sort = function ($v) use (&$sort) {
            if (!is_array($v)) {
                return $v;
            }
            if (!array_is_list($v)) {
                ksort($v);
            }
            return array_map($sort, $v);
        };

        return json_encode($sort($decoded), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
