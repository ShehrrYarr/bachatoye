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
        Schema::create('serial_attribute_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name');                   // e.g. "PTA Status", "Condition"
            $table->json('options');                  // e.g. ["PTA Approved","Non-PTA","Factory Unlocked"]
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serial_attribute_definitions');
    }
};
