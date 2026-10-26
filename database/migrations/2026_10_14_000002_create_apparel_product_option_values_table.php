<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apparel_product_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apparel_product_option_id')->constrained('apparel_product_options')->cascadeOnDelete();
            $table->string('value');
            $table->string('color_hex', 20)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apparel_product_option_values');
    }
};
