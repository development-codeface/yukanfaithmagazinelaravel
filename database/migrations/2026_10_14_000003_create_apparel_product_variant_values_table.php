<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('apparel_product_variant_values');

        Schema::create('apparel_product_variant_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('apparel_product_variant_id');
            $table->unsignedBigInteger('apparel_product_option_value_id');
            $table->timestamps();

            $table->foreign('apparel_product_variant_id', 'apvv_variant_fk')
                ->references('id')
                ->on('apparel_product_variants')
                ->cascadeOnDelete();
            $table->foreign('apparel_product_option_value_id', 'apvv_option_value_fk')
                ->references('id')
                ->on('apparel_product_option_values')
                ->cascadeOnDelete();
            $table->unique(['apparel_product_variant_id', 'apparel_product_option_value_id'], 'variant_option_value_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apparel_product_variant_values');
    }
};
