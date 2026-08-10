<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('show_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('season');
            $table->unsignedInteger('episode_number');
            $table->string('title')->nullable();
            $table->date('air_date')->nullable();
            $table->unsignedBigInteger('tmdb_id')->nullable();
            $table->unsignedBigInteger('trakt_id')->nullable();
            $table->timestamps();

            $table->unique(['show_id', 'season', 'episode_number']);
            // Calendar filters a date range across all episodes; up-next filters
            // air_date per show. Without this both scan the whole table.
            $table->index(['air_date', 'show_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
