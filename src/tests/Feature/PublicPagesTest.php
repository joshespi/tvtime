<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_manifest_is_served_as_a_webmanifest_with_the_app_name_as_json(): void
    {
        config(['app.name' => 'Show "Tracker" & Co']);

        $response = $this->get('/manifest.webmanifest');

        $response->assertOk();
        $this->assertStringContainsString('application/manifest+json', $response->headers->get('Content-Type'));
        $this->assertSame('Show "Tracker" & Co', $response->json('name'));
    }

    public function test_the_donate_page_hides_the_bitcoin_section_without_an_address(): void
    {
        config(['donate.btc_address' => '']);

        $this->actingAs(User::factory()->create())->get('/donate')
            ->assertOk()
            ->assertDontSee('BTC address');
    }

    public function test_the_donate_page_shows_the_bitcoin_address_when_configured(): void
    {
        config(['donate.btc_address' => 'bc1qexampleaddress']);

        $this->actingAs(User::factory()->create())->get('/donate')
            ->assertOk()
            ->assertSee('bc1qexampleaddress');
    }
}
