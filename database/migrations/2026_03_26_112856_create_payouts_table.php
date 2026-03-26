<?php
// database/migrations/2026_03_26_120000_create_payouts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id');
            $table->string('payout_reference', 100)->unique();
            $table->decimal('amount', 12, 2);
            $table->decimal('fee', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2);
            $table->string('payment_method', 50); // bank_transfer, paypal, stripe, etc.
            $table->json('payment_details')->nullable();
            $table->string('status', 50)->default('pending'); // pending, processing, completed, failed, cancelled
            $table->text('notes')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
            
            $table->foreign('merchant_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
            
            $table->index(['merchant_id', 'status']);
            $table->index('payout_reference');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};