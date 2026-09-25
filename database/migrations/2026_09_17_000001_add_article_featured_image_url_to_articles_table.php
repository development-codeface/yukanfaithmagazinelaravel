<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            if (!Schema::hasColumn('articles', 'article_featured_image_url')) {
                $table->string('article_featured_image_url')->nullable()->after('featured_image_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            if (Schema::hasColumn('articles', 'article_featured_image_url')) {
                $table->dropColumn('article_featured_image_url');
            }
        });
    }
};
