<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('article_category')) {
            return; // Table already exists, skip
        }

        Schema::create('article_category', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('article_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamps();

            // Indexes first
            $table->index('article_id');
            $table->index('category_id');

            // Unique constraint to prevent duplicates
            $table->unique(['article_id', 'category_id'], 'unique_article_category');
        });

        // Add foreign keys separately after table creation
        Schema::table('article_category', function (Blueprint $table) {
            $table->foreign('article_id', 'fk_article_category_article')
                ->references('id')->on('articles')
                ->cascadeOnDelete();
            $table->foreign('category_id', 'fk_article_category_category')
                ->references('id')->on('categories')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_category');
    }
};
