<?php

namespace Tests\Feature\Console;

use App\Notifications\Heartbeat;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendHeartbeatTest extends TestCase
{
    public function test_emails_the_configured_address(): void
    {
        Notification::fake();
        config(['services.heartbeat.email' => 'me@example.com']);

        $this->artisan('app:send-heartbeat')->assertSuccessful();

        Notification::assertSentOnDemand(
            Heartbeat::class,
            fn (Heartbeat $notification, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'me@example.com',
        );
    }

    public function test_links_the_dashboard_unless_a_url_is_configured(): void
    {
        $this->assertSentLink(route('dashboard'));

        config(['services.heartbeat.url' => 'https://example.com/keepalive']);

        $this->assertSentLink('https://example.com/keepalive');
    }

    public function test_skips_when_no_address_is_configured(): void
    {
        Notification::fake();
        config(['services.heartbeat.email' => null]);

        $this->artisan('app:send-heartbeat')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_is_scheduled(): void
    {
        // withSchedule() registers on Artisan::starting, so boot the console first.
        Artisan::all();

        $event = collect(app(Schedule::class)->events())
            ->sole(fn ($event) => str_contains($event->command ?? '', 'app:send-heartbeat'));

        $this->assertSame('0 6 * * 1', $event->expression);
        $this->assertSame('America/Denver', $event->timezone);
    }

    private function assertSentLink(string $expected): void
    {
        Notification::fake();
        config(['services.heartbeat.email' => 'me@example.com']);

        $this->artisan('app:send-heartbeat')->assertSuccessful();

        Notification::assertSentOnDemand(
            Heartbeat::class,
            fn (Heartbeat $notification, array $channels, AnonymousNotifiable $notifiable) => $notification->toMail($notifiable)->actionUrl === $expected,
        );
    }
}
