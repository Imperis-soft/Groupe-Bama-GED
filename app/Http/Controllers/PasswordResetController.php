<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function showForgot()
    {
        return view('auth.forgot-password');
    }

    public function sendReset(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $token = Str::random(64);

        DB::table('password_reset_tokens')->upsert([
            'email'      => $request->email,
            'token'      => Hash::make($token),
            'created_at' => now(),
        ], ['email']);

        $user     = User::where('email', $request->email)->first();
        $resetUrl = url('/reset-password/' . $token . '?email=' . urlencode($request->email));

        // Envoyer via NotificationService (email si configuré, sinon in-app)
        app(NotificationService::class)->notify(
            $user,
            'password_reset',
            'Réinitialisation de mot de passe',
            "Cliquez sur le lien suivant pour réinitialiser votre mot de passe. Ce lien expire dans 60 minutes.",
            $resetUrl
        );

        return back()->with('success', 'Si cet email existe, un lien de réinitialisation a été envoyé.');
    }

    public function showReset(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|exists:users,email',
            'token'    => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return back()->withErrors(['token' => 'Lien invalide ou expiré.']);
        }

        // Expiration 60 min
        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return back()->withErrors(['token' => 'Ce lien a expiré. Faites une nouvelle demande.']);
        }

        User::where('email', $request->email)->update([
            'password' => Hash::make($request->password),
        ]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect('/login')->with('success', 'Mot de passe réinitialisé. Vous pouvez vous connecter.');
    }
}
