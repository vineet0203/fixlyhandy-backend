<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->onDelete('cascade');
            
            // Line Item Details
            $table->string('item_name');
            $table->text('description')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('item_total', 12, 2)->default(0);
            
            
            // Package reference (for selected packages)
            $table->foreignId('package_id')->nullable()->constrained('packages')->onDelete('set null');
            
            // Ordering
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index('quote_id');
            $table->index('package_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_items');
    }
};