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
        Schema::create('handpoint_transaction_logs', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->unsignedBigInteger('merchant_order_id')->nullable();
            $table->unsignedBigInteger('merchant_id');
            $table->unsignedBigInteger('customer_id')->nullable();

            // Transaction Identifiers
            $table->string('transaction_id')->unique();
            $table->string('transaction_reference')->nullable()->index();

            // Transaction Status
            $table->string('status'); // success, failed, pending, authorised, declined
            $table->string('fin_status')->nullable(); // AUTHORISED, DECLINED, etc.
            $table->string('status_message')->nullable();

            // Amount Details
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('tip_amount', 10, 2)->nullable();
            $table->decimal('approved_amount', 10, 2)->nullable();
            $table->decimal('requested_amount', 10, 2)->nullable();

            // EMV & Card Details
            $table->string('aid')->nullable(); // Application Identifier
            $table->string('arc')->nullable(); // Authorization Response Code
            $table->string('issuer_response_code')->nullable();
            $table->string('rrn')->nullable()->index(); // Retrieval Reference Number
            $table->string('authorization_code')->nullable();
            $table->string('card_last4', 4)->nullable();
            $table->string('card_brand')->nullable();
            $table->string('masked_card_number')->nullable();
            $table->string('card_expiry')->nullable();
            $table->string('tender_type')->nullable(); // CREDIT, DEBIT, etc.

            // Terminal Details
            $table->string('device_serial')->nullable()->index();
            $table->string('device_status_message')->nullable();
            $table->string('device_battery_status')->nullable();

            // Receipt URLs
            $table->text('merchant_receipt_url')->nullable();
            $table->text('customer_receipt_url')->nullable();

            // Flags
            $table->boolean('is_partial_approval')->default(false);
            $table->boolean('is_recovered')->default(false);
            $table->boolean('is_reversal_attempted')->default(false);

            // Raw Response (JSON)
            $table->json('raw_response')->nullable();
            $table->json('additional_data')->nullable();

            // Timestamps
            $table->timestamp('transaction_timestamp')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['merchant_id']);

            // Foreign keys
            $table->foreign('merchant_order_id')->references('id')->on('merchant_orders')->onDelete('set null');
            $table->foreign('merchant_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('handpoint_transaction_logs');
    }
};
