<?php

namespace App\Http\Controllers;

use App\Models\DocumentAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show()
    {
        $user = auth()->user()->load('roles', 'loginHistories');
        return view('profile.show', compact('user'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone'     => 'nullable|string|max:50',
            'address'   => 'nullable|string|max:500',
        ]);
        $user->update($data);
        return redirect()->route('profile.show')->with('success', 'Profil mis à jour.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);
        $user = auth()->user();
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Mot de passe actuel incorrect.'])->withInput();
        }
        $user->update(['password' => Hash::make($request->password)]);
        return redirect()->route('profile.show')->with('success_password', 'Mot de passe mis à jour.');
    }

    public function activity()
    {
        $user = auth()->user();
        $activities = DocumentAuditLog::where('user_id', $user->id)
            ->with('document')
            ->latest()
            ->paginate(25);

        return view('profile.activity', compact('user', 'activities'));
    }

    public function sessions()
    {
        $user       = auth()->user();
        $currentId  = session()->getId();
        $sessions   = DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(function ($s) use ($currentId) {
                $payload = @unserialize(base64_decode($s->payload));
                return (object)[
                    'id'            => $s->id,
                    'ip_address'    => $s->ip_address,
                    'user_agent'    => $s->user_agent,
                    'last_activity' => \Carbon\Carbon::createFromTimestamp($s->last_activity),
                    'is_current'    => $s->id === $currentId,
                ];
            });

        return view('profile.sessions', compact('user', 'sessions', 'currentId'));
    }

    public function revokeSession(Request $request, string $sessionId)
    {
        $user = auth()->user();
        // Ne pas révoquer la session courante
        if ($sessionId === session()->getId()) {
            return back()->with('error', 'Impossible de révoquer la session courante.');
        }
        DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $user->id)
            ->delete();

        return back()->with('success', 'Session révoquée.');
    }
}
