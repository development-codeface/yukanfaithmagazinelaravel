<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::create('magazine_issues', function (Blueprint $table) {
            $table->id();

            $table->string('title');

            $table->unsignedBigInteger('cover_media_id')->nullable();
            $table->date('issue_date')->nullable();

            $table->text('description')->nullable();
            $table->string('pdf_url')->nullable();

            $table->dateTime('published_at')->nullable();

            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            // $table->foreign('cover_media_id', 'fk_magazine_issues_cover_media')
            //     ->references('id')->on('media')
            //     ->nullOnDelete()
            //     ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('magazine_issues');
    }
};
