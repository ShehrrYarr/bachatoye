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
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('free_delivery')->default(false)->after('show_in_ecom');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('free_delivery')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('free_delivery');
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('free_delivery');
        });
    }
};
