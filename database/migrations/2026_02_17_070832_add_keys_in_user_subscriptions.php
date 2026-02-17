<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('user_subscriptions', 'billing_type')) {
                $table->enum('billing_type', ['monthly', 'yearly'])->after('plan_id');
            }
            if (!Schema::hasColumn('user_subscriptions', 'amount_paid')) {
                $table->decimal('amount_paid', 10, 2)->nullable()->after('transaction_id');
            }
            if (!Schema::hasColumn('user_subscriptions', 'status')) {
                $table->string('status')->default('active')->after('amount_paid');
            }
            // Make transaction_id unique if not already
            if (Schema::hasColumn('user_subscriptions', 'transaction_id')) {
                $table->string('transaction_id')->unique()->change();
            }
        });
    }

    public function down()
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['billing_type', 'amount_paid', 'status']);
        });
    }
};
