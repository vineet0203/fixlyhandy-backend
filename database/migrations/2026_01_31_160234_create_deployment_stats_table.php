<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('deployment_stats', function (Blueprint $table) {
            $table->id();
            
            // Deployment Identification
            $table->string('deployment_id', 50)->unique()->nullable();
            $table->string('trigger_type', 20)->default('github'); // github, manual, rollback
            $table->string('environment', 20)->default('production');
            
            // Git Information
            $table->string('commit_hash', 100);
            $table->string('commit_short_hash', 10)->nullable();
            $table->text('commit_message');
            $table->string('author_name', 100)->nullable();
            $table->string('author_email', 150)->nullable();
            $table->string('author_github_url', 255)->nullable();
            $table->string('commit_url', 255)->nullable();
            
            // Repository Information
            $table->string('repository_name', 255);
            $table->string('repository_url', 255)->nullable();
            $table->string('branch', 50)->default('main');
            
            // Deployment Execution Info
            $table->boolean('success')->default(false);
            $table->decimal('duration_seconds', 8, 2);
            $table->integer('output_size')->default(0);
            
            // IP & Location Tracking
            $table->string('trigger_ip', 45)->nullable(); // IPv6 compatible
            $table->string('trigger_country', 100)->nullable();
            $table->string('trigger_city', 100)->nullable();
            $table->string('trigger_region', 100)->nullable();
            $table->decimal('trigger_latitude', 10, 8)->nullable();
            $table->decimal('trigger_longitude', 10, 8)->nullable();
            
            // Error Information (if failed)
            $table->text('error_message')->nullable();
            $table->text('error_trace')->nullable();
            
            // System Information
            $table->string('server_hostname', 100)->nullable();
            $table->string('server_ip', 45)->nullable();
            
            // Backup Information
            $table->boolean('backup_created')->default(false);
            $table->string('backup_path', 255)->nullable();
            
            // Rollback Information
            $table->boolean('is_rollback')->default(false);
            $table->string('rollback_from_commit', 100)->nullable();
            
            // Additional Metadata
            $table->json('metadata')->nullable(); // For any extra data
            $table->json('payload_summary')->nullable(); // GitHub payload summary
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for faster queries
            $table->index('commit_hash');
            $table->index('author_name');
            $table->index('success');
            $table->index('created_at');
            $table->index('trigger_ip');
            $table->index(['environment', 'success']);
            $table->index(['trigger_type', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('deployment_stats');
    }
};