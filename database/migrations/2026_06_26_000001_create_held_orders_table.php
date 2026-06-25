<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('held_orders', function (Blueprint $table) {
            $table->id();
            $table->string('label', 100);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->json('cart_data');
            $table->json('customer_data')->nullable();
            $table->string('discount_type', 20)->default('flat');
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->string('payment_method', 50)->default('cash');
            $table->text('order_notes')->nullable();
            $table->decimal('total', 10, 2)->default(0);
            $table->json('section_ids')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('held_orders');
    }
};
