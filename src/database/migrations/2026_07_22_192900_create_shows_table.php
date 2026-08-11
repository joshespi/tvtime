<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tmdb_id')->nullable()->unique();
            // Dropped again by 2026_08_11_000001_drop_trakt_columns; kept here so
            // already-migrated installs replay the same history.
            $table->string('trakt_id')->nullable()->unique();
            $table->string('title');
            $table->string('poster_path')->nullable();
            $table->text('overview')->nullable();
            $table->string('status')->nullable();
            $table->decimal('vote_average', 3, 1)->nullable();
            $table->json('genres')->nullable();
            $table->date('first_air_date')->nullable();
            $table->string('resolved_source')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shows');
    }
};
