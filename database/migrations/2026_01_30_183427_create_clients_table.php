<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // database/migrations/xxxx_xx_xx_xxxxxx_create_clients_table.php
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('client_type', ['commercial', 'residential'])->default('commercial')->index();

            // ---------- Residential specific ----------
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();

            // ---------- Commercial specific ----------
            $table->string('business_name')->nullable();
            $table->enum('business_type', ['individual', 'sole_proprietorship', 'partnership', 'llc', 'corporation', 'non_profit', 'government', 'other'])->nullable();
            $table->string('industry')->nullable();
            $table->string('business_registration_number')->nullable();
            $table->string('contact_person_name')->nullable();
            $table->string('designation')->nullable();          // renamed from designation_role
            $table->string('billing_name')->nullable();
            $table->enum('payment_term', ['net_7', 'net_15', 'net_30', 'net_45', 'net_60', 'due_on_receipt'])->default('net_30');
            $table->string('preferred_currency', 3)->default('USD');
            $table->decimal('tax_percentage', 5, 2)->nullable();
            $table->string('website_url')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('service_category', 100)->nullable()->default(null);

            // ---------- Common for both ----------
            $table->string('email')->nullable();                // unified
            $table->string('mobile_number')->nullable();        // unified
            $table->string('alternate_mobile_number')->nullable();

            // Unified address (structured)
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('zip_code')->nullable();

            $table->text('notes')->nullable();                  // unified notes
            $table->enum('status', ['active', 'inactive', 'suspended', 'archived'])->default('active');

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['vendor_id', 'status']);
            $table->index('email');
            $table->index('service_category');
        });
    }

    public function down()
    {
        Schema::dropIfExists('clients');
    }
};
