<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('serial_numbers', function (Blueprint $table) {
            $table->foreignId('shop_id')->nullable()->after('product_id')
                ->constrained('shops')->nullOnDelete();
            $table->index(['shop_id', 'status']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('shop_id')->nullable()->after('product_id')
                ->constrained('shops')->nullOnDelete();
        });

        DB::statement("ALTER TABLE stock_movements MODIFY type ENUM('purchase', 'sale', 'return', 'adjustment', 'damage', 'transfer_out', 'transfer_in') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE stock_movements MODIFY type ENUM('purchase', 'sale', 'return', 'adjustment', 'damage') NOT NULL");

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['shop_id']);
            $table->dropColumn('shop_id');
        });

        Schema::table('serial_numbers', function (Blueprint $table) {
            $table->dropForeign(['shop_id']);
            $table->dropIndex(['shop_id', 'status']);
            $table->dropColumn('shop_id');
        });
    }
};
