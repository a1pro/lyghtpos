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
        $roleNames = ['admin', 'merchant', 'affiliate', 'salesperson','subadmin'];
        
        foreach ($roleNames as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
            ]);
        }
    }
}