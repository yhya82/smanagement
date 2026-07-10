<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A guest hitting the root URL is sent to the login page - there's no
     * public marketing page in this app, only the authenticated portal.
     */
    public function test_root_redirects_a_guest_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
