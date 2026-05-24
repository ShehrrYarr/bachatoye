<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->enum('type', ['percentage', 'flat', 'buy_x_get_y']);
            $table->decimal('value', 10, 2)->default(0);          // % or flat amount
            $table->integer('buy_quantity')->nullable();           // for buy_x_get_y
            $table->integer('get_quantity')->nullable();           // for buy_x_get_y
            $table->enum('applies_to', ['all', 'products', 'categories'])->default('all');
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('deal_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
        });

        Schema::create('deal_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_categories');
        Schema::dropIfExists('deal_products');
        Schema::dropIfExists('deals');
    }
};
