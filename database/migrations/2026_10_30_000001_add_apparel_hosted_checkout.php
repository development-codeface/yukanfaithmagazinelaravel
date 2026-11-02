<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apparel_orders', function (Blueprint $table) {
            $table->string('payment_flow', 16)->default('intent');
            $table->string('checkout_session_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('apparel_orders', fn (Blueprint $table) => $table->dropColumn(['payment_flow', 'checkout_session_id']));
    }
};
