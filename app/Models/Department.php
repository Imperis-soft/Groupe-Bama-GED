<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Service commun à toutes les entreprises (DG, RH, Comptabilité…), géré par le super admin.
 *
 * Propres à chaque entreprise : les membres, les droits sur ses catégories et le responsable.
 * Les relations users() et categories() sont filtrées sur l'entreprise courante par le cloisonnement
 * des modèles User et Category ; pour les modifier, passer par syncMembers() / syncCategoryAccess(),
 * qui ne touchent jamais aux données des autres entreprises.
 */
class Department extends Model
{
    public const ACCESS_LEVELS = ['view' => 'Consultation', 'edit' => 'Modification'];

    protected $fillable = ['name', 'description'];

    /**
     * Mots-clés des services communs : servent à suggérer les catégories de documents
     * qui concernent un service (ex. RH → « Personnel », « Paie », « Contrats de travail »).
     * Clé : slug du nom du service. Mots sans accents, comparés mot à mot.
     */
    public const SUGGESTION_KEYWORDS = [
        'direction-generale'       => ['direction', 'gouvernance', 'conseil', 'strategie', 'pv', 'proces', 'assemblee', 'statuts', 'decision', 'rapport'],
        'secretariat-de-direction' => ['courrier', 'note', 'notes', 'compte', 'rendu', 'agenda', 'correspondance', 'secretariat', 'convocation'],
        'ressources-humaines'      => ['rh', 'personnel', 'paie', 'salaire', 'salaires', 'recrutement', 'conge', 'conges', 'formation', 'employe', 'employes', 'travail', 'carriere', 'cnss', 'inps'],
        'comptabilite'             => ['compta', 'comptabilite', 'comptable', 'facture', 'factures', 'fiscal', 'fiscalite', 'impot', 'impots', 'tva', 'bilan', 'depense', 'depenses', 'recette', 'recettes', 'piece', 'pieces'],
        'finances-et-tresorerie'   => ['finance', 'finances', 'tresorerie', 'banque', 'bancaire', 'budget', 'credit', 'emprunt', 'paiement', 'paiements', 'releve', 'releves'],
        'juridique'                => ['juridique', 'contrat', 'contrats', 'contentieux', 'litige', 'statuts', 'avocat', 'conformite', 'convention', 'conventions', 'acte', 'actes', 'proces'],
        'commercial'               => ['commercial', 'client', 'clients', 'devis', 'offre', 'offres', 'vente', 'ventes', 'proforma', 'commande', 'commandes'],
        'marketing-et-communication' => ['marketing', 'communication', 'presse', 'publicite', 'evenement', 'evenements', 'logo', 'brochure', 'campagne'],
        'achats'                   => ['achat', 'achats', 'fournisseur', 'fournisseurs', 'appel', 'commande', 'commandes', 'bon', 'bons', 'marche', 'marches'],
        'logistique'               => ['logistique', 'stock', 'stocks', 'livraison', 'livraisons', 'transport', 'reception', 'inventaire', 'magasin', 'entrepot'],
        'informatique'             => ['informatique', 'it', 'licence', 'licences', 'materiel', 'logiciel', 'logiciels', 'reseau', 'securite', 'systeme', 'procedure'],
        'audit-et-controle-interne' => ['audit', 'audits', 'controle', 'procedure', 'procedures', 'risque', 'risques', 'inspection', 'rapport'],
        'qualite-hygiene-securite-environnement' => ['qualite', 'qhse', 'hse', 'hygiene', 'securite', 'environnement', 'iso', 'norme', 'normes', 'certification', 'incident', 'incidents'],
        'technique-et-exploitation' => ['technique', 'techniques', 'exploitation', 'production', 'maintenance', 'plan', 'plans', 'chantier', 'chantiers', 'equipement', 'equipements'],
        'service-client'           => ['client', 'clients', 'reclamation', 'reclamations', 'sav', 'support', 'demande', 'demandes'],
        'courrier-et-archives'     => ['courrier', 'archive', 'archives', 'arrivee', 'depart', 'registre', 'classement'],
    ];

    /** Catégories (parmi celles données) qui semblent concerner ce service */
    public function suggestedCategoryIds(iterable $categories): array
    {
        $keywords = self::SUGGESTION_KEYWORDS[\Illuminate\Support\Str::slug($this->name)] ?? [];
        // Service ajouté par le super admin : les mots de son nom servent de mots-clés
        if ($keywords === []) {
            $keywords = array_filter(explode('-', \Illuminate\Support\Str::slug($this->name)), fn ($w) => strlen($w) > 3);
        }
        $keywords = array_flip($keywords);

        $ids = [];
        foreach ($categories as $category) {
            $words = explode('-', \Illuminate\Support\Str::slug($category->name));
            if (array_intersect_key(array_flip($words), $keywords)) {
                $ids[] = $category->id;
            }
        }

        return $ids;
    }

    public function users() { return $this->belongsToMany(User::class); }

    public function categories()
    {
        return $this->belongsToMany(Category::class)->withPivot('access_level');
    }

    // Réglages par entreprise (responsable du service)
    public function organizations()
    {
        return $this->belongsToMany(Organization::class)->withPivot('manager_id')->withTimestamps();
    }

    public function managerFor(int $organizationId): ?User
    {
        $managerId = DB::table('department_organization')
            ->where('department_id', $this->id)->where('organization_id', $organizationId)
            ->value('manager_id');

        return $managerId ? User::withoutGlobalScopes()->find($managerId) : null;
    }

    public function setManager(int $organizationId, ?int $userId): void
    {
        DB::table('department_organization')->updateOrInsert(
            ['department_id' => $this->id, 'organization_id' => $organizationId],
            ['manager_id' => $userId, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    // Membres du service dans une entreprise (les membres des autres entreprises sont conservés)
    public function syncMembers(int $organizationId, array $userIds): void
    {
        $orgUserIds = User::withoutGlobalScopes()->where('organization_id', $organizationId)->pluck('id');
        $wanted = collect($userIds)->map(fn ($id) => (int) $id)->intersect($orgUserIds)->unique();

        DB::table('department_user')->where('department_id', $this->id)
            ->whereIn('user_id', $orgUserIds)->whereNotIn('user_id', $wanted)->delete();
        DB::table('department_user')->insertOrIgnore(
            $wanted->map(fn ($id) => ['department_id' => $this->id, 'user_id' => $id])->values()->all()
        );
    }

    // Droits du service sur les catégories d'une entreprise : [category_id => 'view'|'edit']
    public function syncCategoryAccess(int $organizationId, array $access): void
    {
        $orgCategoryIds = Category::withoutGlobalScopes()->where('organization_id', $organizationId)->pluck('id');

        DB::table('category_department')->where('department_id', $this->id)->whereIn('category_id', $orgCategoryIds)->delete();
        DB::table('category_department')->insert(
            collect($access)
                ->filter(fn ($level, $categoryId) => isset(self::ACCESS_LEVELS[$level]) && $orgCategoryIds->contains((int) $categoryId))
                ->map(fn ($level, $categoryId) => ['department_id' => $this->id, 'category_id' => (int) $categoryId, 'access_level' => $level])
                ->values()->all()
        );
    }

    // Nombre total de membres, toutes entreprises confondues (console super admin)
    public function membersCountAllOrganizations(): int
    {
        return DB::table('department_user')->where('department_id', $this->id)->count();
    }
}
