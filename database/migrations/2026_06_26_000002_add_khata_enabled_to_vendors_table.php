<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->boolean('khata_enabled')->default(false)->after('notes');
        });

        // Existing vendors already have ledger activity — enable khata for them
        DB::statement('UPDATE vendors SET khata_enabled = 1');
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('khata_enabled');
        });
    }
};
