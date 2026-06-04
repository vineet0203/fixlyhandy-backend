<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUsersSeeder extends Seeder
{
    public function run()
    {
        // Get platform_super_admin role
        $adminRole = Role::where('slug', 'platform_admin')->first();

        if (!$adminRole) {
            $this->command->error('❌ platform_super_admin role not found. Run RolesSeeder first.');
            return;
        }

        $adminUsers = [
            [
                'email' => 'rajpootsourabh1999@gmail.com',
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'password' => 'AdminPassword123!',
                'description' => 'Primary super administrator',
            ]
        ];

        foreach ($adminUsers as $adminData) {
            $user = User::updateOrCreate(
                ['email' => $adminData['email']],
                [
                    'first_name' => $adminData['first_name'],
                    'last_name' => $adminData['last_name'],
                    'password' => Hash::make($adminData['password']),
                    'is_active' => true,
                    'is_system' => false,
                    'created_by' => 1,
                    'updated_by' => 1,
                ]
            );

            // Attach admin role if not already attached
            if (!$user->hasRole('platform_admin')) {
                $user->roles()->attach($adminRole->id, [
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),

                ]);
                $this->command->info("✅ Created admin user: {$adminData['email']} - {$adminData['description']}");
            } else {
                $this->command->info("ℹ️  Admin user already exists: {$adminData['email']}");
            }
        }

        $this->command->info("\n📋 Admin Login Credentials:");
        $this->command->info("==========================");
        foreach ($adminUsers as $adminData) {
            $this->command->info("Email: {$adminData['email']}");
            $this->command->info("Password: {$adminData['password']}");
            $this->command->info("Description: {$adminData['description']}");
            $this->command->info("---");
        }
    }
}
