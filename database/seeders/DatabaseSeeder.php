<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);
        $this->call(SettingsSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(MerchantSubscriptionPlanSeeder::class);
        $this->call(GovernanctLayerData::class);
        $this->call(AppSettingSeeder::class);
        // $this->call(TransactionSeeder::class);
    }
}
