<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Region;

class RegionsTableSeeder extends Seeder
{
    public function run()
    {
        $regions = [
            ['name' => 'Trivandrum'],
            ['name' => 'Kollam'],
            ['name' => 'Pathanamthitta'],
        ];

        foreach ($regions as $region) {
            Region::create($region);
        }
    }
}
