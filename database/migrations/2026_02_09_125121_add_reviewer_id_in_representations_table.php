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
        Schema::table('representations', function (Blueprint $table) {
            if (!Schema::hasColumn('representations', 'reviewer_user_id')) {
                $table->foreignId('reviewer_user_id')
                    ->nullable()
                    ->after('review_message')
                    ->constrained('users') 
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('representations', function (Blueprint $table) {
            $table->dropForeign(['reviewer_user_id']);
            $table->dropColumn(['reviewer_user_id']);
        });
    }
};
