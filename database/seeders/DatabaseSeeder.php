<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        // Services communs à toutes les entreprises
        $this->call(DepartmentSeeder::class);
        // Crée le super admin, l'entreprise de démonstration et son administrateur
        $this->call(UserSeeder::class);

        // Catégories de l'entreprise de démonstration
        $demo = \App\Models\Organization::where('slug', 'entreprise-demo')->first();
        \App\Support\Tenant::run($demo, fn () => $this->call(CategorySeeder::class));

        // Les utilisateurs de la démo sans service rejoignent la Direction Générale
        if ($demo && ($direction = \App\Models\Department::where('name', 'Direction Générale')->first())) {
            $userIds = \App\Models\User::withoutGlobalScopes()->where('organization_id', $demo->id)->whereDoesntHave('departments')->pluck('id');
            $direction->users()->syncWithoutDetaching($userIds);
        }
    }
}
