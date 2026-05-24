<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['expense_category_id']);
            $table->unsignedBigInteger('expense_category_id')->nullable()->change();
            $table->foreign('expense_category_id')->references('id')->on('expense_categories')->nullOnDelete();

            $table->enum('payment_method', ['cash', 'bank_transfer', 'card', 'other'])
                  ->default('cash')
                  ->after('expense_date');

            $table->text('notes')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'notes']);
            $table->dropForeign(['expense_category_id']);
            $table->unsignedBigInteger('expense_category_id')->nullable(false)->change();
            $table->foreign('expense_category_id')->references('id')->on('expense_categories')->restrictOnDelete();
        });
    }
};
