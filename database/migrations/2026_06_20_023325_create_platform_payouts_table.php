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
        Schema::create('platform_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_platform_id')->constrained('delivery_platforms');
            $table->foreignId('bank_account_id')->constrained('bank_accounts');
            $table->decimal('amount', 12, 2);
            $table->date('payout_date');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_payouts');
    }
};
