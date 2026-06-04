<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            if (!Schema::hasColumn('quotes', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->after('client_id')->constrained('customers')->nullOnDelete();
                $table->index('customer_id');
            }

            if (!Schema::hasColumn('quotes', 'customer_approved_price')) {
                $table->decimal('customer_approved_price', 12, 2)->nullable()->after('total_amount');
            }

            if (!Schema::hasColumn('quotes', 'customer_signature')) {
                $table->longText('customer_signature')->nullable()->after('client_signature');
            }
        });

        Schema::table('jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('jobs', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->after('client_id')->constrained('customers')->nullOnDelete();
                $table->index('customer_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            if (Schema::hasColumn('quotes', 'customer_id')) {
                $table->dropForeign(['customer_id']);
                $table->dropIndex(['customer_id']);
                $table->dropColumn('customer_id');
            }

            if (Schema::hasColumn('quotes', 'customer_approved_price')) {
                $table->dropColumn('customer_approved_price');
            }

            if (Schema::hasColumn('quotes', 'customer_signature')) {
                $table->dropColumn('customer_signature');
            }
        });

        Schema::table('jobs', function (Blueprint $table) {
            if (Schema::hasColumn('jobs', 'customer_id')) {
                $table->dropForeign(['customer_id']);
                $table->dropIndex(['customer_id']);
                $table->dropColumn('customer_id');
            }
        });
    }
};