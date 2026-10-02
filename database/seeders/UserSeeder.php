<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();

        // Entreprise de démonstration avec un abonnement d'un an
        $organization = Organization::firstOrCreate(
            ['slug' => 'entreprise-demo'],
            ['name' => 'Entreprise Démo', 'reference_prefix' => 'DEMO', 'status' => 'active']
        );
        if (!$organization->subscriptions()->exists()) {
            Subscription::create([
                'organization_id' => $organization->id,
                'plan_id'         => Plan::where('slug', 'entreprise')->value('id'),
                'status'          => 'active',
                'starts_at'       => today(),
                'ends_at'         => today()->addYear()->subDay(),
                'amount'          => 0,
                'payment_method'  => 'gratuit',
                'notes'           => 'Abonnement de démonstration (seeder)',
            ]);
        }

        // Super administrateur de la plateforme (Imperis) : aucune entreprise
        $imperis = User::firstOrCreate(
            ['email' => 'contact@imperis.com'],
            [
                'full_name' => 'Imperis Group',
                'phone'     => '+22300000001',
                'address'   => 'Bamako, Mali',
                'password'  => Hash::make('idADMIN78'),
            ]
        );
        $imperis->forceFill(['is_super_admin' => true, 'organization_id' => null, 'is_active' => true])->save();

        $this->command->info('✓ Utilisateurs créés : admin@demo.com (entreprise de démo) et ' . config('saas.super_admin_email') . ' (super admin)');
    }
}
