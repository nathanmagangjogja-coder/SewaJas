<?php
// app/Http/Controllers/AuditLogController.php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        // Hanya super_admin yang boleh melihat semua log
        // Admin toko hanya melihat log dari branch-nya
        $user = Auth::user();

        $query = AuditLog::with('user')
            ->when($request->filled('event'), fn($q)   => $q->where('event', $request->event))
            ->when($request->filled('model'), fn($q)   => $q->where('model', $request->model))
            ->when($request->filled('user_id'), fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('search'), fn($q)  => $q->where(function ($q2) use ($request) {
                $q2->where('model_label', 'like', "%{$request->search}%")
                   ->orWhere('ip_address', 'like', "%{$request->search}%");
            }))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'),   fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        // Untuk dropdown filter
        $events    = AuditLog::select('event')->distinct()->pluck('event');
        $models    = AuditLog::select('model')->distinct()->pluck('model');
        $usersList = User::orderBy('name')->get(['id', 'name']);

        // Stats ringkasan hari ini
        $todayStats = [
            'total'   => AuditLog::whereDate('created_at', today())->count(),
            'creates' => AuditLog::whereDate('created_at', today())->where('event', 'like', '%creat%')->count(),
            'updates' => AuditLog::whereDate('created_at', today())->where('event', 'update%')->count(),
            'deletes' => AuditLog::whereDate('created_at', today())->where('event', 'deleted')->count(),
        ];

        return view('admin.audit-logs', compact(
            'query', 'events', 'models', 'usersList', 'todayStats'
        ));
    }

    // Detail satu log (modal / slide-over)
    public function show(AuditLog $auditLog)
    {
        $auditLog->load('user');
        return response()->json([
            'id'               => $auditLog->id,
            'event'            => $auditLog->event,
            'event_label'      => $auditLog->event_label,
            'model'            => $auditLog->model,
            'model_id'         => $auditLog->model_id,
            'model_label'      => $auditLog->model_label,
            'user'             => $auditLog->user?->name ?? 'Sistem',
            'ip_address'       => $auditLog->ip_address,
            'url'              => $auditLog->url,
            'method'           => $auditLog->method,
            'old_values'       => $auditLog->old_values,
            'new_values'       => $auditLog->new_values,
            'meaningful'       => $auditLog->meaningful_changes,
            'created_at'       => $auditLog->created_at->format('d M Y H:i:s'),
        ]);
    }
}