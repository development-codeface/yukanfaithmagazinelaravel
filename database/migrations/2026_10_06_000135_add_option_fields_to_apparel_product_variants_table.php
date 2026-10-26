<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('apparel_product_variants', 'option_type')) {
            Schema::table('apparel_product_variants', function (Blueprint $table) {
                $table->string('option_type')->nullable()->after('apparel_product_id');
                $table->string('option_value')->nullable()->after('option_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('apparel_product_variants', 'option_type')) {
            Schema::table('apparel_product_variants', function (Blueprint $table) {
                $table->dropColumn(['option_type', 'option_value']);
            });
        }
    }
};
