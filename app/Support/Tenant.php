<?php

namespace App\Support;

use App\Models\Organization;
use Illuminate\Validation\Rules\Exists;

/**
 * Entreprise courante (tenant) pour la requête en cours.
 *
 * Définie par le middleware SetTenant après l'authentification.
 * Quand elle est définie, les modèles utilisant BelongsToOrganization
 * sont automatiquement filtrés sur cette entreprise.
 * Hors contexte (console, pages publiques, espace super admin) : aucun filtre.
 */
class Tenant
{
    private static ?int $id = null;
    private static ?Organization $organization = null;

    public static function set(?Organization $organization): void
    {
        static::$organization = $organization;
        static::$id = $organization?->id;
    }

    public static function clear(): void
    {
        static::set(null);
    }

    public static function id(): ?int
    {
        return static::$id;
    }

    public static function organization(): ?Organization
    {
        return static::$organization;
    }

    public static function check(): bool
    {
        return static::$id !== null;
    }

    /**
     * Exécute $callback dans le contexte d'une entreprise (tâches planifiées, jobs…).
     */
    public static function run(?Organization $organization, callable $callback): mixed
    {
        $previous = static::$organization;
        static::set($organization);

        try {
            return $callback();
        } finally {
            static::set($previous);
        }
    }

    /**
     * Règle de validation "exists" limitée à l'entreprise courante.
     * Les règles exists:table,id passent par le query builder et ignorent les scopes Eloquent.
     */
    public static function exists(string $table, string $column = 'id'): Exists
    {
        $rule = new Exists($table, $column);

        return static::check() ? $rule->where('organization_id', static::$id) : $rule;
    }
}
