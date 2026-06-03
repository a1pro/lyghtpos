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
            $table->string('handpoint_aid')->nullable()->after('card_transaction_id');
            $table->string('handpoint_arc')->nullable()->after('handpoint_aid');
            $table->string('handpoint_issuer_response_code')->nullable()->after('handpoint_arc');
            $table->string('handpoint_rrn')->nullable()->after('handpoint_issuer_response_code');
            $table->string('handpoint_authorization_code')->nullable()->after('handpoint_rrn');

            // Card Details
            $table->string('handpoint_card_last4')->nullable()->after('handpoint_authorization_code');
            $table->string('handpoint_card_brand')->nullable()->after('handpoint_card_last4');

            $table->string('handpoint_transaction_reference')->nullable()->after('handpoint_card_brand');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchant_orders', function (Blueprint $table) {
            $table->dropColumn([
                'handpoint_aid',
                'handpoint_arc',
                'handpoint_issuer_response_code',
                'handpoint_rrn',
                'handpoint_authorization_code',
                'handpoint_card_last4',
                'handpoint_card_brand',
                'handpoint_transaction_reference',
            ]);
        });
    }
};
