<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Trakt's API went behind a paywall, and TMDB alone already covered everything. */
return new class extends Migration
{
    public function up(): void
    {
        // SQLite doesn't drop a column's unique index for free the way MySQL does —
        // the index has to go first or dropColumn() fails against the test suite's
        // sqlite connection.
        Schema::table('shows', function (Blueprint $table) {
            $table->dropUnique(['trakt_id']);
        });
        Schema::table('shows', function (Blueprint $table) {
            $table->dropColumn(['trakt_id', 'resolved_source']);
        });

        Schema::table('movies', function (Blueprint $table) {
            $table->dropUnique(['trakt_id']);
        });
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn(['trakt_id', 'resolved_source']);
        });

        Schema::table('episodes', function (Blueprint $table) {
            $table->dropColumn('trakt_id');
        });
    }

    public function down(): void
    {
        Schema::table('shows', function (Blueprint $table) {
            $table->string('trakt_id')->nullable()->unique();
            $table->string('resolved_source')->nullable();
        });

        Schema::table('movies', function (Blueprint $table) {
            $table->string('trakt_id')->nullable()->unique();
            $table->string('resolved_source')->nullable();
        });

        Schema::table('episodes', function (Blueprint $table) {
            $table->unsignedBigInteger('trakt_id')->nullable();
        });
    }
};
