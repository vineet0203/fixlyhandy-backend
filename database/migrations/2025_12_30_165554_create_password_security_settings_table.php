<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // First check if table exists
        if (Schema::hasTable('password_security_settings')) {
            Schema::dropIfExists('password_security_settings');
        }

        Schema::create('password_security_settings', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['global', 'company'])->default('global');

            // Security settings
            $table->unsignedTinyInteger('min_length')->default(8);
            $table->boolean('require_uppercase')->default(true);
            $table->boolean('require_lowercase')->default(true);
            $table->boolean('require_numbers')->default(true);
            $table->boolean('require_symbols')->default(true);
            $table->unsignedTinyInteger('password_expiry_days')->default(90);
            $table->unsignedTinyInteger('password_history_size')->default(5);
            $table->unsignedTinyInteger('max_login_attempts')->default(5);
            $table->unsignedSmallInteger('lockout_duration_minutes')->default(15);
            $table->boolean('force_password_change_on_first_login')->default(false);
            $table->boolean('notify_on_password_change')->default(true);
            $table->boolean('require_mfa')->default(false);

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Indexes
            $table->index('type');
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_security_settings');
    }
};
