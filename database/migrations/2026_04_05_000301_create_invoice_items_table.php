<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('job_id')->nullable()->constrained('jobs')->nullOnDelete();
            $table->string('job_name');
            $table->decimal('mileage', 12, 2)->default(0);
            $table->decimal('other_expense', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('vat', 8, 2)->default(0);
            $table->decimal('final_amount', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['invoice_id', 'job_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
