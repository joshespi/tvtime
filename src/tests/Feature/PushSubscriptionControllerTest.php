<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        // Exception JSON rendering is scoped to api/* routes (see bootstrap/app.php),
        // so even a JSON request to this web route gets the redirect-based 401 handling.
        $this->postJson('/push-subscriptions', [])->assertRedirect('/login');
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

        $response->assertSessionHasErrors(['endpoint', 'keys.p256dh', 'keys.auth']);
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
