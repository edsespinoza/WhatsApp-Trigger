<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/whatstrigger/login');

        $response->assertStatus(200);
    }

    public function test_root_redirects_unauthenticated_users(): void
    {
        $response = $this->get('/');

        $response->assertRedirect();
    }
}
