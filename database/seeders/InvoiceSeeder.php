<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('invoices')->insert([
            ['id'=>'INV001','vendor_id' => 1, 'dairy_id' => 1, 'total_amount' => 15000, 'discount' => 500, 'status' => 'approved', 'created_at' => now()],
            ['id'=>'INV002','vendor_id' => 2, 'dairy_id' => 2, 'total_amount' => 12000, 'discount' => 0, 'status' => 'approved', 'created_at' => now()],
        ]);
    }
}
