<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestArticlesSeeder extends Seeder
{
    public function run(): void
    {
        // Get or create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@magazine.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password123'),
            ]
        );

        // Get categories
        $categories = Category::all();
        if ($categories->isEmpty()) {
            $this->call(CategoriesSeeder::class);
            $categories = Category::all();
        }

        // Create test articles with images
        $articles = [
            [
                'title' => 'Bishop Dr. Loretta Sanders: The Quiet Boldness of Building Yukanfaith',
                'summary' => 'Discover the vision and leadership of Bishop Dr. Loretta Sanders as she shares the inspiration behind Yukanfaith magazine.',
                'content' => '<p>This is the first featured article about Bishop Dr. Loretta Sanders and her journey in building Yukanfaith magazine. The quiet boldness of her vision has transformed lives and communities across the nation.</p><p>Her dedication to faith and excellence is evident in every issue published.</p>',
                'featured_image_url' => 'uploads/articles/1770183657_Screenshot 2025-04-29 100737.png',
                'category_id' => $categories->first()->id ?? 1,
                'author_id' => $admin->id,
                'status' => 'published',
                'published_at' => now()->subDays(5),
            ],
            [
                'title' => 'The Power of Faith in Modern Times',
                'summary' => 'Explore how faith continues to inspire and transform lives in today\'s modern world.',
                'content' => '<p>In a world filled with challenges and uncertainties, faith remains a beacon of hope for many. This article explores the profound impact of spiritual beliefs on personal growth and community development.</p>',
                'featured_image_url' => 'uploads/articles/1770183981_Screenshot 2025-03-04 115933.png',
                'category_id' => $categories->count() > 1 ? $categories->skip(1)->first()->id : 1,
                'author_id' => $admin->id,
                'status' => 'published',
                'published_at' => now()->subDays(3),
            ],
            [
                'title' => 'Building Stronger Communities Through Unity',
                'summary' => 'Learn how communities are coming together to build a stronger, more unified future.',
                'content' => '<p>Unity is the foundation of strong communities. This article highlights inspiring stories of how diverse groups are working together to create positive change and lasting impact in their neighborhoods.</p>',
                'featured_image_url' => 'uploads/articles/1770184079_Screenshot 2025-03-04 122831.png',
                'category_id' => $categories->count() > 2 ? $categories->skip(2)->first()->id : 1,
                'author_id' => $admin->id,
                'status' => 'published',
                'published_at' => now()->subDays(1),
            ],
            [
                'title' => 'Latest News from the Magazine',
                'summary' => 'Stay updated with the latest news and announcements from Yukanfaith Magazine.',
                'content' => '<p>We are thrilled to announce our latest magazine issue featuring exclusive interviews and inspiring stories. Be sure to stay tuned for more exciting content coming soon.</p>',
                'featured_image_url' => 'uploads/banners/1770176316.jpg',
                'category_id' => $categories->first()->id ?? 1,
                'author_id' => $admin->id,
                'status' => 'published',
                'published_at' => now(),
            ],
            [
                'title' => 'Inspiring Stories of Change',
                'summary' => 'Read about individuals who are making a difference in their communities.',
                'content' => '<p>Every day, ordinary people do extraordinary things. This article features stories of courage, compassion, and commitment from people making real change in their communities.</p>',
                'featured_image_url' => 'uploads/banners/1770176636.jpeg',
                'category_id' => $categories->count() > 3 ? $categories->skip(3)->first()->id : 1,
                'author_id' => $admin->id,
                'status' => 'published',
                'published_at' => now()->subDays(2),
            ],
            [
                'title' => 'Wellness and Spiritual Growth',
                'summary' => 'Discover practices and insights for personal wellness and spiritual development.',
                'content' => '<p>True wellness encompasses physical, mental, and spiritual health. This comprehensive guide explores various practices that can help you achieve balance and peace in your life.</p>',
                'featured_image_url' => 'uploads/banners/1770176650.jpeg',
                'category_id' => $categories->first()->id ?? 1,
                'author_id' => $admin->id,
                'status' => 'published',
                'published_at' => now()->subDays(4),
            ],
        ];

        foreach ($articles as $articleData) {
            $article = Article::updateOrCreate(
                ['title' => $articleData['title']],
                $articleData
            );

            // Attach to multiple categories for better testing
            if ($categories->count() > 0) {
                $article->categories()->sync($categories->pluck('id')->toArray());
            }
        }
    }
}
