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
        Schema::table('merchant_orders', function (Blueprint $table) {
            $table->string('refund_handpoint_card_txn_id')->nullable()->after('refund_cyclos_token_response');
            $table->json('refund_handpoint_card_response')->nullable()->after('refund_handpoint_card_txn_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchant_orders', function (Blueprint $table) {
            $table->dropColumn([
                'refund_handpoint_card_txn_id',
                'refund_handpoint_card_response',
            ]);
        });
    }
};
