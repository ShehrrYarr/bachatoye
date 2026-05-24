<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->enum('source', ['ecommerce', 'pos'])->default('ecommerce');

            // Customer info (inline for guest orders; linked for known customers)
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone', 20);
            $table->string('customer_email')->nullable();

            // Delivery
            $table->text('delivery_address')->nullable();
            $table->string('city', 100)->nullable();
            $table->text('delivery_notes')->nullable();

            // Amounts
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('delivery_charge', 12, 2)->default(0);
            $table->decimal('total', 12, 2);

            // Payment
            $table->enum('payment_method', ['cash', 'bank_transfer', 'khata'])->default('cash');
            $table->enum('payment_status', ['pending', 'paid', 'partial'])->default('pending');
            $table->string('payment_proof')->nullable(); // uploaded bank transfer image
            $table->text('payment_notes')->nullable();

            // Status
            $table->enum('status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'returned'])
                  ->default('pending');
            $table->string('tracking_number')->nullable();

            // POS specific
            $table->foreignId('served_by')->nullable()->constrained('users')->nullOnDelete();

            // Deal applied
            $table->foreignId('deal_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');       // snapshot at time of sale
            $table->string('product_barcode')->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->integer('quantity');
            $table->integer('free_quantity')->default(0); // for buy_x_get_y
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
