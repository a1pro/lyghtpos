<?php
// database/migrations/2024_01_01_000002_create_import_failure_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('import_failure_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('import_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('row_number');
            $table->string('product_name', 500)->nullable();
            $table->string('barcode', 100)->nullable();
            $table->string('error_type', 50)->index();
            $table->text('error_message');
            $table->json('raw_data')->nullable();
            $table->boolean('resolved')->default(false)->index();
            $table->integer('retry_count')->default(0);
            $table->timestamp('created_at')->useCurrent();
            
            // Indexes for performance
            $table->index(['import_id', 'resolved']);
            $table->index(['user_id', 'resolved']);
            $table->index(['error_type', 'resolved']);
            $table->index('created_at');
            
            // Foreign keys
            $table->foreign('import_id')
                  ->references('id')
                  ->on('merchant_product_imports')
                  ->onDelete('cascade');
                  
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('import_failure_logs');
    }
};