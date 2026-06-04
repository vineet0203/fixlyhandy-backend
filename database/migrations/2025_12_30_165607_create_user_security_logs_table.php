<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // First check if table exists
        if (Schema::hasTable('user_security_logs')) {
            Schema::dropIfExists('user_security_logs');
        }

        Schema::create('user_security_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('event_type', [
                'password_changed',
                'login_success',
                'login_failed',
                'logout',
                'account_locked',
                'account_unlocked',
                'password_reset_requested',
                'password_reset_success',
                'mfa_enabled',
                'mfa_disabled',
                'suspicious_activity'
            ]);
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('location', 191)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('event_time')->useCurrent();

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Foreign key for user_id
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // REMOVED duplicate foreign keys for created_by and updated_by

            // Indexes for performance
            $table->index(['user_id', 'event_time']);
            $table->index('event_type');
            $table->index('event_time');
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_security_logs');
    }
};
