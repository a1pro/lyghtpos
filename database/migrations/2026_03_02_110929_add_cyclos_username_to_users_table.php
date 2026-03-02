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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'cyclos_username')) {
                $table->string('cyclos_username')->nullable()->after('cyclos_user_id');
            }
            
            // Also add cyclos_group if it doesn't exist
            if (!Schema::hasColumn('users', 'cyclos_group')) {
                $table->string('cyclos_group')->nullable()->after('cyclos_username');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['cyclos_username', 'cyclos_group']);
        });
    }
};
