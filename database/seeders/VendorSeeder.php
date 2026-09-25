<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('vendors')->insert([
            ['name' => 'ABC Supplies', 'email' => 'abc@supplies.com', 'phone' => '9898989898', 'address' => 'Trivandrum', 'status' => 1],
            ['name' => 'Fresh Equipments', 'email' => 'info@freshequip.com', 'phone' => '9777799999', 'address' => 'Kollam', 'status' => 1],
        ]);
    }
}
