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
            $table->string('business_name')->nullable()->after('email');
            $table->string('primary_contact_name')->nullable()->after('business_name');
            $table->string('phone')->nullable()->after('primary_contact_name');
            $table->string('location')->nullable()->after('phone');
            $table->string('website')->nullable()->after('location');
            $table->string('consent_language_authorizing')->nullable()->after('website');
            $table->string('communyti_enrollment')->nullable()->after('consent_language_authorizing');
            $table->string('advocate_contact')->nullable()->after('communyti_enrollment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn([
                'business_name',
                'primary_contact_name',
                'phone',
                'location',
                'website',
                'consent_language_authorizing',
                'communyti_enrollment',
                'advocate_contact'
            ]);
        });
    }
};
