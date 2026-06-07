<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Each product independently chooses which attribute definition is shown on store
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('primary_serial_attribute_id')
                  ->nullable()
                  ->after('is_serialized')
                  ->constrained('serial_attribute_definitions')
                  ->nullOnDelete();
        });

        // Remove the old global "one primary for everything" flag
        Schema::table('serial_attribute_definitions', function (Blueprint $table) {
            $table->dropColumn('is_primary');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['primary_serial_attribute_id']);
            $table->dropColumn('primary_serial_attribute_id');
        });

        Schema::table('serial_attribute_definitions', function (Blueprint $table) {
            $table->boolean('is_primary')->default(false)->after('is_active');
        });
    }
};
