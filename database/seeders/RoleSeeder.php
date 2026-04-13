<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $admin = Role::firstOrCreate(['name' => 'admin'], ['display_name' => 'Administrator']);
        $editor = Role::firstOrCreate(['name' => 'editor'], ['display_name' => 'Editor']);
        $viewer = Role::firstOrCreate(['name' => 'viewer'], ['display_name' => 'Viewer']);

        // Attach admin emails if users exist
        foreach (['admin@bama.com', 'contact@imperis.com'] as $email) {
            $user = User::where('email', $email)->first();
            if ($user && ! $user->roles()->where('name', 'admin')->exists()) {
                $user->roles()->attach($admin->id);
            }
        }
    }
}
