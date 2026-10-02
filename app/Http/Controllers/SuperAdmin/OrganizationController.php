<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlatformActivityLog;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Services\DocumentArchivalService;
use App\Services\OrganizationStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use RuntimeException;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        $query = Organization::withCount([
            'users',
            'documents' => fn ($q) => $q->withoutGlobalScopes(),
        ])->orderBy('name');

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $currentOrgIds = Subscription::where('status', '!=', 'cancelled')
            ->whereDate('starts_at', '<=', today())
            ->whereDate('ends_at', '>=', today())
            ->pluck('organization_id');

        match ($request->input('status')) {
            'active'    => $query->where('status', 'active')->whereIn('id', $currentOrgIds),
            'suspended' => $query->where('status', 'suspended'),
            'expired'   => $query->where('status', 'active')->whereNotIn('id', $currentOrgIds),
            default     => null,
        };

        $organizations = $query->paginate(20)->withQueryString();

        return view('super.organizations.index', compact('organizations'));
    }

    public function create()
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();
        return view('super.organizations.create', compact('plans'));
    }

    /**
     * Règles de validation du stockage MinIO.
     * Par défaut (bucket vide) : un dossier propre à l'entreprise dans le bucket partagé de la plateforme.
     * Bucket et serveur dédiés : options facultatives.
     */
    private function storageRules(Request $request, ?Organization $organization = null): array
    {
        $dedicated = $request->boolean('storage_dedicated');

        $bucketRules = [
            $dedicated ? 'required' : 'nullable',
            'string', 'min:3', 'max:63', 'regex:' . OrganizationStorageService::BUCKET_REGEX,
            Rule::unique('organizations', 'storage_bucket')->ignore($organization?->id),
        ];
        // Sur le serveur de la plateforme, le bucket partagé est réservé
        if (!$dedicated) {
            $bucketRules[] = Rule::notIn([config('filesystems.disks.s3.bucket')]);
        }

        return [
            'storage_bucket'        => $bucketRules,
            'storage_create_bucket' => 'boolean',
            'storage_dedicated'     => 'boolean',
            'storage_endpoint'      => 'exclude_unless:storage_dedicated,1|required|url|max:255',
            'storage_url'           => 'nullable|url|max:255',
            'storage_region'        => 'nullable|string|max:50',
            'storage_key'           => 'exclude_unless:storage_dedicated,1|required|string|max:255',
            // En modification, laisser vide = conserver la clé secrète actuelle
            'storage_secret'        => 'exclude_unless:storage_dedicated,1|' . ($organization?->hasDedicatedServer() ? 'nullable' : 'required') . '|string|max:255',
        ];
    }

    private function storageMessages(): array
    {
        return [
            'storage_bucket.regex'     => 'Nom de bucket invalide : 3 à 63 caractères, minuscules, chiffres, points et tirets ; commence et finit par une lettre ou un chiffre.',
            'storage_bucket.unique'    => 'Ce bucket est déjà attribué à une autre entreprise.',
            'storage_bucket.not_in'    => 'Ce bucket est le bucket partagé de la plateforme. Choisissez un bucket propre à l\'entreprise.',
            'storage_endpoint.required'=> 'Indiquez l\'adresse du serveur MinIO dédié.',
            'storage_key.required'     => 'Indiquez la clé d\'accès du serveur MinIO dédié.',
            'storage_secret.required'  => 'Indiquez la clé secrète du serveur MinIO dédié.',
        ];
    }

    /**
     * Attributs storage_* à enregistrer sur l'entreprise à partir des données validées.
     */
    private function storageAttributes(array $data, ?Organization $organization = null): array
    {
        $dedicated = !empty($data['storage_dedicated']);

        $attributes = [
            'storage_bucket'   => $data['storage_bucket'] ?? $organization?->storage_bucket,
            'storage_url'      => $data['storage_url'] ?? null,
            'storage_region'   => $data['storage_region'] ?? null,
            'storage_endpoint' => $dedicated ? $data['storage_endpoint'] : null,
            'storage_key'      => $dedicated ? $data['storage_key'] : null,
            'storage_secret'   => null,
        ];
        if ($dedicated) {
            $attributes['storage_secret'] = filled($data['storage_secret'] ?? null)
                ? $data['storage_secret']
                : $organization?->storage_secret;
        }

        return $attributes;
    }

    /**
     * Vérifie le bucket (et le crée si demandé). Retourne un message d'erreur, sinon null.
     */
    private function provisionBucket(Organization $organization, bool $create, ?bool &$created = null): ?string
    {
        if (!$organization->hasDedicatedBucket()) {
            return null;
        }

        try {
            $created = app(OrganizationStorageService::class)->ensureBucket($organization, $create);
            return null;
        } catch (RuntimeException $e) {
            return $e->getMessage();
        }
    }

    // Créer une entreprise + son administrateur + son premier abonnement
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'reference_prefix' => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/'],
            'email'            => 'nullable|email|max:255',
            'phone'            => 'nullable|string|max:50',
            'address'          => 'nullable|string|max:1000',
            'notes'            => 'nullable|string|max:5000',

            'admin_name'       => 'required|string|max:255',
            'admin_email'      => 'required|email|max:255|unique:users,email',
            'admin_password'   => 'required|string|min:8',

            'plan_id'          => ['required', Rule::exists('plans', 'id')],
            'mode'             => 'required|in:trial,paid',
            'months'           => 'required_if:mode,paid|nullable|integer|min:1|max:60',
            'amount'           => 'nullable|integer|min:0',
            'payment_method'   => ['nullable', Rule::in(array_keys(Subscription::PAYMENT_METHODS))],
            'payment_reference'=> 'nullable|string|max:255',
        ] + $this->storageRules($request), $this->storageMessages());

        // Le bucket MinIO doit être prêt avant de créer l'entreprise
        $storage = $this->storageAttributes($data);
        $created = false;
        if ($error = $this->provisionBucket(new Organization($storage), $request->boolean('storage_create_bucket'), $created)) {
            return back()->withInput($request->except('storage_secret', 'admin_password'))->withErrors(['storage_bucket' => $error]);
        }

        try {
            $organization = $this->createOrganization($data, $storage);
        } catch (RuntimeException $e) {
            // Dossier MinIO impossible à créer : rien n'est enregistré (transaction annulée)
            return back()->withInput($request->except('storage_secret', 'admin_password'))->withErrors(['storage_bucket' => $e->getMessage()]);
        }

        $where = $organization->hasDedicatedBucket()
            ? "bucket MinIO « {$organization->storageBucket()} »" . ($created ? ' créé' : '')
            : "dossier « {$organization->storageBucket()}/{$organization->storagePrefix()}/ » créé";
        PlatformActivityLog::record('organization_created', "Entreprise « {$organization->name} » créée ({$where})", $organization);

        return redirect()->route('super.organizations.show', $organization)
            ->with('success', "Entreprise « {$organization->name} » créée. L'administrateur peut se connecter avec {$data['admin_email']}.");
    }

    // Entreprise + dossier MinIO + administrateur + premier abonnement, en une seule transaction
    private function createOrganization(array $data, array $storage): Organization
    {
        return DB::transaction(function () use ($data, $storage) {
            $organization = Organization::create([
                'name'             => $data['name'],
                'reference_prefix' => $data['reference_prefix'],
                'email'            => $data['email'] ?? null,
                'phone'            => $data['phone'] ?? null,
                'address'          => $data['address'] ?? null,
                'notes'            => $data['notes'] ?? null,
            ] + $storage);

            // Bucket partagé : chaque entreprise a son propre dossier, créé dès maintenant
            if (!$organization->hasDedicatedBucket()) {
                app(OrganizationStorageService::class)->ensureFolder($organization);
            }

            $admin = User::create([
                'organization_id' => $organization->id,
                'full_name'       => $data['admin_name'],
                'email'           => $data['admin_email'],
                'password'        => Hash::make($data['admin_password']),
            ]);
            if ($role = Role::where('name', 'admin')->first()) {
                $admin->roles()->attach($role->id);
            }
            // L'administrateur rejoint la Direction Générale (service commun), s'il existe
            if ($direction = \App\Models\Department::where('name', 'Direction Générale')->first()) {
                $admin->departments()->attach($direction->id);
            }

            $start = today();
            $trial = $data['mode'] === 'trial';
            Subscription::create([
                'organization_id'   => $organization->id,
                'plan_id'           => $data['plan_id'],
                'status'            => $trial ? 'trial' : 'active',
                'starts_at'         => $start,
                'ends_at'           => $trial
                    ? $start->copy()->addDays(config('saas.trial_days') - 1)
                    : $start->copy()->addMonths((int) $data['months'])->subDay(),
                'amount'            => $trial ? 0 : (int) ($data['amount'] ?? 0),
                'currency'          => Plan::find($data['plan_id'])->currency,
                'payment_method'    => $trial ? 'gratuit' : ($data['payment_method'] ?? null),
                'payment_reference' => $data['payment_reference'] ?? null,
                'paid_at'           => $trial ? null : $start,
                'notes'             => $trial ? 'Essai gratuit de ' . config('saas.trial_days') . ' jours' : null,
                'created_by'        => auth()->id(),
            ]);

            return $organization;
        });
    }

    public function show(Organization $organization)
    {
        $users = User::with('roles')
            ->where('organization_id', $organization->id)
            ->orderBy('full_name')
            ->get();

        $subscriptions = $organization->subscriptions()->with('plan', 'creator')->get();
        $current       = $organization->activeSubscription();
        $plan          = $current?->plan;

        $stats = [
            'documents'     => Document::withoutGlobalScopes()->where('organization_id', $organization->id)->whereNull('deleted_at')->count(),
            'trashed'       => Document::withoutGlobalScopes()->where('organization_id', $organization->id)->whereNotNull('deleted_at')->count(),
            'storage_bytes' => $organization->storageUsedBytes(),
            'users'         => $users->count(),
        ];

        $roles    = Role::orderBy('name')->get();
        $departments = \App\Models\Department::orderBy('name')->get();
        $activity = PlatformActivityLog::with('user')->where('organization_id', $organization->id)->latest()->take(10)->get();

        return view('super.organizations.show', compact('organization', 'users', 'subscriptions', 'current', 'plan', 'stats', 'roles', 'departments', 'activity'));
    }

    public function edit(Organization $organization)
    {
        $bucketLocked = Document::withoutGlobalScopes()->withTrashed()->where('organization_id', $organization->id)->exists();

        return view('super.organizations.edit', compact('organization', 'bucketLocked'));
    }

    public function update(Request $request, Organization $organization)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'reference_prefix' => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/'],
            'email'            => 'nullable|email|max:255',
            'phone'            => 'nullable|string|max:50',
            'address'          => 'nullable|string|max:1000',
            'notes'            => 'nullable|string|max:5000',
        ] + $this->storageRules($request, $organization), $this->storageMessages());

        $storage = $this->storageAttributes($data, $organization);

        // Les fichiers existants restent dans l'ancien bucket : on ne change pas de bucket une fois des documents enregistrés
        $hasFiles = Document::withoutGlobalScopes()->withTrashed()->where('organization_id', $organization->id)->exists();
        if ($hasFiles && $storage['storage_bucket'] !== $organization->storage_bucket) {
            return back()->withInput($request->except('storage_secret'))
                ->withErrors(['storage_bucket' => 'Cette entreprise a déjà des documents : son bucket ne peut plus être changé.']);
        }

        $general = collect($data)->only(['name', 'reference_prefix', 'email', 'phone', 'address', 'notes'])->all();
        $organization->fill($general + $storage);

        if ($organization->isDirty(Organization::STORAGE_FIELDS)
            && ($error = $this->provisionBucket($organization, $request->boolean('storage_create_bucket')))) {
            return back()->withInput($request->except('storage_secret'))->withErrors(['storage_bucket' => $error]);
        }

        $organization->save();
        PlatformActivityLog::record('organization_updated', "Fiche de « {$organization->name} » modifiée", $organization);

        return redirect()->route('super.organizations.show', $organization)->with('success', 'Entreprise mise à jour.');
    }

    // Vérifier la connexion au bucket MinIO de l'entreprise (et le créer s'il manque)
    public function checkStorage(Organization $organization)
    {
        if (!$organization->hasDedicatedBucket()) {
            try {
                app(OrganizationStorageService::class)->ensureFolder($organization);
            } catch (RuntimeException $e) {
                return back()->with('error', $e->getMessage());
            }
            return back()->with('success', "Connexion OK : le dossier « {$organization->storageBucket()}/{$organization->storagePrefix()}/ » est accessible.");
        }

        $created = false;
        if ($error = $this->provisionBucket($organization, true, $created)) {
            return back()->with('error', $error);
        }

        if ($created) {
            PlatformActivityLog::record('organization_bucket_created', "Bucket MinIO « {$organization->storageBucket()} » créé pour « {$organization->name} »", $organization);
        }

        return back()->with('success', $created
            ? "Bucket « {$organization->storageBucket()} » créé sur MinIO."
            : "Connexion OK : le bucket « {$organization->storageBucket()} » est accessible.");
    }

    public function suspend(Request $request, Organization $organization)
    {
        $data = $request->validate(['reason' => 'required|string|max:255']);

        $organization->update(['status' => 'suspended', 'suspended_reason' => $data['reason']]);
        PlatformActivityLog::record('organization_suspended', "« {$organization->name} » suspendue : {$data['reason']}", $organization);

        return back()->with('success', "« {$organization->name} » est suspendue. Ses utilisateurs n'ont plus accès à l'application.");
    }

    public function activate(Organization $organization)
    {
        $organization->update(['status' => 'active', 'suspended_reason' => null]);
        PlatformActivityLog::record('organization_activated', "« {$organization->name} » réactivée", $organization);

        return back()->with('success', "« {$organization->name} » est réactivée.");
    }

    // Suppression définitive : documents, fichiers MinIO, utilisateurs, abonnements
    public function destroy(Request $request, Organization $organization)
    {
        $request->validate(['confirm_name' => 'required|string']);

        if ($request->input('confirm_name') !== $organization->name) {
            return back()->withErrors(['confirm_name' => 'Le nom saisi ne correspond pas. Suppression annulée.']);
        }

        $documents = Document::withoutGlobalScopes()->withTrashed()->where('organization_id', $organization->id)->get();
        if ($documents->contains(fn (Document $d) => $d->isUnderLegalHold())) {
            return back()->with('error', 'Cette entreprise a des documents sous gel juridique (Legal Hold). Levez le gel avant de la supprimer.');
        }

        $service = app(DocumentArchivalService::class);
        foreach ($documents as $document) {
            $service->deleteStoredFiles($document);
        }

        // Bucket partagé : on retire tout le dossier de l'entreprise ; bucket dédié : supprimé s'il est vide
        app(OrganizationStorageService::class)->deleteFolder($organization);
        $bucketDeleted = app(OrganizationStorageService::class)->deleteBucketIfEmpty($organization);

        $name = $organization->name;
        PlatformActivityLog::record('organization_deleted', "Entreprise « {$name} » supprimée définitivement ({$documents->count()} documents" . ($bucketDeleted ? ", bucket « {$organization->storageBucket()} » supprimé" : '') . ')', $organization);
        $organization->delete();

        if ((int) $request->session()->get('acting_organization_id') === $organization->id) {
            $request->session()->forget('acting_organization_id');
        }

        return redirect()->route('super.organizations.index')->with('success', "Entreprise « {$name} » supprimée.");
    }

    // Export complet des données de l'entreprise (préparé en arrière-plan)
    public function export(Organization $organization)
    {
        $busy = \App\Models\OrganizationExport::withoutGlobalScopes()->where('organization_id', $organization->id)
            ->whereIn('status', ['pending', 'running'])->exists();
        if ($busy) {
            return back()->with('error', 'Un export est déjà en préparation pour cette entreprise.');
        }

        $export = \App\Models\OrganizationExport::create(['organization_id' => $organization->id, 'requested_by' => auth()->id()]);
        \App\Jobs\ExportOrganization::dispatch($export->id);
        PlatformActivityLog::record('organization_exported', "Export complet de « {$organization->name} » demandé", $organization);

        return back()->with('success', 'Export lancé : il apparaîtra ici dès qu\'il sera prêt.');
    }

    public function downloadExport(Organization $organization, int $export)
    {
        $export = \App\Models\OrganizationExport::withoutGlobalScopes()->where('organization_id', $organization->id)->findOrFail($export);
        abort_unless($export->isReady() && $organization->disk()->exists($export->path), 404, 'Export introuvable ou expiré.');

        return $organization->disk()->download($export->path, basename($export->path));
    }

    // Le super admin entre dans l'entreprise avec les droits d'administrateur
    public function enter(Request $request, Organization $organization)
    {
        $request->session()->put('acting_organization_id', $organization->id);
        PlatformActivityLog::record('organization_entered', "Accès à l'espace de « {$organization->name} »", $organization);

        return redirect()->route('dashboard')->with('success', "Vous intervenez dans l'espace de « {$organization->name} ».");
    }

    public function leave(Request $request)
    {
        $organization = Organization::find($request->session()->pull('acting_organization_id'));

        if ($organization) {
            PlatformActivityLog::record('organization_left', "Sortie de l'espace de « {$organization->name} »", $organization);
            return redirect()->route('super.organizations.show', $organization);
        }

        return redirect()->route('super.dashboard');
    }

    // Ajouter un utilisateur à une entreprise
    public function storeUser(Request $request, Organization $organization)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'email'     => 'required|email|max:255|unique:users,email',
            'password'  => 'required|string|min:8',
            'role_id'   => ['required', Rule::exists('roles', 'id')],
            'department_id' => ['required', Rule::exists('departments', 'id')],
        ], ['department_id.required' => 'Affectez l\'utilisateur à un service.']);

        $user = User::create([
            'organization_id' => $organization->id,
            'full_name'       => $data['full_name'],
            'email'           => $data['email'],
            'password'        => Hash::make($data['password']),
        ]);
        $user->roles()->attach($data['role_id']);
        $user->departments()->attach($data['department_id']);

        PlatformActivityLog::record('user_created', "Utilisateur {$user->email} ajouté à « {$organization->name} »", $organization);

        $message = "Utilisateur {$user->email} ajouté.";
        if (!$organization->canAddUsers(0)) {
            $message .= ' Attention : l\'entreprise dépasse maintenant la limite d\'utilisateurs de son offre.';
        }

        return back()->with('success', $message);
    }
}
