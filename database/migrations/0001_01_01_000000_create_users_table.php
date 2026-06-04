<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Tenant / Business Owner Link (Multi Vendor Support)
            $table->foreignId('vendor_id')
                ->nullable()
                ->constrained('vendors')
                ->nullOnDelete();


            // Identity
            $table->string('email')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();

            // Authentication
            $table->string('password');
            $table->timestamp('password_changed_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();

            // Account state
            $table->boolean('is_active')->default(true);
            $table->string('status', 50)->default('active'); // active, inactive, suspended
            $table->boolean('is_system')->default(false);

            // Deactivation / Reactivation tracking
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamp('reactivated_at')->nullable();
            $table->string('deactivation_reason', 500)->nullable();
            $table->string('reactivation_reason', 500)->nullable();

            // Login security
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->unsignedSmallInteger('failed_login_attempts')->default(0);
            $table->timestamp('account_locked_until')->nullable();
            $table->boolean('force_password_change')->default(false);
            $table->timestamp('last_password_reset_at')->nullable();
            $table->json('security_settings')->nullable();

            // Tokens
            $table->rememberToken();

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Indexes
            $table->index('is_active');
            $table->index('status');
            $table->index('account_locked_until');
            $table->index('password_changed_at');
            $table->index('last_login_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};
