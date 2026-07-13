<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number')->unique();
            // NULL = main shop on either side; exactly one side may be NULL.
            $table->foreignId('from_shop_id')->nullable()->constrained('shops')->nullOnDelete();
            $table->foreignId('to_shop_id')->nullable()->constrained('shops')->nullOnDelete();
            $table->text('note')->nullable();
            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('total_qty')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_name');
            $table->foreignId('product_color_id')->nullable()->constrained('product_colors')->nullOnDelete();
            $table->string('color_name')->nullable();
            $table->foreignId('serial_number_id')->nullable()->constrained('serial_numbers')->nullOnDelete();
            $table->string('serial_code')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
    }
};
