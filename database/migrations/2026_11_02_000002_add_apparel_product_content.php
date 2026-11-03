<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apparel_products', function (Blueprint $table) {
            $table->json('size_chart')->nullable();
            $table->json('detail_sections')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('apparel_products', function (Blueprint $table) {
            $table->dropColumn(['size_chart', 'detail_sections']);
        });
    }
};
