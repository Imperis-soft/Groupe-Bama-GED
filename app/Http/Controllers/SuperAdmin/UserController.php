<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\PlatformActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('organization', 'roles')->orderBy('full_name');

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            });
        }
        if ($orgId = $request->input('organization')) {
            $query->where('organization_id', $orgId);
        }
        if ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $users         = $query->paginate(25)->withQueryString();
        $organizations = Organization::orderBy('name')->get(['id', 'name']);

        return view('super.users.index', compact('users', 'organizations'));
    }

    public function toggleActive(User $user)
    {
        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Le compte super administrateur ne peut pas être désactivé.');
        }

        $user->update(['is_active' => !$user->is_active]);

        if (!$user->is_active) {
            // Couper immédiatement ses sessions
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->forceFill(['remember_token' => Str::random(60)])->save();
        }

        PlatformActivityLog::record(
            $user->is_active ? 'user_activated' : 'user_deactivated',
            "Compte {$user->email} " . ($user->is_active ? 'réactivé' : 'désactivé'),
            $user->organization
        );

        return back()->with('success', "Compte {$user->email} " . ($user->is_active ? 'réactivé.' : 'désactivé.'));
    }

    // Définir un nouveau mot de passe (support : utilisateur bloqué, admin parti…)
    public function updatePassword(Request $request, User $user)
    {
        $request->validate(['password' => 'required|string|min:8']);

        $user->forceFill([
            'password'       => Hash::make($request->input('password')),
            'remember_token' => Str::random(60),
        ])->save();
        DB::table('sessions')->where('user_id', $user->id)->delete();

        PlatformActivityLog::record('user_password_reset', "Mot de passe de {$user->email} redéfini par le super admin", $user->organization);

        return back()->with('success', "Nouveau mot de passe défini pour {$user->email}. Communiquez-le-lui de façon sécurisée.");
    }
}
