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
        Schema::create('procharge_transaction_logs', function (Blueprint $table) {
            $table->id();
            
            $table->string('transaction_identifier')->nullable();
            $table->string('authorization_number')->nullable();
            $table->string('invoice_id')->nullable();
            $table->string('payment_id')->nullable();
            $table->string('batch_id')->nullable();
            $table->string('batch_number')->nullable();
            
            $table->string('card_type')->nullable();
            $table->string('card_last_four')->nullable();
            $table->string('card_scheme')->nullable();
            
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('currency')->default('USD');
            
            $table->integer('response_code')->nullable();
            $table->string('response_text')->nullable();
            $table->string('network_response_code')->nullable();
            $table->string('host_response')->nullable();
            
            $table->string('card_token')->nullable(); // The token used
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            
            $table->string('status')->default('pending'); // pending, approved, declined, failed
            
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            
            $table->string('environment')->default('sandbox');
            
            $table->timestamps();
            
            $table->index(['created_at']);
            $table->index(['transaction_identifier']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procharge_transaction_logs');
    }
};
