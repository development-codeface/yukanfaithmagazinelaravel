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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name');
            $table->unsignedBigInteger('parent_category')->nullable();
            $table->text('description')->nullable();
            $table->string('banner_image')->nullable();
            $table->string('category_subtitle')->nullable();
            $table->text('subtitle_description')->nullable();
            $table->timestamps();

            // Foreign key for parent category
            $table->foreign('parent_category')->references('id')->on('categories')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
