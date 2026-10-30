<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apparel_carts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->json('items');
            $table->timestamps();
        });
        Schema::table('apparel_orders', function (Blueprint $table) {
            $table->string('payment_intent_id')->nullable()->unique();
            $table->timestamp('paid_at')->nullable();
            $table->boolean('api_checkout')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('apparel_orders', fn (Blueprint $table) => $table->dropColumn(['payment_intent_id', 'paid_at', 'api_checkout']));
        Schema::dropIfExists('apparel_carts');
    }
};
