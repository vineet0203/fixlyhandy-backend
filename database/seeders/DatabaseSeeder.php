<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SystemUserSeeder::class,
            SystemDataSeeder::class,
            AdminUsersSeeder::class,
            PasswordSecuritySettingsSeeder::class,
            DocumentTemplateSeeder::class,
        ]);
    }
}
