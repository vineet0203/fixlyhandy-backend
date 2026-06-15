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
        Schema::table('service_categories', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->nullable()->after('description');
        });

        Schema::table('service_sub_categories', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_categories', function (Blueprint $table) {
            $table->dropColumn('price');
        });

        Schema::table('service_sub_categories', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};
