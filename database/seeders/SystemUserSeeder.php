<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SystemUserSeeder extends Seeder
{
    public function run()
    {
        User::updateOrCreate(
            ['email' => 'system@fixlyhandy.local'],
            [
                'password'   => Hash::make(str()->random(32)),
                'is_active'  => true,
                'is_system'  => true,
                'first_name' => 'System',
                'last_name'  => 'User',
                'created_at' => now(),
                'updated_at' => now(),

            ]
        );
    }
}
