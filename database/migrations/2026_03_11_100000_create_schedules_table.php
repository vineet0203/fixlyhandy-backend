<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crew_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');

            $table->enum('priority', ['normal', 'high', 'emergency'])->default('normal');
            $table->enum('status', ['draft', 'scheduled', 'completed', 'cancelled'])->default('draft')->index();

            $table->text('notes')->nullable();

            // Schedule options
            $table->boolean('is_multi_day')->default(false);
            $table->boolean('is_recurring')->default(false);

            // Notification preferences
            $table->boolean('notify_client')->default(false);
            $table->boolean('notify_crew')->default(false);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['vendor_id', 'status']);
            $table->index(['start_datetime', 'end_datetime']);
            $table->index('job_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('schedules');
    }
};
