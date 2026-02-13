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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('badge_color')->default('secondary');
            $table->string('badge_text')->nullable();
            $table->text('short_description')->nullable();
            $table->text('description')->nullable();
            $table->decimal('monthly_price', 10, 2);
            $table->decimal('yearly_price', 10, 2);
            $table->decimal('yearly_total', 10, 2)->nullable();
            $table->json('features')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
