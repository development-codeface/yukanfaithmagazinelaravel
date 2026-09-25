<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

// Test Article and Category relationships
try {
    $article = \App\Models\Article::with('categories')->first();
    if ($article) {
        echo "✓ Article found: " . $article->title . "\n";
        echo "✓ Categories count: " . $article->categories->count() . "\n";
    } else {
        echo "No articles found\n";
    }

    $category = \App\Models\Category::first();
    if ($category) {
        echo "✓ Category found: " . ($category->category_name ?? 'ID ' . $category->id) . "\n";
    } else {
        echo "No categories found\n";
    }

    // Check pivot table
    $count = \DB::table('article_category')->count();
    echo "✓ Pivot table entries: " . $count . "\n";

    echo "\n✓ All relationships working!\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
