<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_movies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
            // Each watch lives in movie_watches (see later migrations); this is the
            // latest one, denormalized and kept in sync by UserMovie::syncWatchedAt().
            $table->timestamp('watched_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'movie_id']);
            // The watchlist filters this user's rows by watched_at being null.
            $table->index(['user_id', 'watched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_movies');
    }
};
