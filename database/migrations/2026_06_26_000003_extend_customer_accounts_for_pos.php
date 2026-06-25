<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_accounts', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('phone', 30)->nullable()->unique()->after('email');
            $table->string('plain_password')->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('customer_accounts', function (Blueprint $table) {
            $table->dropColumn(['phone', 'plain_password']);
            $table->string('email')->nullable(false)->change();
        });
    }
};
