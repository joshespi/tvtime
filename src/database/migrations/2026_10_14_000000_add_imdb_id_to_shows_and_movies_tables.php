<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shows', function (Blueprint $table) {
            $table->string('imdb_id')->nullable();
        });

        Schema::table('movies', function (Blueprint $table) {
            $table->string('imdb_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('shows', function (Blueprint $table) {
            $table->dropColumn('imdb_id');
        });

        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn('imdb_id');
        });
    }
};
