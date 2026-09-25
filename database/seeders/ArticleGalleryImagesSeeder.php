<?php

namespace Database\Seeders;

use App\Models\ArticleGalleryImage;
use App\Models\Article;
use Illuminate\Database\Seeder;

class ArticleGalleryImagesSeeder extends Seeder
{
    public function run(): void
    {
        // Gallery images to cycle through
        $galleryImages = [
            'uploads/banners/1770176316.jpg',
            'uploads/banners/1770176636.jpeg',
            'uploads/banners/1770176650.jpeg',
            'uploads/banners/1770176663.jpeg',
            'uploads/articles/1770183657_Screenshot 2025-04-29 100737.png',
            'uploads/articles/1770183981_Screenshot 2025-03-04 115933.png',
            'uploads/articles/1770184079_Screenshot 2025-03-04 122831.png',
        ];

        $articles = Article::all();

        foreach ($articles as $article) {
            // Skip if already has gallery images
            if ($article->galleryImages()->count() > 0) {
                continue;
            }

            // Add 2-4 gallery images to each article
            $numImages = rand(2, 4);
            for ($i = 0; $i < $numImages; $i++) {
                $randomImage = $galleryImages[array_rand($galleryImages)];
                
                ArticleGalleryImage::create([
                    'article_id' => $article->id,
                    'image_path' => $randomImage,
                ]);
            }
        }
    }
}
