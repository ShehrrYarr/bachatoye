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
        Schema::table('serial_attribute_definitions', function (Blueprint $table) {
            $table->boolean('is_primary')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('serial_attribute_definitions', function (Blueprint $table) {
            $table->dropColumn('is_primary');
        });
    }
};
