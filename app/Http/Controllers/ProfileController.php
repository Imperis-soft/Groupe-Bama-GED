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
        $user = auth()->user()->load('roles', 'loginHistories', 'delegate');
        $colleagues = \App\Models\User::where('id', '!=', $user->id)->where('is_active', true)->orderBy('full_name')->get(['id', 'full_name']);
        return view('profile.show', compact('user', 'colleagues'));
    }

    // Absence : pendant la période, les validations sont confiées au suppléant
    public function updateAbsence(Request $request)
    {
        $user = auth()->user();

        if ($request->boolean('clear')) {
            $user->update(['absent_from' => null, 'absent_until' => null, 'delegate_id' => null]);
            return redirect()->route('profile.show')->with('success', 'Absence supprimée : les validations vous sont de nouveau adressées.');
        }

        $data = $request->validate([
            'absent_from'  => 'required|date',
            'absent_until' => 'required|date|after_or_equal:absent_from|after_or_equal:today',
            'delegate_id'  => ['required', \App\Support\Tenant::exists('users'), \Illuminate\Validation\Rule::notIn([$user->id])],
        ], [
            'absent_until.after_or_equal' => 'La date de retour doit être postérieure au début de l\'absence et à aujourd\'hui.',
            'delegate_id.required'        => 'Choisissez la personne qui validera à votre place.',
            'delegate_id.not_in'          => 'Vous ne pouvez pas être votre propre suppléant.',
        ]);

        $user->update($data);
        $reassigned = app(\App\Services\ApprovalWorkflow::class)->reassignAbsent($user->fresh());

        $message = 'Absence enregistrée du ' . $user->absent_from->format('d/m/Y') . ' au ' . $user->absent_until->format('d/m/Y') . '.';
        if ($reassigned) {
            $message .= " {$reassigned} validation(s) en cours confiée(s) à {$user->delegate->full_name}.";
        }

        return redirect()->route('profile.show')->with('success', $message);
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
