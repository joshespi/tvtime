<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public function test_unknown_routes_render_the_branded_404(): void
    {
        $this->get('/no-such-page')->assertNotFound()->assertSee('Page not found');
    }

    public function test_the_session_expired_page_renders(): void
    {
        // CSRF verification is skipped under test, so a real 419 can't be
        // provoked — render the view the framework would hand it.
        $this->view('errors.419')->assertSee('Session expired')->assertSee('Reload');
    }
}
