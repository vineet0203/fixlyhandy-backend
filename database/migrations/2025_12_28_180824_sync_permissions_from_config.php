<?php

use App\Helpers\RoleCapabilities;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

class SyncPermissionsFromConfig extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // This ensures permissions are synced when running migrations
        //Artisan::call('db:seed', ['--class' => 'SystemDataSeeder']);
        // Clear existing permissions and resync from config
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \Illuminate\Support\Facades\DB::table('role_permissions')->delete();
        \Illuminate\Support\Facades\DB::table('permissions')->delete();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        // Resync permissions from config
        $results = RoleCapabilities::syncPermissionsFromConfig();
        
        // Log the results
        \Illuminate\Support\Facades\Log::info('Permissions resynced from config', $results);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optional: Clear permissions on rollback
        // \App\Models\Permission::truncate();
        // \App\Models\Role::truncate();
    }
}


//php artisan make:migration sync_permissions_from_config
