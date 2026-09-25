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
        Schema::table('categories', function (Blueprint $table) {
            // Add missing columns if they don't already exist
            if (!Schema::hasColumn('categories', 'category_name')) {
                $table->string('category_name')->after('id');
            }
            if (!Schema::hasColumn('categories', 'parent_category')) {
                $table->unsignedBigInteger('parent_category')->nullable()->after('category_name');
            }
            if (!Schema::hasColumn('categories', 'description')) {
                $table->text('description')->nullable()->after('parent_category');
            }
            if (!Schema::hasColumn('categories', 'banner_image')) {
                $table->string('banner_image')->nullable()->after('description');
            }
            if (!Schema::hasColumn('categories', 'category_subtitle')) {
                $table->string('category_subtitle')->nullable()->after('banner_image');
            }
            if (!Schema::hasColumn('categories', 'subtitle_description')) {
                $table->text('subtitle_description')->nullable()->after('category_subtitle');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'category_name')) {
                $table->dropColumn('category_name');
            }
            if (Schema::hasColumn('categories', 'parent_category')) {
                $table->dropColumn('parent_category');
            }
            if (Schema::hasColumn('categories', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('categories', 'banner_image')) {
                $table->dropColumn('banner_image');
            }
            if (Schema::hasColumn('categories', 'category_subtitle')) {
                $table->dropColumn('category_subtitle');
            }
            if (Schema::hasColumn('categories', 'subtitle_description')) {
                $table->dropColumn('subtitle_description');
            }
        });
    }
};
