<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('serial_number_id')
                  ->nullable()
                  ->after('quantity')
                  ->constrained('serial_numbers')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['serial_number_id']);
            $table->dropColumn('serial_number_id');
        });
    }
};
