<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('assets')->insert([
            ['dairy_id' => 1, 'asset_name' => 'Milk Van',  'purchase_value' => 250000, 'current_value' => 230000, 'purchase_date' => '2024-02-10', 'status' => 'active'],
            ['dairy_id' => 2, 'asset_name' => 'Boiler Unit', 'purchase_value' => 180000, 'current_value' => 150000, 'purchase_date' => '2024-05-12', 'status' => 'active'],
        ]);
    }
}
