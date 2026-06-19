<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('delivery_platform_id')
                  ->nullable()->after('vendor_id')
                  ->constrained('delivery_platforms')->nullOnDelete();
            $table->enum('cod_status', ['pending', 'settled'])->nullable()->after('delivery_platform_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['delivery_platform_id']);
            $table->dropColumn(['delivery_platform_id', 'cod_status']);
        });
    }
};
