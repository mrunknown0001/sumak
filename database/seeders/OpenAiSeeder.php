<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class OpenAiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting OpenAI related seeders...');

        $this->call([
            OpenAiPermissionsSeeder::class,
            ChatGptApiLogSeeder::class,
        ]);

        $this->command->info('OpenAI seeders completed successfully!');
    }
}