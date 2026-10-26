<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apparel_product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apparel_product_id')->constrained('apparel_products')->cascadeOnDelete();
            $table->string('option_type')->nullable();
            $table->string('option_value')->nullable();
            $table->string('color_name')->nullable();
            $table->string('color_hex', 20)->nullable();
            $table->string('size')->nullable();
            $table->string('sku')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('status')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apparel_product_variants');
    }
};
