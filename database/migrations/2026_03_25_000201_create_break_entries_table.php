<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('break_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('time_entry_id')->constrained('time_entries')->cascadeOnDelete();
            $table->dateTime('break_start');
            $table->dateTime('break_end')->nullable();
            $table->unsignedInteger('break_duration')->default(0);
            $table->timestamps();

            $table->index(['time_entry_id', 'break_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('break_entries');
    }
};
