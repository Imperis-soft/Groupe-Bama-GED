<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();

        // Super admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@bama.com'],
            [
                'full_name' => 'Admin Bama',
                'phone'     => '+22300000000',
                'address'   => 'Bamako, Mali',
                'password'  => Hash::make('AdminBama01'),
            ]
        );
        if ($adminRole && !$admin->roles()->where('name', 'admin')->exists()) {
            $admin->roles()->attach($adminRole->id);
        }

        // Admin Imperis
        $imperis = User::firstOrCreate(
            ['email' => 'contact@imperis.com'],
            [
                'full_name' => 'Imperis Sarl',
                'phone'     => '+22300000001',
                'address'   => 'Bamako, Mali',
                'password'  => Hash::make('Imperis@2024'),
            ]
        );
        if ($adminRole && !$imperis->roles()->where('name', 'admin')->exists()) {
            $imperis->roles()->attach($adminRole->id);
        }

        $this->command->info('✓ Utilisateurs créés : admin@bama.com / AdminBama01');
    }
}
