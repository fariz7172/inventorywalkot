<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginRecaptchaTest extends TestCase
{
    public function test_login_page_contains_recaptcha(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('g-recaptcha');
        $response->assertSee('6LcsZhkuAAAAAAtMqp9exbS4c9u160d1IUtMgX7O');
        $response->assertSee('https://www.google.com/recaptcha/api.js');
    }

    public function test_login_fails_without_recaptcha(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@gmail.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['g-recaptcha-response']);
    }

    public function test_login_fails_with_invalid_recaptcha(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@gmail.com',
            'password' => 'password',
            'g-recaptcha-response' => 'invalid_dummy_token',
        ]);

        $response->assertSessionHasErrors(['g-recaptcha-response']);
    }
}

