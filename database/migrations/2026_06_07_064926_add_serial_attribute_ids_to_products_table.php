<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Which attribute fields are relevant when entering serial/IMEI numbers for this product.
        // NULL / empty array = show ALL active attributes (backward-compatible default).
        Schema::table('products', function (Blueprint $table) {
            $table->json('serial_attribute_ids')->nullable()->after('primary_serial_attribute_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('serial_attribute_ids');
        });
    }
};
