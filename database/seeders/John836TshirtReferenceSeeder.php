<?php

namespace Database\Seeders;

use App\Models\ApparelCategory;
use App\Models\ApparelProduct;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class John836TshirtReferenceSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $slug = 'john-836-unapologetically-free-t-shirt-black-history-juneteenth-pride-tee';
            if (ApparelProduct::withTrashed()->where('slug', $slug)->exists()) {
                return;
            }
            $category = ApparelCategory::firstOrCreate(['slug' => 't-shirts'], ['name' => 'T-shirts', 'status' => true]);
            ApparelProduct::create([
                'apparel_category_id' => $category->id,
                'name' => 'John 8:36 Unapologetically Free T-Shirt - Black History Juneteenth Pride Tee',
                'slug' => $slug,
                // This import is for content review; configure local pricing and inventory before publishing.
                'price' => 0,
                'stock' => 0,
                'status' => 'draft',
                'short_description' => 'John 8:36 Unapologetically Free graphic T-shirt in black.',
                'description' => 'Unisex Gildan 5000 crew-neck T-shirt with a regular fit and John 8:36 design.',
                'size_guide' => 'Measurements imported from https://yukanfaithapparel.com/product/28875833. Sleeve length is measured from the center back. Configure price, stock and purchase options before publishing this draft.',
                'size_chart' => [
                    'columns' => ['S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL'],
                    'rows' => [
                        ['label' => 'Width', 'imperial' => ['18.00', '20.00', '22.00', '24.00', '26.00', '28.00', '30.00', '32.00'], 'metric' => ['45.72', '50.80', '55.88', '60.96', '66.04', '71.12', '76.20', '81.28']],
                        ['label' => 'Length', 'imperial' => ['28.00', '29.00', '30.00', '31.00', '32.00', '33.00', '34.00', '35.00'], 'metric' => ['71.12', '73.66', '76.20', '78.74', '81.28', '83.82', '86.36', '88.90']],
                        ['label' => 'Sleeve length (from center back)', 'imperial' => ['15.10', '16.50', '18.00', '19.50', '21.00', '22.40', '23.70', '25.00'], 'metric' => ['38.35', '41.91', '45.72', '49.53', '53.34', '56.90', '60.20', '63.50']],
                        ['label' => 'Size tolerance', 'imperial' => array_fill(0, 8, '1.50'), 'metric' => array_fill(0, 8, '3.81')],
                    ],
                ],
                'detail_sections' => [
                    ['icon' => 'shirt', 'title' => 'Fabric', 'content' => 'The black solid-colour version is 100% cotton, with smooth, strong spun fibers suitable for printing.'],
                    ['icon' => 'shirt', 'title' => 'Seamless body', 'content' => 'Tubular knit construction avoids side seams and reduces fabric waste.'],
                    ['icon' => 'info', 'title' => 'Collar', 'content' => 'An elastic rib-knit collar helps the neckline keep its shape.'],
                    ['icon' => 'shield', 'title' => 'Shoulder reinforcement', 'content' => 'Twill tape supports the shoulder seams and helps prevent stretching.'],
                    ['icon' => 'info', 'title' => 'Product information', 'content' => 'Gildan 5000. For adults. Made in Nicaragua.'],
                ],
                'care_instructions' => 'Machine wash cold, up to 30°C / 90°F. Use non-chlorine bleach when needed. Tumble dry on low heat. Do not iron or dry-clean.',
            ]);
        });
    }
}
