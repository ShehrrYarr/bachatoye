<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One order per offline sale: two syncs racing past the idempotency check in
 * PosController::createOrder() now fail on this index instead of saving the
 * sale twice. NULLs (online sales) don't collide in a MySQL unique index.
 * Existing duplicates are renamed by the previous migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['offline_ref']);
            $table->unique('offline_ref');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['offline_ref']);
            $table->index('offline_ref');
        });
    }
};
