<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('section_id')
                  ->nullable()
                  ->after('parent_id')
                  ->constrained('sections')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Section::class);
            $table->dropColumn('section_id');
        });
    }
};
