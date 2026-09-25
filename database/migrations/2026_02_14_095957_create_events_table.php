<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (Schema::hasTable('events')) {
            return; // Table already exists, skip
        }

        Schema::create('events', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->string('subtitle')->nullable();

            $table->longText('description')->nullable();

            $table->string('image')->nullable();

            $table->string('button_link')->nullable();

            $table->boolean('status')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
