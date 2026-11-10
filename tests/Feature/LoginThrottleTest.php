<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Bersihkan throttle rate limiter sebelum tes
        $throttleKey = Str::transliterate('test@example.com|127.0.0.1');
        RateLimiter::clear($throttleKey);
    }

    public function test_login_shows_retries_left_and_locks_after_3_attempts(): void
    {
        // Mock reCAPTCHA Google HTTP verification agar selalu sukses pada pengujian throttling
        \Illuminate\Support\Facades\Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => \Illuminate\Support\Facades\Http::response([
                'success' => true,
            ]),
        ]);

        // Percobaan 1: Gagal
        $response1 = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword1',
            'g-recaptcha-response' => 'valid-mock-token',
        ]);
        $response1->assertSessionHasErrors('email');
        $this->assertStringContainsString('Sisa kesempatan: 2x lagi', session('errors')->first('email'));

        // Percobaan 2: Gagal
        $response2 = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword2',
            'g-recaptcha-response' => 'valid-mock-token',
        ]);
        $response2->assertSessionHasErrors('email');
        $this->assertStringContainsString('Sisa kesempatan: 1x lagi', session('errors')->first('email'));

        // Percobaan 3: Gagal (Habis)
        $response3 = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword3',
            'g-recaptcha-response' => 'valid-mock-token',
        ]);
        $response3->assertSessionHasErrors('email');
        $this->assertStringContainsString('Terlalu banyak percobaan login yang gagal', session('errors')->first('email'));

        // Percobaan 4: Terkunci (Lockout 5 menit)
        $response4 = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword4',
            'g-recaptcha-response' => 'valid-mock-token',
        ]);
        $response4->assertSessionHasErrors('email');
        $this->assertStringContainsString('Akses dijeda selama 5 menit', session('errors')->first('email'));
    }
}
