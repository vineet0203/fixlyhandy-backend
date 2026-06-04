<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            // Drop the incorrect foreign key that references 'users' table
            $table->dropForeign(['vendor_id']);
            
            // Add the correct foreign key that references 'vendors' table
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            // Revert: drop the correct FK and restore the incorrect one
            $table->dropForeign(['vendor_id']);
            $table->foreign('vendor_id')->references('id')->on('users')->onDelete('set null');
        });
    }
};
