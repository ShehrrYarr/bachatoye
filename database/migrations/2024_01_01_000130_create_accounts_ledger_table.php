<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['debit', 'credit']); // debit = customer owes us, credit = we owe them/they paid
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('description');
            $table->string('reference')->nullable(); // order_number, return_number, etc.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // who entered it
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts_ledger');
    }
};
