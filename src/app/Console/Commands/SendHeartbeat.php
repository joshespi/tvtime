<?php

namespace App\Console\Commands;

use App\Notifications\Heartbeat;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

#[Signature('app:send-heartbeat')]
#[Description('Email a heartbeat link so the mail account stays active')]
class SendHeartbeat extends Command
{
    public function handle(): void
    {
        $email = config('services.heartbeat.email');

        if (blank($email)) {
            $this->warn('HEARTBEAT_EMAIL is not set, skipping.');

            return;
        }

        $url = config('services.heartbeat.url') ?: route('dashboard');

        Notification::route('mail', $email)->notify(new Heartbeat($url));

        $this->info("Sent heartbeat to {$email}.");
    }
}
