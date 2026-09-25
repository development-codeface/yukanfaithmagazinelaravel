<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'category_name' => 'Magazine',
                'description' => 'Latest magazine issues and features',
                'banner_image' => 'uploads/category/magazine.jpg',
            ],
            [
                'category_name' => 'News',
                'description' => 'Breaking news and updates',
                'banner_image' => 'uploads/category/news.jpg',
            ],
            [
                'category_name' => 'Events',
                'description' => 'Upcoming and past events',
                'banner_image' => 'uploads/category/events.jpg',
            ],
            [
                'category_name' => 'Featured Stories',
                'description' => 'Featured articles and stories',
                'banner_image' => 'uploads/category/stories.jpg',
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['category_name' => $category['category_name']],
                $category
            );
        }
    }
}
