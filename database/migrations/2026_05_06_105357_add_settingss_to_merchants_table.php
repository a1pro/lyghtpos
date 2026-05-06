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
        Schema::table('merchants', function (Blueprint $table) {
            $table->decimal('vat_tax_percentage', 5, 2)->default(10.00)->after('website');
            $table->json('pos_payment_types')->nullable()->after('vat_tax_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn(['vat_tax_percentage', 'pos_payment_types']);
        });
    }
};
