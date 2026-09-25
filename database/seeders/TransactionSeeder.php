<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('transactions')->insert([
            ['fund_allocation_id' => 1, 'dairy_id'=>'1', 'type' => 'debit', 'amount' => 5000, 'description' => 'Fuel expense', 'status' => 'completed', 'transaction_date' => now(), 'created_at' => now()],
            ['fund_allocation_id' => 2, 'dairy_id'=>'1', 'type' => 'debit', 'amount' => 3000, 'description' => 'Repair expense', 'status' => 'completed',  'transaction_date' => now(),'created_at' => now()],
        ]);
    }
}
