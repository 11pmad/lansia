<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_is_disabled(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(404);

        $postResponse = $this->post('/register', [
            'name' => 'Random User',
            'email' => 'random@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $postResponse->assertStatus(404);
        $this->assertGuest();
    }
}
