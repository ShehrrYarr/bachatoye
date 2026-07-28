<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ledger descriptions embed a full item list (product names x qty), which
     * routinely exceeds VARCHAR(255) on orders with many line items and
     * truncates the whole INSERT with a fatal SQL error. TEXT removes the cap.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE vendor_ledger MODIFY description TEXT NULL");
        DB::statement("ALTER TABLE accounts_ledger MODIFY description TEXT NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE vendor_ledger MODIFY description VARCHAR(255) NULL");
        DB::statement("ALTER TABLE accounts_ledger MODIFY description VARCHAR(255) NOT NULL");
    }
};
