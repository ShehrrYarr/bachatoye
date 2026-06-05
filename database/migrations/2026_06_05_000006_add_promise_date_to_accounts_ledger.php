<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts_ledger', function (Blueprint $table) {
            $table->date('promise_date')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('accounts_ledger', function (Blueprint $table) {
            $table->dropColumn('promise_date');
        });
    }
};
