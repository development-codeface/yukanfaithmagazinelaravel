<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('magazine_issues', function (Blueprint $table) {
            $table->decimal('digital_price', 10, 2)->nullable();
            $table->decimal('print_price', 10, 2)->nullable();
            $table->decimal('both_price', 10, 2)->nullable();
        });
        DB::table('magazine_issues')->where('price', '>', 0)->update(['digital_price' => DB::raw('price')]);
        Schema::table('magazine_purchases', function (Blueprint $table) {
            $table->string('delivery_type', 20)->default('digital');
            $table->json('delivery_address')->nullable();
            $table->timestamp('print_acknowledged_at')->nullable();
            $table->index('user_id', 'magazine_purchases_user_lookup');
        });
        Schema::table('magazine_purchases', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'magazine_issue_id']);
            $table->unique(['user_id', 'magazine_issue_id', 'delivery_type'], 'magazine_purchases_user_issue_format_unique');
        });
    }

    public function down(): void
    {
        if (DB::table('magazine_purchases')->select('user_id', 'magazine_issue_id')->groupBy('user_id', 'magazine_issue_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Cannot roll back magazine formats while multiple formats have been purchased for the same issue.');
        }
        Schema::table('magazine_purchases', function (Blueprint $table) {
            $table->dropUnique('magazine_purchases_user_issue_format_unique');
            $table->unique(['user_id', 'magazine_issue_id']);
            $table->dropColumn(['delivery_type', 'delivery_address', 'print_acknowledged_at']);
        });
        Schema::table('magazine_issues', fn (Blueprint $table) => $table->dropColumn(['digital_price', 'print_price', 'both_price']));
    }
};
