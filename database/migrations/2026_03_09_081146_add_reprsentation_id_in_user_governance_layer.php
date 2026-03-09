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
        Schema::table('user_governance_layers', function (Blueprint $table) {
            $table->foreignId('representation_id')
            ->nullable()
            ->after('user_id')
            ->constrained('representations')
            ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_governance_layers', function (Blueprint $table) {
            $table->dropColumn('representation_id');
        });
    }
};
