<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureBranchScope
 * Memastikan user yang bukan super_admin hanya bisa akses
 * data yang sesuai dengan branch_id mereka.
 */
class EnsureBranchScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Super admin boleh akses semua cabang
        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        // Admin & Sales WAJIB punya branch_id.
        // FIX: sebelumnya user langsung kena abort(403) polos tanpa arahan jelas,
        // dan tetap dalam sesi login (bingung: sudah login tapi semua halaman 403).
        // Sekarang: logout paksa + redirect ke halaman login dengan pesan yang
        // jelas, supaya user tahu harus menghubungi Super Admin, bukan mengira
        // aplikasinya error / mencoba login berulang-ulang.
        if (is_null($user->branch_id)) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with(
                'error',
                'Akun Anda belum dikaitkan dengan cabang manapun. Hubungi Super Admin untuk melengkapi data cabang sebelum bisa login.'
            );
        }

        // User tidak aktif
        if (!$user->is_active) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Akun Anda telah dinonaktifkan.');
        }

        return $next($request);
    }
}