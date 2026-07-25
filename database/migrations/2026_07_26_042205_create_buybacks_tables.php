<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buybacks', function (Blueprint $table) {
            $table->id();
            $table->string('buyback_number')->unique();
            // NULL = main shop — the shop performing the buyback, not necessarily
            // where the unit was originally sold.
            $table->foreignId('shop_id')->nullable()->constrained('shops')->nullOnDelete();

            $table->string('seller_name');
            $table->string('seller_phone')->nullable();
            $table->string('seller_cnic')->nullable();
            // Optional match against an existing Customer/Vendor record, purely for
            // audit/traceability — never required, never blocks the transaction.
            $table->foreignId('seller_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('seller_vendor_id')->nullable()->constrained('vendors')->nullOnDelete();

            $table->decimal('amount_total', 12, 2)->default(0);
            $table->enum('payment_method', ['cash', 'bank_transfer']);
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['shop_id', 'created_at']);
        });

        Schema::create('buyback_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyback_id')->constrained('buybacks')->cascadeOnDelete();

            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_name');
            $table->foreignId('product_color_id')->nullable()->constrained('product_colors')->nullOnDelete();
            $table->string('color_name')->nullable();
            $table->foreignId('serial_number_id')->nullable()->constrained('serial_numbers')->nullOnDelete();

            // Snapshot of the last sale, captured before the serial is reactivated,
            // so provenance stays visible even after order_id is cleared.
            $table->foreignId('original_order_item_id')->nullable()->constrained('order_items')->nullOnDelete();

            $table->decimal('price_paid', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buyback_items');
        Schema::dropIfExists('buybacks');
    }
};
