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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->nullable();       // vendor's invoice/bill number
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->date('purchase_date');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->enum('payment_method', ['cash', 'credit', 'partial'])->default('cash');
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->enum('payment_status', ['paid', 'partial', 'unpaid'])->default('paid');
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
        Schema::dropIfExists('purchases');
    }
};
