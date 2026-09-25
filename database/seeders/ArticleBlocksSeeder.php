<?php

namespace Database\Seeders;

use App\Models\ArticleBlock;
use App\Models\Article;
use Illuminate\Database\Seeder;

class ArticleBlocksSeeder extends Seeder
{
    public function run(): void
    {
        // Get articles to add blocks to
        $articles = Article::all();

        foreach ($articles as $article) {
            // Skip if article already has blocks
            if ($article->blocks()->count() > 0) {
                continue;
            }

            // Create content block with text
            ArticleBlock::create([
                'article_id' => $article->id,
                'block_type' => 'text',
                'block_order' => 1,
                'block_data' => [
                    'content' => '<p>' . $article->summary . '</p><p>This is the main content area where additional details about the article are provided. Readers can find comprehensive information about the topic here.</p>'
                ]
            ]);

            // Create image block
            ArticleBlock::create([
                'article_id' => $article->id,
                'block_type' => 'image',
                'block_order' => 2,
                'block_data' => [
                    'image' => 'uploads/banners/1770176650.jpeg',
                    'caption' => 'Featured image for ' . $article->title
                ]
            ]);

            // Create another text block
            ArticleBlock::create([
                'article_id' => $article->id,
                'block_type' => 'text',
                'block_order' => 3,
                'block_data' => [
                    'content' => '<p>This section provides additional context and insights related to the article topic. Readers will find valuable information that enhances their understanding of the subject matter.</p><p>Our team has carefully curated this content to ensure it meets the highest standards of quality and relevance.</p>'
                ]
            ]);

            // Create gallery/quote block for some articles
            if ($article->id % 2 == 0) {
                ArticleBlock::create([
                    'article_id' => $article->id,
                    'block_type' => 'quote',
                    'block_order' => 4,
                    'block_data' => [
                        'content' => '"' . $article->summary . '"',
                        'author' => 'Anonymous'
                    ]
                ]);
            }
        }
    }
}
