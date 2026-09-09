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
            $table->string('refund_mode')->nullable()->after('payment_status');
            $table->enum('refund_status', ['pending', 'refunded', 'failed'])->nullable()->after('refund_mode');
            $table->text('refund_notes')->nullable()->after('refund_status');

            // Procharge refund fields
            $table->string('refund_procharge_txn_id')->nullable()->after('refund_notes');
            $table->json('refund_procharge_response')->nullable()->after('refund_procharge_txn_id');

            // Cyclos Token refund fields
            $table->string('refund_cyclos_token_txn_id')->nullable()->after('refund_procharge_response');
            $table->json('refund_cyclos_token_response')->nullable()->after('refund_cyclos_token_txn_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchant_orders', function (Blueprint $table) {
            $table->dropColumn([
                'refund_mode',
                'refund_status',
                'refund_notes',
                'refund_procharge_txn_id',
                'refund_procharge_response',
                'refund_cyclos_token_txn_id',
                'refund_cyclos_token_response',
            ]);
        });
    }
};
