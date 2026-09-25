<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('expenses')->insert([
            ['dairy_id' => 1, 'fund_id' => 1, 'expensecategory_id' => 1, 'amount' => 5000, 'description' => 'Diesel for milk trucks', 'created_at' => now()],
            ['dairy_id' => 2, 'fund_id' => 2, 'expensecategory_id' => 2, 'amount' => 3000, 'description' => 'Pasteurizer repair', 'created_at' => now()],
        ]);
    }
}
