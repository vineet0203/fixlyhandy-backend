<?php
// database/migrations/xxxx_create_temporary_uploads_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temporary_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('temp_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('original_name');
            $table->string('storage_path');
            $table->string('disk')->default('local');
            $table->string('mime_type');
            $table->integer('size'); // in bytes
            $table->timestamp('expires_at');
            $table->boolean('is_used')->default(false);
            $table->string('final_path')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
            
            $table->index(['temp_id', 'is_used']);
            $table->index('expires_at');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temporary_uploads');
    }
};