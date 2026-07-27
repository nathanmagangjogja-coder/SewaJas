<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * LOKASI FILE: app/Http/Controllers/Admin/AuditLogController.php
 *
 * FIX BindingResolutionException:
 *   Pastikan folder Admin ada: app/Http/Controllers/Admin/
 *   Namespace HARUS: App\Http\Controllers\Admin
 *   Route import: use App\Http\Controllers\Admin\AuditLogController;
 */
class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $user         = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();

        $query = ActivityLog::with(['user', 'branch'])
            ->orderBy('created_at', 'desc');

        // Branch scope
        if (!$isSuperAdmin) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filters
        if ($request->filled('action'))     $query->where('action', $request->action);
        if ($request->filled('user_id'))    $query->where('user_id', $request->user_id);
        if ($request->filled('date_from'))  $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))    $query->whereDate('created_at', '<=', $request->date_to);
        if ($request->boolean('critical'))  $query->critical();
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('description', 'like', "%$s%")->orWhere('action', 'like', "%$s%"));
        }

        $logs = $query->paginate(25)->withQueryString();

        // Dropdown data
        $branches = $isSuperAdmin ? Branch::orderBy('name')->get() : collect();
        $actions  = ActivityLog::when(!$isSuperAdmin, fn($q) => $q->where('branch_id', $user->branch_id))
                        ->distinct()->orderBy('action')->pluck('action');
        $users    = User::when(!$isSuperAdmin, fn($q) => $q->where('branch_id', $user->branch_id))
                        ->orderBy('name')->get(['id','name','role']);

        // Stats
        $base  = ActivityLog::when(!$isSuperAdmin, fn($q) => $q->where('branch_id', $user->branch_id));
        $stats = [
            'total'        => (clone $base)->count(),
            'today'        => (clone $base)->whereDate('created_at', today())->count(),
            'critical'     => (clone $base)->critical()->count(),
            'unique_users' => (clone $base)->distinct('user_id')->count('user_id'),
        ];

        $criticalActions = [
            'delete_rental','cancel_rental','delete_product',
            'delete_user','delete_customer','delete_package',
        ];

        return view('admin.audit-logs.index', compact(
            'logs','branches','actions','users',
            'isSuperAdmin','criticalActions','stats'
        ));
    }

    public function show(ActivityLog $log)
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin() && $log->branch_id !== $user->branch_id) {
            abort(403);
        }
        $log->load('user','branch');
        return view('admin.audit-logs.show', compact('log'));
    }
}
