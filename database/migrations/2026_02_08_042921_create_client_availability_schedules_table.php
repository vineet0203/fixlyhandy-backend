<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_availability_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');

            // Days Available - Using SET for multiple days
            $table->json('available_days')->nullable();

            // Time Slots
            $table->time('preferred_start_time')->default('09:00:00');
            $table->time('preferred_end_time')->default('17:00:00');

            // Lunch Break
            $table->boolean('has_lunch_break')->default(false);
            $table->time('lunch_start')->nullable();
            $table->time('lunch_end')->nullable();

            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_availability_schedules');
    }
};
