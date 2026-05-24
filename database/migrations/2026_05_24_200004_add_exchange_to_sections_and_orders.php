<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Section-level flag: which sections allow exchange/trade-in
        Schema::table('sections', function (Blueprint $table) {
            $table->boolean('exchange_enabled')->default(false)->after('sort_order');
        });

        // Store exchange trade-in details on the order itself
        Schema::table('orders', function (Blueprint $table) {
            $table->string('exchange_item_name')->nullable()->after('notes');
            $table->decimal('exchange_value', 12, 2)->nullable()->after('exchange_item_name');
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn('exchange_enabled');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['exchange_item_name', 'exchange_value']);
        });
    }
};
