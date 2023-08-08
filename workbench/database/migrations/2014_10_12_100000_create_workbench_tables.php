<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('podcasts', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('comments', function ($table) {
            $table->id();
            $table->foreignId('podcast_id');
            $table->string('content');
            $table->timestamps();
        });

        Schema::create('books', function ($table) {
            $table->id();
            $table->string('title');
            $table->foreignId('user_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('podcasts');
    }
};
