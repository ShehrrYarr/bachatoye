<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('vendor_id')->nullable()->after('customer_id')
                  ->constrained()->nullOnDelete();
        });

        Schema::table('vendor_ledger', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('purchase_id')
                  ->constrained('orders')->nullOnDelete();
            $table->string('reference')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeignIfExists(['vendor_id']);
            $table->dropColumnIfExists('vendor_id');
        });

        Schema::table('vendor_ledger', function (Blueprint $table) {
            $table->dropForeignIfExists(['order_id']);
            $table->dropColumnIfExists('order_id');
            $table->dropColumnIfExists('reference');
        });
    }
};
