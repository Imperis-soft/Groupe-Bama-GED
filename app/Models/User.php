<?php

namespace App\Models;

use App\Models\Role;
use App\Models\LoginHistory;
use App\Models\Concerns\BelongsToOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens; // <--- Importe ça

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, BelongsToOrganization;
    // Les attributs pouvant être assignés en masse
    protected $fillable = [
        'organization_id',
        'full_name',
        'email',
        'phone',
        'address',
        'password',
        'is_active',
        'absent_from',
        'absent_until',
        'delegate_id',
    ];

    // Valeurs par défaut (identiques à celles de la base)
    protected $attributes = [
        'is_active'      => true,
        'is_super_admin' => false,
    ];

    // Les attributs à cacher pour les tableaux
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Les attributs à caster
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'is_active' => 'boolean',
            'absent_from' => 'date',
            'absent_until' => 'date',
        ];
    }

    // Relation avec les rôles (many to many)
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    // Favoris
    public function favorites()
    {
        return $this->belongsToMany(Document::class, 'document_favorites');
    }

    // Services dont l'utilisateur est membre
    public function departments()
    {
        return $this->belongsToMany(Department::class);
    }

    // Suppléant pendant les absences
    public function delegate()
    {
        return $this->belongsTo(User::class, 'delegate_id');
    }

    // Absent à cette date (aujourd'hui par défaut) ?
    public function isAbsent(?\DateTimeInterface $date = null): bool
    {
        $day = \Illuminate\Support\Carbon::parse($date ?? today())->startOfDay();

        return $this->absent_from && $this->absent_until
            && $day->betweenIncluded($this->absent_from->copy()->startOfDay(), $this->absent_until->copy()->startOfDay());
    }

    /**
     * Personne qui valide réellement à la place de l'utilisateur aujourd'hui :
     * lui-même, ou son suppléant s'il est absent (en suivant la chaîne, sans boucle).
     */
    public function effectiveApprover(): User
    {
        $current = $this;
        $seen = [$this->id => true];
        while ($current->isAbsent() && $current->delegate_id && !isset($seen[$current->delegate_id])) {
            $next = User::withoutGlobalScopes()->where('organization_id', $this->organization_id)->find($current->delegate_id);
            if (!$next || !$next->is_active) {
                break;
            }
            $seen[$next->id] = true;
            $current = $next;
        }

        return $current;
    }

    // Historique de connexions
    public function loginHistories()
    {
        return $this->hasMany(LoginHistory::class)->latest();
    }

    // Vérifie si l'utilisateur a un rôle spécifique
    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function hasRole(string $role): bool
    {
        // Le super admin a les droits d'administrateur dans l'entreprise où il intervient
        if ($role === 'admin' && $this->isSuperAdmin()) {
            return true;
        }

        // Utilise la relation chargée (une seule requête par instance)
        return $this->roles->contains('name', $role);
    }

    // Vérifie si l'utilisateur possède au moins un des rôles
    public function hasAnyRole(array $roles): bool
    {
        if (in_array('admin', $roles, true) && $this->isSuperAdmin()) {
            return true;
        }

        return $this->roles->whereIn('name', $roles)->isNotEmpty();
    }

    // Cache des droits par catégorie (calculé une fois par instance)
    protected ?array $categoryAccessCache = null;

    /**
     * Droits de l'utilisateur sur les catégories de son entreprise, via ses services.
     * - levels : [category_id => 'view'|'edit'] (hérité du parent, catégorie publique = 'view')
     * - restricted : [category_id => true] si la catégorie (ou un parent) a des règles par service
     * Un lecteur n'obtient jamais 'edit' par son service.
     */
    public function categoryAccess(): array
    {
        if ($this->categoryAccessCache !== null) {
            return $this->categoryAccessCache;
        }

        $categories = Category::withoutGlobalScopes()
            ->where('organization_id', $this->organization_id)
            ->get(['id', 'parent_id', 'is_public'])
            ->keyBy('id');

        $rules = DB::table('category_department')
            ->whereIn('category_id', $categories->keys())
            ->get(['category_id', 'department_id', 'access_level']);

        $myDepartments = DB::table('department_user')->where('user_id', $this->id)->pluck('department_id')->flip();
        $rank = ['view' => 1, 'edit' => 2];

        // Niveau accordé directement à la catégorie par les services de l'utilisateur
        $own = [];
        foreach ($rules as $rule) {
            if (!isset($myDepartments[$rule->department_id])) {
                continue;
            }
            $current = $own[$rule->category_id] ?? null;
            if (!$current || $rank[$rule->access_level] > $rank[$current]) {
                $own[$rule->category_id] = $rule->access_level;
            }
        }
        $hasRules = $rules->pluck('category_id')->flip();

        $levels = [];
        $restricted = [];
        $resolve = function (int $id, int $depth = 0) use (&$resolve, &$levels, &$restricted, $categories, $own, $hasRules, $rank) {
            if (array_key_exists($id, $levels)) {
                return;
            }
            $category = $categories->get($id);
            $level = $own[$id] ?? ($category?->is_public ? 'view' : null);
            $isRestricted = isset($hasRules[$id]);

            $parentId = $category?->parent_id;
            if ($parentId && $depth < 50 && $categories->has($parentId)) {
                $resolve($parentId, $depth + 1);
                $parentLevel = $levels[$parentId];
                if ($parentLevel && (!$level || $rank[$parentLevel] > $rank[$level])) {
                    $level = $parentLevel;
                }
                $isRestricted = $isRestricted || isset($restricted[$parentId]);
            }

            $levels[$id] = $level;
            if ($isRestricted) {
                $restricted[$id] = true;
            }
        };
        foreach ($categories->keys() as $id) {
            $resolve($id);
        }

        $canEdit = $this->hasAnyRole(['admin', 'editor']);
        $levels = array_filter(array_map(
            fn ($level) => ($level === 'edit' && !$canEdit) ? 'view' : $level,
            $levels
        ));

        return $this->categoryAccessCache = ['levels' => $levels, 'restricted' => $restricted];
    }

    public function forgetCategoryAccess(): void
    {
        $this->categoryAccessCache = null;
    }

    // Niveau d'accès sur une catégorie via les services : 'view', 'edit' ou null
    public function categoryAccessLevel(?int $categoryId): ?string
    {
        return $categoryId ? ($this->categoryAccess()['levels'][$categoryId] ?? null) : null;
    }

    // Catégories consultables via les services (ou publiques)
    public function viewableCategoryIds(): array
    {
        return array_keys($this->categoryAccess()['levels']);
    }

    // Peut-on ranger un document dans cette catégorie ?
    // Une catégorie sans règle par service reste ouverte ; sinon il faut le droit de modification.
    public function canFileInCategory(?int $categoryId): bool
    {
        if (!$categoryId || $this->hasRole('admin')) {
            return true;
        }
        $access = $this->categoryAccess();

        return !isset($access['restricted'][$categoryId]) || ($access['levels'][$categoryId] ?? null) === 'edit';
    }

    // Créer un dossier : à la racine réservé à l'admin (plan de classement),
    // dans un dossier existant pour un éditeur qui peut y ranger des documents.
    // Le sous-dossier hérite des droits par service et de la règle de conservation du parent.
    public function canCreateFolderIn(?int $parentId): bool
    {
        if ($this->hasRole('admin')) {
            return true;
        }

        return $parentId && $this->hasRole('editor') && $this->canFileInCategory($parentId);
    }

    // Renommer / supprimer un dossier : l'admin, ou l'éditeur qui l'a créé et peut toujours y ranger
    public function canManageFolder(Category $folder): bool
    {
        if ($this->hasRole('admin')) {
            return true;
        }

        return (int) $folder->created_by === (int) $this->id && $this->hasRole('editor') && $this->canFileInCategory($folder->id);
    }
}
