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
        Schema::table('representations', function (Blueprint $table) {
            /** DROP COLUMNS */
            if (Schema::hasColumn('representations', 'subject')) {
                $table->dropColumn('subject');
            }
            if (Schema::hasColumn('representations', 'purpose')) {
                $table->dropColumn('purpose');
            }
            if (Schema::hasColumn('representations', 'futuristic_plan')) {
                $table->dropColumn('futuristic_plan');
            }
            if (Schema::hasColumn('representations', 'currency')) {
                $table->dropColumn('currency');
            }
            /** DROP COLUMNS */


            if (!Schema::hasColumn('representations', 'community')) {
                $table->string('community')->nullable()->after('user_id');
            }

            if (!Schema::hasColumn('representations', 'role')) {
                $table->string('role')->nullable()->after('community');
            }

            if (!Schema::hasColumn('representations', 'accepted_currencies')) {
                $table->json('accepted_currencies')->nullable()->after('role');
            }

            if (!Schema::hasColumn('representations', 'participate_vouchers')) {
                $table->boolean('participate_vouchers')->default(false)->after('accepted_currencies');
            }

            if (!Schema::hasColumn('representations', 'allow_barter')) {
                $table->boolean('allow_barter')->default(false)->after('participate_vouchers');
            }

            if (!Schema::hasColumn('representations', 'pledge_description')) {
                $table->text('pledge_description')->nullable()->after('allow_barter');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('representations', function (Blueprint $table) {
            if (Schema::hasColumn('representations', 'community')) {
                $table->dropColumn('community');
            }

            if (Schema::hasColumn('representations', 'role')) {
                $table->dropColumn('role');
            }

            if (Schema::hasColumn('representations', 'accepted_currencies')) {
                $table->dropColumn('accepted_currencies');
            }

            if (Schema::hasColumn('representations', 'participate_vouchers')) {
                $table->dropColumn('participate_vouchers');
            }

            if (Schema::hasColumn('representations', 'allow_barter')) {
                $table->dropColumn('allow_barter');
            }

            if (Schema::hasColumn('representations', 'pledge_description')) {
                $table->dropColumn('pledge_description');
            }

            /** ADD BACK COLUMNS */
            if (!Schema::hasColumn('representations', 'subject')) {
                $table->string('subject')->nullable()->after('user_id');
            }

            if (!Schema::hasColumn('representations', 'purpose')) {
                $table->string('purpose')->nullable()->after('subject');
            }

            if (!Schema::hasColumn('representations', 'futuristic_plan')) {
                $table->string('futuristic_plan')->nullable()->after('purpose');
            }

            if (!Schema::hasColumn('representations', 'currency')) {
                $table->string('currency')->nullable()->after('pledge_value');
            }
            /** ADD BACK COLUMNS */
        });
    }
};
