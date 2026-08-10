<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tmdb_id')->nullable()->unique();
            // A Trakt id is always the movie's slug (e.g. "the-matrix-1999"), never numeric.
            $table->string('trakt_id')->nullable()->unique();
            $table->string('title');
            $table->string('poster_path')->nullable();
            $table->text('overview')->nullable();
            $table->string('status')->nullable();
            $table->decimal('vote_average', 3, 1)->nullable();
            $table->json('genres')->nullable();
            $table->date('release_date')->nullable();
            $table->string('resolved_source')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            // Calendar filters a release-date range.
            $table->index('release_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
