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
        if (Schema::hasColumn('articles', 'left_image_title')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->string('left_image_title')->nullable()->default(null)->change();
            });
        } else {
            Schema::table('articles', function (Blueprint $table) {
                $table->string('left_image_title')->nullable()->default(null)->after('featured_image_url');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('articles', 'left_image_title')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->dropColumn('left_image_title');
            });
        }
    }
};
