<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
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

        $this->sendResetEmail($user, $resetUrl);

        return back()->with('success', 'Si cet email existe, un lien de réinitialisation a été envoyé.');
    }

    private function sendResetEmail(User $user, string $resetUrl): void
    {
        try {
            // Charger les settings SMTP depuis la DB
            $settings = DB::table('settings')->pluck('value', 'key')->toArray();

            $mailEnabled = ($settings['mail_enabled'] ?? '0') === '1'
                && !empty($settings['mail_host'] ?? '');

            if (!$mailEnabled) {
                Log::info('GED: email désactivé, lien reset = ' . $resetUrl);
                return;
            }

            config([
                'mail.default'                 => 'smtp',
                'mail.mailers.smtp.host'       => $settings['mail_host'],
                'mail.mailers.smtp.port'       => $settings['mail_port'] ?? 587,
                'mail.mailers.smtp.username'   => $settings['mail_username'] ?? '',
                'mail.mailers.smtp.password'   => $settings['mail_password'] ?? '',
                'mail.mailers.smtp.encryption' => $settings['mail_encryption'] ?? 'tls',
                'mail.from.address'            => $settings['mail_from_address'] ?? $settings['mail_username'] ?? '',
                'mail.from.name'               => $settings['mail_from_name'] ?? 'Groupe Bama GED',
            ]);

            Mail::send('emails.password-reset', [
                'recipientName' => $user->full_name,
                'resetUrl'      => $resetUrl,
                'subject'       => 'Réinitialisation de mot de passe',
            ], function ($mail) use ($user) {
                $mail->to($user->email, $user->full_name)
                     ->subject('[GED] Réinitialisation de votre mot de passe');
            });

        } catch (\Exception $e) {
            Log::warning('GED password reset email failed: ' . $e->getMessage());
        }
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
