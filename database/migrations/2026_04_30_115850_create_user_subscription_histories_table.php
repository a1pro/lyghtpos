<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserSubscriptionHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('user_subscription_histories', function (Blueprint $table) {
            $table->id();
            
            // User information
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('plan_id');
            $table->unsignedBigInteger('user_subscription_id')->nullable();
            
            // Subscription details
            $table->enum('billing_type', ['monthly', 'yearly'])->default('monthly');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            
            // Payment information
            $table->string('transaction_id')->nullable();
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('payment_method')->default('paypal');
            
            // Status and tracking
            $table->enum('status', ['active', 'expired', 'cancelled', 'changed'])->default('active');
            $table->unsignedBigInteger('changed_to_plan_id')->nullable();
            $table->timestamp('changed_at')->nullable();
            
            // Additional info
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes for better performance
            $table->index('user_id');
            $table->index('plan_id');
            $table->index('user_subscription_id');
            $table->index('status');
            $table->index('billing_type');
            $table->index('transaction_id');
            $table->index('created_at');
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at']);
            
            // Foreign keys
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
                  
            $table->foreign('plan_id')
                  ->references('id')
                  ->on('subscription_plans')
                  ->onDelete('restrict');
                  
            $table->foreign('user_subscription_id')
                  ->references('id')
                  ->on('user_subscriptions')
                  ->onDelete('set null');
                  
            $table->foreign('changed_to_plan_id')
                  ->references('id')
                  ->on('subscription_plans')
                  ->onDelete('set null');
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('user_subscription_histories');
    }
}