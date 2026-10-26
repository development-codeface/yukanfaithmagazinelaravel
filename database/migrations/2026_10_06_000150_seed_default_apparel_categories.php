<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['Women', 'Men', 'Accessories', 'Home & Living'] as $index => $name) {
            DB::table('apparel_categories')->updateOrInsert(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'status' => true,
                    'sort_order' => $index,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('apparel_categories')
            ->whereIn('slug', ['women', 'men', 'accessories', 'home-living'])
            ->delete();
    }
};
