<?php
// database/migrations/2024_01_01_000003_add_import_columns_to_merchant_product_imports.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('merchant_product_imports', function (Blueprint $table) {
            // Add missing columns if they don't exist

            if (!Schema::hasColumn('merchant_product_imports', 'total_rows')) {
                $table->string('total_rows')->nullable()->after('file_name');
            }

            if (!Schema::hasColumn('merchant_product_imports', 'processed_rows')) {
                $table->integer('processed_rows')->default(0)->after('total_rows');
            }
            if (!Schema::hasColumn('merchant_product_imports', 'success_rows')) {
                $table->integer('success_rows')->default(0)->after('processed_rows');
            }
            if (!Schema::hasColumn('merchant_product_imports', 'failed_rows')) {
                $table->integer('failed_rows')->default(0)->after('success_rows');
            }
            if (!Schema::hasColumn('merchant_product_imports', 'status')) {
                $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'partial'])->default('pending')->after('failed_rows');
            }
            if (!Schema::hasColumn('merchant_product_imports', 'error_log')) {
                $table->json('error_log')->nullable()->after('status');
            }
            if (!Schema::hasColumn('merchant_product_imports', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('error_log');
            }
            if (!Schema::hasColumn('merchant_product_imports', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('started_at');
            }
            
            // Add indexes
            $table->index(['user_id', 'status']);
        });
    }

    public function down()
    {
        Schema::table('merchant_product_imports', function (Blueprint $table) {
            $table->dropColumn([
                'processed_rows', 'success_rows', 'failed_rows', 
                'status', 'error_log', 'started_at', 'completed_at'
            ]);
        });
    }
};