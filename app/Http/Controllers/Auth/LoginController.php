<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\Recaptcha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    /**
     * Maksimal percobaan gagal sebelum akun/IP dijeda.
     */
    protected int $maxAttempts = 3;

    /**
     * Durasi jeda dalam detik (5 menit = 300 detik).
     */
    protected int $decaySeconds = 300;

    public function login(Request $request)
    {
        // 1. Validasi format input dasar & reCAPTCHA
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'g-recaptcha-response' => ['required', new Recaptcha()],
        ], [
            'email.required' => 'Email atau password wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Email atau password wajib diisi.',
            'g-recaptcha-response.required' => 'Silakan centang verifikasi "Saya bukan robot" (reCAPTCHA).',
        ]);

        // 2. Throttle Key (Kombinasi email & IP address pemanggil)
        $throttleKey = Str::transliterate(Str::lower($request->input('email')) . '|' . $request->ip());

        // 3. Cek apakah sedang dalam status lockout / jeda
        if (RateLimiter::tooManyAttempts($throttleKey, $this->maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;

            $waktuTunggu = $minutes > 0
                ? ($remainingSeconds > 0 ? "{$minutes} menit {$remainingSeconds} detik" : "{$minutes} menit")
                : "{$seconds} detik";

            return back()->withErrors([
                'email' => "Terlalu banyak percobaan login yang gagal. Akses dijeda selama 5 menit demi keamanan. Silakan coba lagi dalam {$waktuTunggu}.",
            ])->onlyInput('email');
        }

        $credentials = $request->only('email', 'password');

        // 4. Percobaan Autentikasi
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            // Berhasil login: reset hitungan kesalahan
            RateLimiter::clear($throttleKey);

            $request->session()->regenerate();
            $request->session()->flash('show_opname_reminder', true);

            if (Auth::user()->hasRole('kecamatan_admin')) {
                return redirect()->intended('dashboard/surat-jalan');
            }

            return redirect()->intended('dashboard');
        }

        // 5. Gagal Login: catat penambahan kesalahan dengan decay 300 detik (5 menit)
        RateLimiter::hit($throttleKey, $this->decaySeconds);

        $retriesLeft = RateLimiter::retriesLeft($throttleKey, $this->maxAttempts);

        $pesanError = 'Email atau password tidak sesuai.';
        if ($retriesLeft > 0) {
            $pesanError .= " (Sisa kesempatan: {$retriesLeft}x lagi sebelum akun dijeda 5 menit)";
        } else {
            $pesanError = 'Terlalu banyak percobaan login yang gagal. Akses dijeda selama 5 menit demi keamanan.';
        }

        return back()->withErrors([
            'email' => $pesanError,
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
