<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Client-generated reference for offline POS sales. Lets the sync
            // be idempotent — if a sale's success response is lost, retrying
            // with the same ref returns the existing order instead of duplicating.
            $table->string('offline_ref')->nullable()->after('order_number')->index();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('offline_ref');
        });
    }
};
