<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();

            // Quote Details (Section 1)
            $table->string('quote_number')->unique();
            $table->string('title');
            $table->foreignId('client_id')->nullable()->constrained('clients')->onDelete('set null');
            $table->string('client_name');
            $table->string('client_email');
            $table->enum('equity_status', ['pending', 'approved', 'rejected', 'not_applicable'])->default('not_applicable');
            $table->string('currency', 3)->default('USD');

            // Pricing Summary (Section 3)
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->boolean('deposit_required')->default(false);
            $table->enum('deposit_type', ['none', 'percentage', 'fixed'])->default('none')->nullable();
            $table->decimal('deposit_amount', 12, 2)->nullable();

            // Client Approval (Section 4)
            $table->enum('approval_status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->text('client_signature')->nullable();
            $table->timestamp('approval_date')->nullable();
            $table->timestamp('approval_action_date')->nullable();

            // Quote Status (separate from approval)
            $table->enum('status', ['draft', 'accepted', 'sent', 'viewed', 'expired', 'pending', 'approved', 'rejected'])->default('draft');
            $table->timestamp('sent_at')->nullable();

            // Conversion to Job (Section 6)
            $table->boolean('can_convert_to_job')->default(true);
            $table->boolean('is_converted')->default(false);
            $table->foreignId('job_id')->nullable()->onDelete('set null');
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('converted_by')->nullable()->constrained('users')->onDelete('set null');


            // Meta
            $table->timestamp('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('quote_number');
            $table->index('client_id');
            $table->index('client_email');
            $table->index('status');
            $table->index('approval_status');
            $table->index('vendor_id');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
