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
        Schema::create('token_bundles', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('tokens')->default(0);

            $table->decimal('base_amount', 10, 2)->default(0);
            $table->decimal('system_fee', 10, 2)->default(0);
            $table->boolean('discounted')->default(false);

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_bundles');
    }
};
