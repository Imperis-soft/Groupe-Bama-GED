<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateSuperAdmin extends Command
{
    protected $signature = 'saas:super-admin {--email= : Email du super admin (par défaut config saas.super_admin_email)} {--password= : Mot de passe (demandé si absent pour un nouveau compte)}';

    protected $description = 'Créer ou réparer le compte super administrateur de la plateforme';

    public function handle(): int
    {
        $email = $this->option('email') ?: config('saas.super_admin_email');
        $user  = User::withoutGlobalScopes()->where('email', $email)->first();

        $password = $this->option('password');
        if (!$user && !$password) {
            $password = $this->secret("Mot de passe pour {$email}");
        }
        if ($password !== null && strlen($password) < 8) {
            $this->error('Le mot de passe doit faire au moins 8 caractères.');
            return self::FAILURE;
        }

        $user ??= new User(['email' => $email, 'full_name' => config('saas.vendor_name')]);
        if ($password) {
            $user->password = Hash::make($password);
        }
        $user->forceFill([
            'organization_id' => null, // le super admin n'appartient à aucune entreprise
            'is_super_admin'  => true,
            'is_active'       => true,
        ])->save();

        $this->info("✅ {$email} est super administrateur. Espace : " . url('/super-admin'));

        return self::SUCCESS;
    }
}
