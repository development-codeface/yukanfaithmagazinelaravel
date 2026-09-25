<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InvoiceItemSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('invoice_items')->insert([
            ['invoice_id' => 'INV001', 'product_name' => 'Stainless Tank', 'quantity' => 1, 'unit_price' => 15000, 'gst_percent' => 18, 'gst_amount' => 2700, 'discount' => 500, 'taxable_value' => 14500, 'total' => 17200],
            ['invoice_id' => 'INV001', 'product_name' => 'Cooling Motor', 'quantity' => 1, 'unit_price' => 12000, 'gst_percent' => 12, 'gst_amount' => 1440, 'discount' => 0, 'taxable_value' => 12000, 'total' => 13440],
        ]);
    }
}
