<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
{
    \App\Models\User::create([
        'full_name' => 'Admin Bama',
        'email' => 'admin@bama.com',
        'phone' => '+22300000000',
        'address' => 'Bamako, Mali',
        'password' => bcrypt('password123'),
    ]);
}
}
