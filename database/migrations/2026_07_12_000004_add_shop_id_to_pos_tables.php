<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('shop_id')->nullable()->after('id')
                ->constrained('shops')->nullOnDelete();
            $table->index(['shop_id', 'created_at']);
        });

        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->foreignId('shop_id')->nullable()->after('id')
                ->constrained('shops')->nullOnDelete();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('shop_id')->nullable()->after('id')
                ->constrained('shops')->nullOnDelete();
        });

        Schema::table('held_orders', function (Blueprint $table) {
            $table->foreignId('shop_id')->nullable()->after('id')
                ->constrained('shops')->nullOnDelete();
        });

        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->foreignId('shop_id')->nullable()->after('id')
                ->constrained('shops')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['shop_id']);
            $table->dropIndex(['shop_id', 'created_at']);
            $table->dropColumn('shop_id');
        });

        foreach (['bank_accounts', 'expenses', 'held_orders', 'pos_sessions'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['shop_id']);
                $table->dropColumn('shop_id');
            });
        }
    }
};
