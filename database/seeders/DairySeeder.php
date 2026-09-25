<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DairySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('dairies')->insert([
            ['name' => 'Vellarada Dairy', 'location' => 'Vellarada, Kerala', 'admin_userid'=>'1', 'phone' => '0471-5550001',  'created_at' => now()],
            ['name' => 'Nedumangad Dairy', 'location' => 'Nedumangad, Kerala', 'admin_userid'=>'1', 'phone' => '0471-5550002',  'created_at' => now()],
        ]);
    }
}
