<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per completed viewing. `user_movies.watched_at` still answers
        // "is this finished right now" (and so what's in the watchlist), but it
        // gets cleared by "Watch again" — this table is what remembers that a
        // movie was already seen, and how many times.
        Schema::create('movie_watches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_movie_id')->constrained()->cascadeOnDelete();
            $table->timestamp('watched_at');
            $table->timestamps();

            // Watch history reads these newest-first per movie.
            $table->index(['user_movie_id', 'watched_at']);
        });

        // Everything already marked watched counts as one viewing. Chunked and
        // inserted in batches so the deploy doesn't pay one round-trip per row
        // (or hydrate a whole install's watchlist to iterate it).
        DB::table('user_movies')
            ->whereNotNull('watched_at')
            ->orderBy('id')
            ->chunk(500, fn ($userMovies) => DB::table('movie_watches')->insert(
                $userMovies->map(fn ($userMovie) => [
                    'user_movie_id' => $userMovie->id,
                    'watched_at' => $userMovie->watched_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all()
            ));
    }

    public function down(): void
    {
        Schema::dropIfExists('movie_watches');
    }
};
