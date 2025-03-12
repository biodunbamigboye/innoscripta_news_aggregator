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
        Schema::create('articles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('data_source_id');
            $table->string('data_source_identifier')->nullable();
            $table->string('author')->nullable();
            $table->string('category')->nullable();
            $table->string('source')->nullable();
            $table->string('title');
            $table->string('story_url', 600);
            $table->text('image_url')->nullable();
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->dateTime('published_at');
            $table->timestamps();

            $table->unique(['data_source_id', 'story_url']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
