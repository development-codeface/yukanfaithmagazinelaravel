<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apparel_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('number')->unique();
            $table->unsignedBigInteger('user_id')->index();
            $table->uuid('checkout_token')->unique();
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('pending');
            $table->string('name');
            $table->string('email');
            $table->string('phone', 50);
            $table->text('address');
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->string('tracking_number')->nullable();
            $table->decimal('total', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->timestamps();
        });
        Schema::create('apparel_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apparel_order_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->string('name');
            $table->string('options')->nullable();
            $table->string('sku')->nullable();
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apparel_order_items');
        Schema::dropIfExists('apparel_orders');
    }
};
