<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Recaptcha implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secretKey = config('services.recaptcha.secret_key');

        if (empty($secretKey)) {
            Log::warning('Google reCAPTCHA secret key is not set in config/services.php or .env');
            $fail('Konfigurasi reCAPTCHA belum lengkap di server.');
            return;
        }

        if (empty($value)) {
            $fail('Verifikasi reCAPTCHA wajib dicentang.');
            return;
        }

        try {
            $response = Http::asForm()->timeout(10)->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret'   => $secretKey,
                'response' => $value,
                'remoteip' => request()->ip(),
            ]);

            if (!$response->successful()) {
                Log::error('reCAPTCHA HTTP error: ' . $response->status());
                $fail('Gagal menghubungi server verifikasi Google reCAPTCHA.');
                return;
            }

            $body = $response->json();

            if (!($body['success'] ?? false)) {
                $errorCodes = isset($body['error-codes']) ? implode(', ', $body['error-codes']) : 'unknown';
                Log::warning('reCAPTCHA validation failed. Error codes: ' . $errorCodes);
                $fail('Verifikasi reCAPTCHA gagal atau kadaluarsa. Silakan centang ulang.');
            }
        } catch (\Throwable $e) {
            Log::error('reCAPTCHA verification exception: ' . $e->getMessage());
            $fail('Terjadi kesalahan saat memverifikasi reCAPTCHA.');
        }
    }
}

