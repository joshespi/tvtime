<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shows', function (Blueprint $table) {
            $table->json('cast')->nullable();
        });

        Schema::table('movies', function (Blueprint $table) {
            $table->json('cast')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('shows', function (Blueprint $table) {
            $table->dropColumn('cast');
        });

        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn('cast');
        });
    }
};
