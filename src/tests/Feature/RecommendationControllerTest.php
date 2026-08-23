<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->post('/recommendations/dismiss', ['type' => 'show', 'tmdb_id' => '1'])
            ->assertRedirect('/login');
    }

    public function test_dismiss_records_a_not_interested_title(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/recommendations/dismiss', [
            'type' => 'show',
            'tmdb_id' => '1396',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('dismissed_recommendations', [
            'user_id' => $user->id,
            'type' => 'show',
            'tmdb_id' => '1396',
        ]);
    }

    public function test_dismiss_is_idempotent(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/recommendations/dismiss', ['type' => 'movie', 'tmdb_id' => '603']);
        $this->actingAs($user)->post('/recommendations/dismiss', ['type' => 'movie', 'tmdb_id' => '603']);

        $this->assertDatabaseCount('dismissed_recommendations', 1);
    }

    public function test_dismiss_validates_the_type(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/recommendations/dismiss', ['type' => 'bogus', 'tmdb_id' => '1'])
            ->assertSessionHasErrors('type');
    }
}
