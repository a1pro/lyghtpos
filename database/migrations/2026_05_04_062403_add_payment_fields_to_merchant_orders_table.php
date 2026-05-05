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
        Schema::table('merchant_orders', function (Blueprint $table) {
            $table->decimal('tax_amount', 10, 2)->default(0)->after('total_amount');
            $table->decimal('card_amount', 10, 2)->nullable()->after('tax_amount');
            $table->decimal('token_amount', 10, 2)->nullable()->after('card_amount');

            $table->enum('payment_type', ['full_card', 'full_tokens', 'split'])->nullable()->after('payment_method');

            $table->string('card_transaction_id')->nullable()->after('payment_type');
            $table->string('cyclos_transaction_id')->nullable()->after('card_transaction_id');

            $table->json('payment_metadata')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchant_orders', function (Blueprint $table) {
            $table->dropColumn([
                'tax_amount',
                'card_amount',
                'token_amount',
                'payment_type',
                'card_transaction_id',
                'cyclos_transaction_id',
                'payment_metadata',
            ]);
        });
    }
};
