<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Document;
use App\Models\Topic;
use App\Models\TableOfSpecification;
use App\Models\TosItem;
use App\Models\ItemBank;
use App\Models\LearningOutcome;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        $this->call([
            // Add other seeders here if needed
            UserSeeder::class,
        ]);

        // Create test users
        $student = User::create([
            'name' => 'Juan Dela Cruz',
            'email' => 'student@sumakquiz.com',
            'password' => Hash::make('password'),
            'role' => 'student',
            'email_verified_at' => now(),
        ]);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@sumakquiz.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }
}
