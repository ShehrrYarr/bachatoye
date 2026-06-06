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
        Schema::table('serial_numbers', function (Blueprint $table) {
            $table->decimal('cost_price', 12, 2)->nullable()->after('serial_number');
            $table->decimal('selling_price', 12, 2)->nullable()->after('cost_price');
            $table->json('attributes')->nullable()->after('selling_price');
        });
    }

    public function down(): void
    {
        Schema::table('serial_numbers', function (Blueprint $table) {
            $table->dropColumn(['cost_price', 'selling_price', 'attributes']);
        });
    }
};
