<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Unique: a sub shop has exactly one login. NULLs (main-shop staff) are
            // exempt from single-column unique indexes in MySQL.
            $table->foreignId('shop_id')->nullable()->after('is_active')
                ->constrained('shops')->nullOnDelete();
            $table->unique('shop_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['shop_id']);
            $table->dropUnique(['shop_id']);
            $table->dropColumn('shop_id');
        });
    }
};
