<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('apparel_product_images', 'apparel_product_variant_id')) {
            Schema::table('apparel_product_images', function (Blueprint $table) {
                $table->unsignedBigInteger('apparel_product_variant_id')->nullable()->after('apparel_product_id');
            });
        }

        Schema::table('apparel_product_images', function (Blueprint $table) {
            $table->foreign('apparel_product_variant_id', 'apparel_product_images_variant_id_foreign')
                ->references('id')
                ->on('apparel_product_variants')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('apparel_product_images', function (Blueprint $table) {
            $table->dropForeign('apparel_product_images_variant_id_foreign');
        });
    }
};
