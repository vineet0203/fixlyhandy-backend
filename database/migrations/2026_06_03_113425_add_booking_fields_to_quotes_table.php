<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('booking_location')->nullable()->after('notes');
            $table->date('booking_date')->nullable()->after('booking_location');
            $table->string('booking_time', 50)->nullable()->after('booking_date');
        });
    }
    public function down(): void {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['booking_location', 'booking_date', 'booking_time']);
        });
    }
};
