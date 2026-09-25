<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannersSeeder extends Seeder
{
    public function run(): void
    {
        $banners = [
            [
                'image' => 'uploads/slider/cover3.jpeg',
                'status' => 1,
            ],
            [
                'image' => 'uploads/slider/cover4a.jpeg',
                'status' => 1,
            ],
            [
                'image' => 'uploads/slider/cover5.jpeg',
                'status' => 1,
            ],
            [
                'image' => 'uploads/slider/cover52.jpg',
                'status' => 1,
            ],
            [
                'image' => 'uploads/banners/1770176316.jpg',
                'status' => 1,
            ],
            [
                'image' => 'uploads/banners/1770176636.jpeg',
                'status' => 1,
            ],
            [
                'image' => 'uploads/banners/1770176650.jpeg',
                'status' => 1,
            ],
        ];

        foreach ($banners as $banner) {
            Banner::updateOrCreate(
                ['image' => $banner['image']],
                $banner
            );
        }
    }
}
