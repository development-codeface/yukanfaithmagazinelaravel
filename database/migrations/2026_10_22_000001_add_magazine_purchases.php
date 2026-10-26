<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('magazine_issues', fn (Blueprint $table) => $table->decimal('price', 10, 2)->nullable());
        Schema::create('magazine_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('magazine_issue_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount_paid', 10, 2);
            $table->string('transaction_id')->unique();
            $table->timestamps();
            $table->unique(['user_id', 'magazine_issue_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('magazine_purchases');
        Schema::table('magazine_issues', fn (Blueprint $table) => $table->dropColumn('price'));
    }
};
