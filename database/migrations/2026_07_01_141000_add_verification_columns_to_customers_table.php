<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('google_id', 191)->nullable();
            $table->timestamp('whatsapp_verified_at')->nullable();
            $table->string('whatsapp_number', 20)->nullable();
            $table->tinyInteger('is_verified')->default(0);
            $table->enum('verification_method', ['gmail', 'whatsapp', 'both'])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'google_id',
                'whatsapp_verified_at',
                'whatsapp_number',
                'is_verified',
                'verification_method'
            ]);
        });
    }
};
