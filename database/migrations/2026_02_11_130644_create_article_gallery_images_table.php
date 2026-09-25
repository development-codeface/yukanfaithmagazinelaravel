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
        if (Schema::hasTable('article_gallery_images')) {
            return; // Table already exists, skip
        }

        Schema::create('article_gallery_images', function (Blueprint $table) {
        $table->id();
        $table->foreignId('article_id')
              ->constrained()
              ->onDelete('cascade');
        $table->string('image_path');
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_gallery_images');
    }
};
