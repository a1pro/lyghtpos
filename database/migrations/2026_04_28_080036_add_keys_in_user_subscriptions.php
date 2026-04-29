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
            $table->unsignedBigInteger('upgraded_from_plan_id')->nullable()->after('plan_id');
            $table->unsignedBigInteger('upgraded_from_subscription_id')->nullable()->after('upgraded_from_plan_id');
            $table->unsignedBigInteger('upgraded_to_plan_id')->nullable()->after('upgraded_from_subscription_id');
            $table->timestamp('upgraded_at')->nullable()->after('upgraded_to_plan_id');
            $table->decimal('prorated_amount', 10, 2)->default(0)->after('upgraded_at');
            $table->enum('status', ['active', 'expired', 'cancelled', 'superseded', 'upgraded'])->default('active')->change();
            
            // Add foreign keys if needed
            $table->foreign('upgraded_from_plan_id')->references('id')->on('subscription_plans')->onDelete('set null');
            $table->foreign('upgraded_to_plan_id')->references('id')->on('subscription_plans')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
             $table->dropForeign(['upgraded_from_plan_id']);
            $table->dropForeign(['upgraded_to_plan_id']);
            $table->dropColumn([
                'upgraded_from_plan_id',
                'upgraded_from_subscription_id',
                'upgraded_to_plan_id',
                'upgraded_at',
                'prorated_amount'
            ]);
        });
    }
};
