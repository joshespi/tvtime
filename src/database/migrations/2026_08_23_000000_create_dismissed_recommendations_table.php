<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "Not interested" on a recommendation. Keyed on tmdb_id, not a local
        // shows/movies row, since a dismissed title is very often something the
        // user has never tracked and so has no local record at all.
        Schema::create('dismissed_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('tmdb_id');
            $table->timestamps();

            $table->unique(['user_id', 'type', 'tmdb_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dismissed_recommendations');
    }
};
