<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckKecamatanAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->hasRole('kecamatan_admin')) {
            // Izinkan akses ke rute surat-jalan, rab, laporan, dan stock-opname
            if ($request->is('dashboard/surat-jalan*') || $request->is('dashboard/rab*') || $request->is('dashboard/laporan*') || $request->is('dashboard/stock-opname*')) {
                return $next($request);
            }
            
            // Blokir halaman lain dan arahkan ke surat-jalan
            return redirect('/dashboard/surat-jalan');
        }

        return $next($request);
    }
}
