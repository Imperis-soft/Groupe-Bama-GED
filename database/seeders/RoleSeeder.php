<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'admin',  'display_name' => 'Administrateur', 'description' => 'Accès complet à toutes les fonctionnalités.'],
            ['name' => 'editor', 'display_name' => 'Éditeur',        'description' => 'Peut créer, modifier et archiver des documents.'],
            ['name' => 'viewer', 'display_name' => 'Lecteur',        'description' => 'Consultation et téléchargement uniquement.'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }

        $this->command->info('✓ ' . count($roles) . ' rôles créés.');
    }
}
