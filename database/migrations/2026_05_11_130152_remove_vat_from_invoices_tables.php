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
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('vat');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn('vat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('vat', 8, 2)->default(0);
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->decimal('vat', 8, 2)->default(0);
        });
    }
};
