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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            
            // Transaction identification
            $table->string('transaction_id', 255)->unique();
            
            // Merchant information
            $table->unsignedBigInteger('merchant_id');
            $table->string('merchant_cyclos_id', 255);
            
            // Customer information
            $table->unsignedBigInteger('customer_id');
            $table->string('customer_cyclos_id', 255);
            
            // Amount fields with fee calculations
            $table->decimal('total_amount', 12, 2);
            $table->decimal('platform_fee', 12, 2)->default(0);
            $table->decimal('amount_after_platform_fee', 12, 2)->default(0);
            $table->decimal('admin_share', 12, 2)->default(0);
            $table->decimal('merchant_share', 12, 2)->default(0);
            $table->decimal('received_amount', 12, 2)->nullable();
            
            // Payment details
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_status', 50)->default('pending');
            
            // JSON data storage
            $table->json('cart_data')->nullable();
            $table->json('payment_response')->nullable();
            $table->json('fee_breakdown')->nullable();
            
            // Timestamps
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('merchant_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
                  
            $table->foreign('customer_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
            
            // Indexes for better query performance
            $table->index(['merchant_id', 'payment_status']);
            $table->index(['customer_id', 'payment_status']);
            $table->index('transaction_id');
            $table->index('created_at');
            $table->index('payment_status');
            $table->index('payment_method');
            $table->index('platform_fee');
            $table->index('merchant_share');
            $table->index('admin_share');
            $table->index('received_amount');
            
            // Composite indexes for reporting
            $table->index(['merchant_id', 'created_at', 'payment_status'], 'idx_merchant_date_status');
            $table->index(['customer_id', 'created_at', 'payment_status'], 'idx_customer_date_status');
            $table->index(['payment_method', 'payment_status'], 'idx_method_status');
            $table->index(['merchant_id', 'payment_method', 'payment_status'], 'idx_merchant_method_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};