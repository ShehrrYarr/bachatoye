<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts_ledger', function (Blueprint $table) {
            $table->timestamp('edited_at')->nullable()->after('user_id');
            $table->foreignId('edited_by')->nullable()->after('edited_at')
                  ->constrained('users')->nullOnDelete();
        });

        Schema::table('vendor_ledger', function (Blueprint $table) {
            $table->timestamp('edited_at')->nullable()->after('created_by');
            $table->foreignId('edited_by')->nullable()->after('edited_at')
                  ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('accounts_ledger', function (Blueprint $table) {
            $table->dropConstrainedForeignId('edited_by');
            $table->dropColumn('edited_at');
        });

        Schema::table('vendor_ledger', function (Blueprint $table) {
            $table->dropConstrainedForeignId('edited_by');
            $table->dropColumn('edited_at');
        });
    }
};
