<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\GovernanceLayer;

class GovernanctLayerData extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $governance = [
            [
                'name' => 'Governance First Check',
                'description' => 'Check Merchant documents'
            ],
            [
                'name' => 'Governance Second Check',
                'description' => 'Check Merchant Business Detail'
            ],
            [
                'name' => 'Governance Third Check',
                'description' => 'Check Merchant Pledge Request'
            ],
            [
                'name' => 'Governance Fourth Check',
                'description' => 'Check Merchant Representation Form Detail'
            ]
        ];

        foreach($governance as $govt){
            GovernanceLayer::updateOrCreate(
                ['name' => $govt['name']], // First parameter: attributes to search for
                ['description' => $govt['description']] // Second parameter: values to update/create
            );
        }

        $this->command->info('Governance Rules added successfully');
    }
}