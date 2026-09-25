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
        Schema::create('media', function (Blueprint $table) {
            $table->id();

            $table->string('url');
            $table->string('type', 50);
            $table->string('alt_text')->nullable();

            $table->unsignedBigInteger('uploaded_by')->nullable();

            $table->dateTime('created_at')->useCurrent();

            $table->foreign('uploaded_by', 'fk_media_uploaded_by')
                ->references('id')->on('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
