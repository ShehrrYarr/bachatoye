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
        Schema::create('product_attribute_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('serial_attribute_definition_id')->constrained()->cascadeOnDelete();
            $table->string('option_value');
            $table->decimal('price', 12, 2);
            $table->timestamps();
            $table->unique(['product_id', 'serial_attribute_definition_id', 'option_value'], 'pap_product_attr_option_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_attribute_prices');
    }
};
