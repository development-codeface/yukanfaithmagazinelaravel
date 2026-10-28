<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('author_name')->nullable();
            $table->unsignedBigInteger('author_id')->nullable()->change();
        });

        DB::table('articles')->whereNull('author_name')->update([
            'author_name' => DB::raw('(SELECT name FROM users WHERE users.id = articles.author_id)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('author_name');
        });
    }
};
