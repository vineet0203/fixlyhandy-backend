<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->onDelete('cascade');

            // Reminder Details (Section 5)
            $table->timestamp('scheduled_at');
            $table->enum('reminder_type', ['email', 'sms', 'notification'])->default('email');
            $table->enum('status', ['scheduled', 'sent', 'cancelled'])->default('scheduled');

            // Tracking
            $table->timestamp('sent_at')->nullable();
            $table->text('response')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('quote_id');
            $table->index('scheduled_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_reminders');
    }
};
