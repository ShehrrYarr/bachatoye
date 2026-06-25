<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_accounts', function (Blueprint $table) {
            $table->string('security_question')->nullable()->change();
            $table->string('security_answer')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('customer_accounts', function (Blueprint $table) {
            $table->string('security_question')->nullable(false)->change();
            $table->string('security_answer')->nullable(false)->change();
        });
    }
};
