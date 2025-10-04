<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;

class OpenAiPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create OpenAI related permissions
        $permissions = [
            [
                'name' => 'use-openai',
                'description' => 'Can use OpenAI features',
                'group' => 'openai',
            ],
            [
                'name' => 'view-openai-logs',
                'description' => 'Can view OpenAI API logs',
                'group' => 'openai',
            ],
            [
                'name' => 'manage-openai-settings',
                'description' => 'Can manage OpenAI settings',
                'group' => 'openai',
            ],
            [
                'name' => 'batch-process-materials',
                'description' => 'Can batch process materials',
                'group' => 'openai',
            ],
            [
                'name' => 'regenerate-questions',
                'description' => 'Can regenerate quiz questions',
                'group' => 'openai',
            ],
            [
                'name' => 'view-api-costs',
                'description' => 'Can view API usage costs',
                'group' => 'openai',
            ],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        $this->command->info('OpenAI permissions created successfully!');

        // Assign permissions to roles
        $this->assignPermissionsToRoles();
    }

    /**
     * Assign permissions to roles
     */
    private function assignPermissionsToRoles(): void
    {
        // Admin gets all permissions
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminPermissions = Permission::where('group', 'openai')->pluck('id');
            $adminRole->permissions()->syncWithoutDetaching($adminPermissions);
        }

        // Instructor gets most permissions
        $instructorRole = Role::where('name', 'instructor')->first();
        if ($instructorRole) {
            $instructorPermissions = Permission::whereIn('name', [
                'use-openai',
                'view-openai-logs',
                'batch-process-materials',
                'regenerate-questions',
                'view-api-costs',
            ])->pluck('id');
            $instructorRole->permissions()->syncWithoutDetaching($instructorPermissions);
        }

        // Students get basic permissions
        $studentRole = Role::where('name', 'student')->first();
        if ($studentRole) {
            $studentPermissions = Permission::whereIn('name', [
                'use-openai',
            ])->pluck('id');
            $studentRole->permissions()->syncWithoutDetaching($studentPermissions);
        }

        $this->command->info('Permissions assigned to roles successfully!');
    }
}
