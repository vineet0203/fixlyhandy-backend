<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('employee_id');
            $table->string('category', 100);
            $table->string('location_id', 120);
            $table->date('booking_date');
            $table->string('booking_time', 40);
            $table->string('client_name', 255);
            $table->string('email', 255);
            $table->string('mobile', 20);
            $table->string('service_address', 500);
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('payment_status', 30);
            $table->string('payment_method', 30);
            $table->string('transaction_id', 120)->unique();
            $table->timestamps();

            $table->index('vendor_id');
            $table->index('customer_id');
            $table->index('employee_id');
            $table->index('booking_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
