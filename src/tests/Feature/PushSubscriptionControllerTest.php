<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_get_a_json_401_instead_of_a_redirect(): void
    {
        // bootstrap/app.php's shouldRenderJsonWhen() is additive to Laravel's
        // default expectsJson() check, not a replacement for it — a JSON
        // request to any web route still gets a JSON error response, which is
        // what every caller here (pushNotifications() in app.js) actually reads.
        $response = $this->postJson('/push-subscriptions', []);

        $response->assertUnauthorized();
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_store_subscribes_the_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/push-subscriptions', [
            'endpoint' => 'https://push.example.com/abc',
            'keys' => ['p256dh' => 'public-key', 'auth' => 'auth-token'],
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'subscribed']);
        $this->assertDatabaseHas('push_subscriptions', ['endpoint' => 'https://push.example.com/abc']);
    }

    public function test_store_validates_the_payload(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/push-subscriptions', []);

        // A JSON request now gets the validation errors back as JSON (422),
        // rather than a redirect carrying them in the session — see the
        // shouldRenderJsonWhen() fix referenced above.
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['endpoint', 'keys.p256dh', 'keys.auth']);
    }

    public function test_destroy_unsubscribes_the_user(): void
    {
        $user = User::factory()->create();
        $user->updatePushSubscription(
            endpoint: 'https://push.example.com/abc',
            key: 'public-key',
            token: 'auth-token',
        );

        $response = $this->actingAs($user)->deleteJson('/push-subscriptions', [
            'endpoint' => 'https://push.example.com/abc',
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'unsubscribed']);
        $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => 'https://push.example.com/abc']);
    }
}
