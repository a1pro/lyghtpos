<?php
// database/migrations/2026_03_26_142039_add_fields_in_payouts.php

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
        Schema::table('payouts', function (Blueprint $table) {
            // First add processed_notes column
            if (!Schema::hasColumn('payouts', 'processed_notes')) {
                $table->text('processed_notes')->nullable()->after('failure_reason');
            }
            
            // Then add processed_by column after processed_notes
            if (!Schema::hasColumn('payouts', 'processed_by')) {
                $table->unsignedBigInteger('processed_by')->nullable()->after('processed_notes');
                $table->foreign('processed_by')
                      ->references('id')
                      ->on('users')
                      ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            if (Schema::hasColumn('payouts', 'processed_by')) {
                $table->dropForeign(['processed_by']);
                $table->dropColumn('processed_by');
            }
            
            if (Schema::hasColumn('payouts', 'processed_notes')) {
                $table->dropColumn('processed_notes');
            }
        });
    }
};