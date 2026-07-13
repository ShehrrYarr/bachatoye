<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // NOTE: shop_id is the base of the stored generated column shop_key, so
        // MySQL forbids SET NULL / CASCADE on its foreign key — RESTRICT only.
        // (Shop deletion isn't exposed in the app; shops are deactivated instead.)
        if (!Schema::hasColumn('customers', 'shop_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->unsignedBigInteger('shop_id')->nullable()->after('id');
            });
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->foreign('shop_id')->references('id')->on('shops');
            // MySQL composite uniques treat NULL as distinct, which would let the
            // same phone repeat within the main shop. shop_key collapses NULL to 0
            // so uniqueness holds per shop including main.
            $table->unsignedBigInteger('shop_key')->storedAs('COALESCE(shop_id, 0)');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_phone_unique');
            $table->unique(['phone', 'shop_key']);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['phone', 'shop_key']);
            $table->unique('phone');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('shop_key');
            $table->dropForeign(['shop_id']);
            $table->dropColumn('shop_id');
        });
    }
};
