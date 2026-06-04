<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'is_tax_applicable')) {
                $table->boolean('is_tax_applicable')->default(false)->after('preferred_currency');
            }

            if (Schema::hasColumn('clients', 'tax_percentage')) {
                $table->integer('tax_percentage')->default(0)->change();
            } else {
                $table->integer('tax_percentage')->default(0)->after('is_tax_applicable');
            }
        });

        Schema::table('quotes', function (Blueprint $table) {
            if (!Schema::hasColumn('quotes', 'is_tax_applicable')) {
                $table->boolean('is_tax_applicable')->default(false)->after('discount');
            }

            if (!Schema::hasColumn('quotes', 'tax_percentage')) {
                $table->integer('tax_percentage')->default(0)->after('is_tax_applicable');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            if (Schema::hasColumn('quotes', 'tax_percentage')) {
                $table->dropColumn('tax_percentage');
            }
            if (Schema::hasColumn('quotes', 'is_tax_applicable')) {
                $table->dropColumn('is_tax_applicable');
            }
        });

        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'is_tax_applicable')) {
                $table->dropColumn('is_tax_applicable');
            }

            if (Schema::hasColumn('clients', 'tax_percentage')) {
                $table->decimal('tax_percentage', 5, 2)->nullable()->change();
            }
        });
    }
};