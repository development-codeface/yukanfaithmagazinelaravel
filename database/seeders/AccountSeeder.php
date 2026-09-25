<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('accounts')->insert([
            ['dairy_id'=>'1','fund_id' => 1, 'opening_balance' => 100000, 'main_balance' => 100000, 'created_at' => now()],
            ['dairy_id'=>'1','fund_id' => 2, 'opening_balance' => 80000, 'main_balance' => 80000, 'created_at' => now()],
        ]);
    }
}
