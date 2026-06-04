<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            /**
             * Actor (who did it)
             */
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('actor_type')->default('user');
            // user | system | cron | api | webhook

            /**
             * What happened
             */
            $table->string('event');
            // created | updated | deleted | assigned | rejected | status_changed

            $table->string('entity_type');
            // Candidate | JobApplication | Employee | Job | Role

            $table->unsignedBigInteger('entity_id')->nullable();

            /**
             * Data snapshot
             */
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            /**
             * Context & metadata
             */
            $table->string('context')->nullable();
            // employee_update | application_reject | bulk_import

            $table->json('meta')->nullable();
            // ip, request_id, reason, headers, source, etc.

            $table->ipAddress('ip_address')->nullable();

            /**
             * Multi-tenant scope
             */
            $table->foreignId('company_id')->nullable()->index();

            /**
             * Timestamp
             */
            $table->timestamp('created_at')->useCurrent();

            /**
             * Indexes for performance
             */
            $table->index(['entity_type', 'entity_id']);
            $table->index('event');
            $table->index('actor_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
