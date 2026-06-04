<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assigned_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained('clients')->onDelete('set null');
            $table->string('employee_name');
            $table->string('employee_email');
            $table->foreignId('template_id')->constrained('document_templates')->onDelete('cascade');
            $table->uuid('token')->unique();
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->string('completed_pdf_path')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('token');
            $table->index('vendor_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assigned_documents');
    }
};
