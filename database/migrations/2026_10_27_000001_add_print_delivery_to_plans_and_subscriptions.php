<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('print_enabled')->default(false);
        });
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->boolean('print_enabled')->default(false);
            $table->json('delivery_address')->nullable();
            $table->timestamp('print_acknowledged_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', fn (Blueprint $table) => $table->dropColumn(['print_enabled', 'delivery_address', 'print_acknowledged_at']));
        Schema::table('plans', fn (Blueprint $table) => $table->dropColumn('print_enabled'));
    }
};
