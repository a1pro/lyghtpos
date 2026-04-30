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
            // Drop foreign keys first
            $table->dropForeign(['downgrade_to_plan_id']);
            $table->dropForeign(['upgraded_from_plan_id']);
            $table->dropForeign(['upgraded_to_plan_id']);
            
            // Drop unused columns
            $table->dropColumn([
                'paypal_payment_id',
                'paypal_sale_id',
                'downgrade_to_plan_id',
                'downgrade_to_billing_type',
                'downgrade_scheduled_at',
                'downgrade_effective_date',
                'refund_amount',
                'upgraded_from_plan_id',
                'upgraded_from_subscription_id',
                'upgraded_to_plan_id',
                'upgraded_at',
                'prorated_amount'
            ]);
            
            // Change status enum to include all needed values
            $table->enum('status', ['active', 'expired', 'cancelled', 'superseded', 'trialing'])->default('active')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            // Re-add columns if needed for rollback
            $table->string('paypal_payment_id')->nullable();
            $table->string('paypal_sale_id')->nullable();
            $table->unsignedBigInteger('downgrade_to_plan_id')->nullable();
            $table->string('downgrade_to_billing_type')->nullable();
            $table->timestamp('downgrade_scheduled_at')->nullable();
            $table->timestamp('downgrade_effective_date')->nullable();
            $table->decimal('refund_amount', 10, 2)->default(0);
            $table->unsignedBigInteger('upgraded_from_plan_id')->nullable();
            $table->unsignedBigInteger('upgraded_from_subscription_id')->nullable();
            $table->unsignedBigInteger('upgraded_to_plan_id')->nullable();
            $table->timestamp('upgraded_at')->nullable();
            $table->decimal('prorated_amount', 10, 2)->default(0);
            
            // Re-add foreign keys
            $table->foreign('downgrade_to_plan_id')->references('id')->on('subscription_plans')->onDelete('set null');
            $table->foreign('upgraded_from_plan_id')->references('id')->on('subscription_plans')->onDelete('set null');
            $table->foreign('upgraded_to_plan_id')->references('id')->on('subscription_plans')->onDelete('set null');
        });
    }
};