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
            // Downgrade tracking fields
            $table->unsignedBigInteger('downgrade_to_plan_id')->nullable()->after('upgraded_to_plan_id');
            $table->string('downgrade_to_billing_type')->nullable()->after('downgrade_to_plan_id');
            $table->timestamp('downgrade_scheduled_at')->nullable()->after('downgrade_to_billing_type');
            $table->timestamp('downgrade_effective_date')->nullable()->after('downgrade_scheduled_at');
            $table->decimal('refund_amount', 10, 2)->default(0)->after('downgrade_effective_date');
            
            // Add foreign key
            $table->foreign('downgrade_to_plan_id')
                  ->references('id')
                  ->on('subscription_plans')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['downgrade_to_plan_id']);
            $table->dropColumn([
                'downgrade_to_plan_id',
                'downgrade_to_billing_type',
                'downgrade_scheduled_at',
                'downgrade_effective_date',
                'refund_amount'
            ]);
        });
    }
};
