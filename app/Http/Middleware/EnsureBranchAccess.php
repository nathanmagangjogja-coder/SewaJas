<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBranchAccess
{
    /**
     * Pastikan admin_toko dan sales tidak bisa akses data branch lain.
     * super_admin bebas akses semua branch.
     *
     * Cara pakai: ->middleware('branch.access')
     *
     * Middleware ini mengecek:
     * 1. Route parameter  : /rentals/{rental}  → cek rental->branch_id
     * 2. Query string     : ?branch_id=2
     * 3. Request body     : branch_id di form POST
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isSuperAdmin()) {
            return $next($request);
        }

        // Cek branch_id dari berbagai sumber
        $requestedBranchId = $this->resolveBranchId($request);

        if ($requestedBranchId && ! $user->canAccessBranch((int) $requestedBranchId)) {
            abort(403, 'Anda hanya dapat mengakses data cabang Anda sendiri.');
        }

        return $next($request);
    }

    private function resolveBranchId(Request $request): ?int
    {
        // Dari route model binding (misal: /rentals/{rental})
        $rental = $request->route('rental');
        if ($rental && isset($rental->branch_id)) {
            return (int) $rental->branch_id;
        }

        // Dari query string atau form input
        $branchId = $request->input('branch_id') ?? $request->route('branch_id');
        if ($branchId) {
            return (int) $branchId;
        }

        return null;
    }
}
