<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class FundSeeder extends Seeder
{
  

    public function run()
    {
        DB::table('fund_allocations')->insert([
            [
                'dairy_id' => 1,
                'amount' => 500.00,
                'allocation_date' => '2025-10-01',
                'financial_year' => '2025-2026',
                'remarks' => 'Initial allocation',
                'status' => 'approved',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'dairy_id' => 1,
                'amount' => 750.00,
                'allocation_date' => '2025-10-02',
                'financial_year' => '2025-2026',
                'remarks' => 'Monthly adjustment',
                'status' => 'approved',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'dairy_id' => 1,
                'amount' => 300.00,
                'allocation_date' => '2025-10-03',
                'financial_year' => '2025-2026',
                'remarks' => 'Correction',
                'status' => 'approved',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}

