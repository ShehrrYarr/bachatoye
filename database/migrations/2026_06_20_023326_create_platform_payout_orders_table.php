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
        Schema::create('platform_payout_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_payout_id')->constrained('platform_payouts')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->decimal('order_amount', 12, 2);
            $table->timestamps();
            $table->unique(['platform_payout_id', 'order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_payout_orders');
    }
};
