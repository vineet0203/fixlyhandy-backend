<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();

            // Relations
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            // Work details
            $table->enum('work_type', [
                'one_time',
                'recurring',
                'maintenance',
                'emergency',
                'installation',
                'repair',
                'consultation',
                'other'
            ])->default('one_time');

            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');

            $table->enum('status', [
                'pending',
                'scheduled',
                'in_progress',
                'on_hold',
                'completed',
                'cancelled',
                'archived'
            ])->default('pending')->index();

            // Dates
            $table->date('issue_date');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('estimated_completion_date')->nullable();
            $table->dateTime('actual_completion_date')->nullable();

            // Financial
            $table->string('currency', 3)->default('USD');
            $table->decimal('estimated_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_due', 12, 2)->default(0);

            // Location
            $table->enum('location_type', ['office', 'remote', 'client_site', 'other'])->nullable();
            $table->text('address_line_1')->nullable();
            $table->text('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('zip_code')->nullable();

            // Extra
            $table->text('instructions')->nullable();
            $table->text('notes')->nullable();

            // Conversion tracking
            $table->boolean('is_converted_from_quote')->default(false);
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('converted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['start_date', 'end_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('jobs');
    }
};
