<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            if (!Schema::hasColumn('articles', 'single_article_price')) {
                $table->decimal('single_article_price', 10, 2)->nullable()->after('access_type');
            }
        });

        if (!Schema::hasTable('article_purchases')) {
            Schema::create('article_purchases', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('article_id');
                $table->decimal('amount_paid', 10, 2)->nullable();
                $table->boolean('payment_status')->default(false);
                $table->string('transaction_id')->nullable();
                $table->dateTime('purchased_at')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('article_id')->references('id')->on('articles')->cascadeOnDelete();
                $table->unique(['user_id', 'article_id']);
                $table->index('payment_status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('article_purchases');

        Schema::table('articles', function (Blueprint $table) {
            if (Schema::hasColumn('articles', 'single_article_price')) {
                $table->dropColumn('single_article_price');
            }
        });
    }
};
