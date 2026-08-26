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
            $table->string('procharge_authorization_number')->nullable()->after('handpoint_fin_status');
            $table->string('procharge_card_token')->nullable()->after('procharge_authorization_number');
            $table->string('procharge_transaction_id')->nullable()->after('procharge_card_token');
            $table->string('procharge_invoice_id')->nullable()->after('procharge_transaction_id');
            $table->json('procharge_response')->nullable()->after('procharge_invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchant_orders', function (Blueprint $table) {
            $table->dropColumn([
                'procharge_authorization_number',
                'procharge_card_token',
                'procharge_transaction_id',
                'procharge_invoice_id',
                'procharge_response',
            ]);
        });
    }
};
