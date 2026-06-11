<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Extend the type enum to include bundle_free
        DB::statement("ALTER TABLE deals MODIFY COLUMN type ENUM('percentage','flat','buy_x_get_y','bundle_free') NOT NULL");

        // Add role column to deal_products so we can distinguish buy vs free products
        Schema::table('deal_products', function (Blueprint $table) {
            $table->enum('role', ['buy', 'free'])->default('buy')->after('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('deal_products', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        DB::statement("ALTER TABLE deals MODIFY COLUMN type ENUM('percentage','flat','buy_x_get_y') NOT NULL");
    }
};
