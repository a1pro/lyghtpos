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
            $table->enum('status', ['pending', 'approved' , 'rejected', 'hold'])->nullable()->default('pending')->after('currency');
            $table->timestamp('last_reviewed')->nullable()->after('status');
            $table->text('review_message')->nullable()->after('last_reviewed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('representations', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'last_reviewed',
                'review_message',
            ]);
        });
    }
};
