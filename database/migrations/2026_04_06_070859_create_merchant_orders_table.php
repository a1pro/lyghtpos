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
        Schema::create('merchant_orders', function (Blueprint $table) {
            $table->id();

            $table->string('order_number')->unique();
            $table->string('merchant_id')->nullable();

            $table->string('customer_id')->nullable();
            $table->string('customer_cyclos_id')->nullable();

            $table->decimal('total_amount', 10, 2);
            
            $table->enum('payment_method', ['cash', 'unyti', 'pax', 'pos_mobile']);
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');

            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_orders');
    }
};
