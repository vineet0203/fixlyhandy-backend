<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('bill_date');
            $table->date('delivery_date')->nullable();
            $table->date('payment_deadline')->nullable();
            $table->decimal('mileage', 12, 2)->default(0);
            $table->decimal('other_expense', 12, 2)->default(0);
            $table->decimal('vat', 8, 2)->default(0);
            $table->text('note')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->json('billing_address')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamps();

            $table->index(['employee_id', 'bill_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
