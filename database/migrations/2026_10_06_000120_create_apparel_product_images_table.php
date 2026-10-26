<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apparel_product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apparel_product_id')->constrained('apparel_products')->cascadeOnDelete();
            $table->unsignedBigInteger('apparel_product_variant_id')->nullable();
            $table->string('image_path');
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apparel_product_images');
    }
};
