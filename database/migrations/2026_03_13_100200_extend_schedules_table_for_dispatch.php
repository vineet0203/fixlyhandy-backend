<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('schedules')) {
            return;
        }

        Schema::table('schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('schedules', 'dispatch_crew_id')) {
                $table->foreignId('dispatch_crew_id')->nullable()->after('crew_id')->constrained('crews')->nullOnDelete();
            }

            if (!Schema::hasColumn('schedules', 'employee_id')) {
                $table->foreignId('employee_id')->nullable()->after('dispatch_crew_id')->constrained('employees')->nullOnDelete();
            }

            if (!Schema::hasColumn('schedules', 'title')) {
                $table->string('title')->nullable()->after('employee_id');
            }

            if (!Schema::hasColumn('schedules', 'schedule_date')) {
                $table->date('schedule_date')->nullable()->after('title');
            }

            if (!Schema::hasColumn('schedules', 'start_time')) {
                $table->time('start_time')->nullable()->after('schedule_date');
            }

            if (!Schema::hasColumn('schedules', 'end_time')) {
                $table->time('end_time')->nullable()->after('start_time');
            }

            if (!Schema::hasColumn('schedules', 'location_lat')) {
                $table->decimal('location_lat', 10, 7)->nullable()->after('end_time');
            }

            if (!Schema::hasColumn('schedules', 'location_lng')) {
                $table->decimal('location_lng', 10, 7)->nullable()->after('location_lat');
            }

            if (!Schema::hasColumn('schedules', 'address')) {
                $table->text('address')->nullable()->after('location_lng');
            }
        });

        // Keep legacy values while supporting new dispatch statuses.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE schedules MODIFY status ENUM('draft','scheduled','pending','assigned','in_progress','completed','cancelled') DEFAULT 'pending'");
        }

        DB::table('schedules')
            ->whereNull('schedule_date')
            ->whereNotNull('start_datetime')
            ->update(['schedule_date' => DB::raw('DATE(start_datetime)')]);

        DB::table('schedules')
            ->whereNull('start_time')
            ->whereNotNull('start_datetime')
            ->update(['start_time' => DB::raw('TIME(start_datetime)')]);

        DB::table('schedules')
            ->whereNull('end_time')
            ->whereNotNull('end_datetime')
            ->update(['end_time' => DB::raw('TIME(end_datetime)')]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('schedules')) {
            return;
        }

        Schema::table('schedules', function (Blueprint $table) {
            if (Schema::hasColumn('schedules', 'dispatch_crew_id')) {
                $table->dropConstrainedForeignId('dispatch_crew_id');
            }
            if (Schema::hasColumn('schedules', 'employee_id')) {
                $table->dropConstrainedForeignId('employee_id');
            }
            if (Schema::hasColumn('schedules', 'title')) {
                $table->dropColumn('title');
            }
            if (Schema::hasColumn('schedules', 'schedule_date')) {
                $table->dropColumn('schedule_date');
            }
            if (Schema::hasColumn('schedules', 'start_time')) {
                $table->dropColumn('start_time');
            }
            if (Schema::hasColumn('schedules', 'end_time')) {
                $table->dropColumn('end_time');
            }
            if (Schema::hasColumn('schedules', 'location_lat')) {
                $table->dropColumn('location_lat');
            }
            if (Schema::hasColumn('schedules', 'location_lng')) {
                $table->dropColumn('location_lng');
            }
            if (Schema::hasColumn('schedules', 'address')) {
                $table->dropColumn('address');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE schedules MODIFY status ENUM('draft','scheduled','completed','cancelled') DEFAULT 'draft'");
        }
    }
};
