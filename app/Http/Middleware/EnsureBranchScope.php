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

        // Admin & Sales WAJIB punya branch_id
        if (is_null($user->branch_id)) {
            abort(403, 'Akun Anda belum dikaitkan dengan cabang manapun. Hubungi Super Admin.');
        }

        // User tidak aktif
        if (!$user->is_active) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Akun Anda telah dinonaktifkan.');
        }

        return $next($request);
    }
}
