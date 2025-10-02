<?php

namespace Database\Seeders;

use App\Models\User;

class UserSeeder extends \Illuminate\Database\Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@localhost',
            'password' => bcrypt('password'),
            'role' => 'superadmin',
        ]);

        User::create([
            'name' => 'Admin',
            'email' => 'admin@localhost',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Student User1',
            'email' => 'student1@localhost',
            'password' => bcrypt('password'),
            'role' => 'student',
        ]);
    }
}