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
      Schema::create('articles', function (Blueprint $table) {
            $table->id();

            $table->string('title');

            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('author_id');

            $table->unsignedBigInteger('issue_id')->nullable();

            $table->text('summary')->nullable();
            $table->longText('content');

            $table->string('featured_image_url')->nullable();

            $table->enum('access_type', ['free', 'paid'])->default('free');

            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');

            $table->dateTime('published_at')->nullable();

            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Indexes (from SQL)
            $table->index('category_id', 'idx_articles_category');
            $table->index('author_id', 'idx_articles_author');
            $table->index('status', 'idx_articles_status');
            $table->index('published_at', 'idx_articles_published_at');
            $table->index('issue_id', 'fk_articles_issue');

            // Foreign Keys (from SQL)
            $table->foreign('author_id', 'fk_articles_author')
                ->references('id')->on('users')
                ->cascadeOnUpdate();

            $table->foreign('category_id', 'fk_articles_category')
                ->references('id')->on('categories')
                ->cascadeOnUpdate();

            $table->foreign('issue_id', 'fk_articles_issue')
                ->references('id')->on('magazine_issues')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
