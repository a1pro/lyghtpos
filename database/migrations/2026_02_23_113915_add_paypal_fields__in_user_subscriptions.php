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
        Schema::table('user_subscriptions', function (Blueprint $table) {
            
            $table->string('payment_status')->default('pending')->after('payment_method');
            $table->string('paypal_payment_id')->nullable()->after('payment_status');
            $table->string('paypal_payer_id')->nullable()->after('paypal_payment_id');
            $table->string('paypal_sale_id')->nullable()->after('paypal_payer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'payment_status',
                'paypal_payment_id',
                'paypal_payer_id',
                'paypal_sale_id'
            ]);
        });
    }
};
