<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One table for every "we already told this user about this thing" record.
        // The subject is polymorphic (episode today, movie today, whatever ships
        // next) because the bookkeeping is identical regardless of media type.
        Schema::create('notifications_sent', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('subject');
            $table->string('channel'); // push, mail
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['user_id', 'subject_type', 'subject_id', 'channel'], 'notifications_sent_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications_sent');
    }
};
