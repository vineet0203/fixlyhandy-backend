<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employees')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'name')) {
                $table->string('name')->nullable()->after('employee_id');
            }

            if (!Schema::hasColumn('employees', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }

            if (!Schema::hasColumn('employees', 'password')) {
                $table->string('password')->nullable()->after('phone');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('employees')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'password')) {
                $table->dropColumn('password');
            }

            if (Schema::hasColumn('employees', 'phone')) {
                $table->dropColumn('phone');
            }

            if (Schema::hasColumn('employees', 'name')) {
                $table->dropColumn('name');
            }
        });
    }
};
