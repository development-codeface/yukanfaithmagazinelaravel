<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['digital_price', 'print_price', 'both_price'] as $column) {
            if (!Schema::hasColumn('plans', $column)) {
                Schema::table('plans', fn (Blueprint $table) => $table->decimal($column, 10, 2)->nullable());
            }
        }
        if (!Schema::hasColumn('plans', 'is_trial')) {
            Schema::table('plans', fn (Blueprint $table) => $table->boolean('is_trial')->default(false));
        }
        if (!Schema::hasColumn('subscriptions', 'delivery_type')) {
            Schema::table('subscriptions', fn (Blueprint $table) => $table->string('delivery_type', 20)->default('digital'));
        }
        DB::table('plans')->where('print_enabled', false)->update(['digital_price' => DB::raw('price')]);
        DB::table('plans')->where('print_enabled', true)->update(['both_price' => DB::raw('price')]);
        DB::table('subscriptions')->where('print_enabled', true)->update(['delivery_type' => 'both']);
        // Existing subscriptions retain their agreed expiry date.
        $trial = [
            'is_trial' => true, 'duration' => 7, 'duration_type' => 'days',
            'price' => 0, 'digital_price' => 0, 'print_price' => null, 'both_price' => null,
            'print_enabled' => false,
            'description' => 'Seven days of complimentary digital access for new readers.',
            'features' => "Digital magazine reader\nPremium articles\n7 day trial for new accounts",
        ];
        if (Schema::hasColumn('plans', 'duration_days')) $trial['duration_days'] = 7;
        if (!Schema::hasColumn('plans', 'features')) unset($trial['features']);
        DB::table('plans')->where('name', 'Free Trial')->update($trial);
    }

    public function down(): void
    {
        Schema::table('subscriptions', fn (Blueprint $table) => $table->dropColumn('delivery_type'));
        Schema::table('plans', fn (Blueprint $table) => $table->dropColumn(['digital_price', 'print_price', 'both_price', 'is_trial']));
    }
};
