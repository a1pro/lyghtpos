<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roleNames = ['admin', 'merchant', 'affiliate', 'salesperson', 'subadmin', 'trade_member', 'organization', 'favor_member','merchantmanager','merchantcashier'];

        foreach ($roleNames as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
            ]);
        }
    }
}
