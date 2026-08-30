<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_shows', function (Blueprint $table) {
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('note')->nullable();
        });

        Schema::table('user_movies', function (Blueprint $table) {
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('user_shows', function (Blueprint $table) {
            $table->dropColumn(['rating', 'note']);
        });

        Schema::table('user_movies', function (Blueprint $table) {
            $table->dropColumn(['rating', 'note']);
        });
    }
};
