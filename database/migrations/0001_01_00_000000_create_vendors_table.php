<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();

            // Basic Business Information
            $table->string('business_name');
            $table->string('website_name')->nullable();
            $table->string('business_type')->nullable();
            $table->text('service_description')->nullable();

            // Contact Information
            $table->string('full_name')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile_number')->nullable();

            // Terms & Conditions
            $table->boolean('terms_accepted')->default(false);

            // Status (Active / Inactive)
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->timestamps();

            // Add SoftDeletes
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};