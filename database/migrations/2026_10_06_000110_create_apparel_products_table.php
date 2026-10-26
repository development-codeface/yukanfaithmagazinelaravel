<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apparel_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apparel_category_id')->constrained('apparel_categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('compare_at_price', 10, 2)->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->string('status')->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->longText('size_guide')->nullable();
            $table->longText('product_details')->nullable();
            $table->longText('care_instructions')->nullable();
            $table->longText('shipping_delivery')->nullable();
            $table->longText('return_policy')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apparel_products');
    }
};
