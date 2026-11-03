<?php

namespace Database\Seeders;

use App\Models\ApparelProduct;
use Illuminate\Database\Seeder;

class ApparelMeasurementExampleSeeder extends Seeder
{
    public function run(): void
    {
        $product = ApparelProduct::where('slug', 'made-for-more-black-mug-2-corinthians-418-inspirational-coffee-cup-11oz15oz')->first();
        // Seed only the requested existing mug, without replacing an entered chart.
        if (!$product || $product->size_chart !== null) {
            return;
        }

        $product->update([
            'size_chart' => [
                'columns' => ['11oz', '15oz'],
                'rows' => [
                    ['label' => 'Height', 'imperial' => ['3.78', '4.69'], 'metric' => ['9.60', '11.91']],
                    ['label' => 'Diameter', 'imperial' => ['3.19', '3.43'], 'metric' => ['8.10', '8.71']],
                ],
            ],
            'size_guide' => trim(($product->size_guide ?? '')."\nExample measurements only. Replace with verified supplier measurements before publishing."),
        ]);
    }
}
