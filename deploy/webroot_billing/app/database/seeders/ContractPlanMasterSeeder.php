<?php

namespace Database\Seeders;

use App\Models\ContractPlanMaster;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContractPlanMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $masters = [
            [
                'name' => 'DSチャットボットシリーズ',
                'description' => 'DSチャットボット製品の製品マスター',
                'is_active' => true,
                'display_order' => 1,
            ],
            [
                'name' => 'DSHRシリーズ',
                'description' => 'DSHR製品の製品マスター',
                'is_active' => true,
                'display_order' => 2,
            ],
            [
                'name' => 'DSXRシリーズ',
                'description' => 'DSXR製品の製品マスター',
                'is_active' => true,
                'display_order' => 3,
            ],
            [
                'name' => 'DSオンラインシリーズ',
                'description' => 'DSオンライン製品の製品マスター',
                'is_active' => true,
                'display_order' => 4,
            ],
        ];

        foreach ($masters as $master) {
            ContractPlanMaster::updateOrCreate(
                ['name' => $master['name']],
                $master
            );
        }
    }
}
