<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('articles', 'access_type')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->enum('access_type', ['free', 'paid'])->default('free')->after('featured_image_url');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('articles', 'access_type')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->dropColumn('access_type');
            });
        }
    }
};
