<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            // No FK: product_color_id is the base of the stored generated color_key,
            // so MySQL would forbid CASCADE — and colors can be deleted in the app.
            $table->unsignedBigInteger('product_color_id')->nullable()->index();
            // NULL color collapses to 0 so the composite unique also covers no-color rows.
            $table->unsignedBigInteger('color_key')->storedAs('COALESCE(product_color_id, 0)');
            $table->integer('quantity')->default(0);
            $table->timestamps();

            $table->unique(['shop_id', 'product_id', 'color_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_stocks');
    }
};
