<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Extend the enum to include 'exchange' as a valid refund method
        DB::statement("ALTER TABLE `returns` MODIFY `refund_method` ENUM('cash', 'khata_credit', 'bank_transfer', 'exchange') NOT NULL DEFAULT 'cash'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `returns` MODIFY `refund_method` ENUM('cash', 'khata_credit', 'bank_transfer') NOT NULL DEFAULT 'cash'");
    }
};
