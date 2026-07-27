@extends('Layouts.app')
@section('title', 'Audit Log Aktivitas')

@section('content')
<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="font-playfair text-2xl font-bold" style="color:var(--text-dark)">Audit Log</h1>
            <p class="text-sm mt-0.5" style="color:var(--text-soft)">
                Rekam jejak semua aksi staf — create, edit, hapus, dan pembayaran
            </p>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-4">
            <p class="text-xs mb-1" style="color:var(--text-soft)">Total Log</p>
            <p class="text-2xl font-bold font-playfair" style="color:var(--text-dark)">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs mb-1" style="color:var(--text-soft)">Hari Ini</p>
            <p class="text-2xl font-bold font-playfair" style="color:var(--primary)">{{ $stats['today'] }}</p>
        </div>
        <div class="card p-4" style="border-left:3px solid #EF4444">
            <p class="text-xs mb-1" style="color:var(--text-soft)">Aksi Kritis</p>
            <p class="text-2xl font-bold font-playfair text-red-500">{{ $stats['critical'] }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs mb-1" style="color:var(--text-soft)">Pengguna Aktif</p>
            <p class="text-2xl font-bold font-playfair" style="color:var(--text-dark)">{{ $stats['unique_users'] }}</p>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card p-4">
        <form method="GET" action="{{ route('audit.index') }}"
              class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            @if($isSuperAdmin)
            <div>
                <label class="block text-xs font-semibold mb-1" style="color:var(--text-soft)">Cabang</label>
                <select name="branch_id" class="form-input w-full text-sm">
                    <option value="">Semua Cabang</option>
                    @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected':'' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div>
                <label class="block text-xs font-semibold mb-1" style="color:var(--text-soft)">Jenis Aksi</label>
                <select name="action" class="form-input w-full text-sm">
                    <option value="">Semua</option>
                    @foreach($actions as $act)
                    <option value="{{ $act }}" {{ request('action') == $act ? 'selected':'' }}>
                        {{ str_replace('_',' ', ucfirst($act)) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1" style="color:var(--text-soft)">Staf</label>
                <select name="user_id" class="form-input w-full text-sm">
                    <option value="">Semua Staf</option>
                    @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected':'' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-semibold mb-1" style="color:var(--text-soft)">Dari</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input w-full text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1" style="color:var(--text-soft)">Sampai</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input w-full text-sm">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1" style="color:var(--text-soft)">Cari</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-input w-full text-sm" placeholder="Cari deskripsi...">
            </div>
            <div class="flex items-end gap-2">
                <label class="flex items-center gap-2 text-sm" style="color:var(--text-dark)">
                    <input type="checkbox" name="critical" value="1" {{ request('critical') ? 'checked':'' }}
                           class="rounded">
                    Kritis saja
                </label>
            </div>
            <div class="sm:col-span-2 lg:col-span-2 flex items-end gap-2">
                <button type="submit" class="btn-primary px-4 py-2 text-sm flex items-center gap-2">
                    <i data-lucide="search" class="w-4 h-4"></i> Filter
                </button>
                <a href="{{ route('audit.index') }}" class="btn-secondary px-4 py-2 text-sm">Reset</a>
            </div>
        </form>
    </div>

    {{-- Tabel --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b flex items-center gap-2" style="border-color:var(--border)">
            <i data-lucide="shield-check" class="w-4 h-4" style="color:var(--primary)"></i>
            <h3 class="font-semibold text-sm" style="color:var(--text-dark)">Riwayat Aktivitas</h3>
            <span class="ml-auto text-xs" style="color:var(--text-soft)">{{ $logs->total() }} entri</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr style="background:var(--bg-soft)">
                        <th class="text-left px-4 py-3 text-xs font-semibold" style="color:var(--text-soft)">Waktu</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold" style="color:var(--text-soft)">Staf</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold" style="color:var(--text-soft)">Aksi</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold" style="color:var(--text-soft)">Deskripsi</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold" style="color:var(--text-soft)">IP</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y" style="border-color:var(--border)">
                    @forelse($logs as $log)
                    @php $isCritical = in_array($log->action, $criticalActions); @endphp
                    <tr class="hover:bg-[var(--bg-soft)] transition-colors {{ $isCritical ? 'border-l-2 border-red-400' : '' }}">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <p class="text-xs font-medium" style="color:var(--text-dark)">{{ $log->created_at->format('d/m/Y') }}</p>
                            <p class="text-xs" style="color:var(--text-soft)">{{ $log->created_at->format('H:i:s') }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-sm font-medium" style="color:var(--text-dark)">{{ $log->user?->name ?? 'System' }}</p>
                            <p class="text-xs" style="color:var(--text-soft)">{{ $log->user?->role ?? '' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-lg text-xs font-semibold
                                {{ $isCritical ? 'bg-red-50 text-red-700' : 'bg-blue-50 text-blue-700' }}">
                                {{ $log->action_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 max-w-xs">
                            <p class="text-sm truncate" style="color:var(--text-dark)">{{ $log->description }}</p>
                        </td>
                        <td class="px-4 py-3 text-xs font-mono" style="color:var(--text-soft)">
                            {{ $log->ip_address ?? '-' }}
                        </td>
                        <td class="px-4 py-3">
                            @if($log->has_diff)
                            <a href="{{ route('audit.show', $log) }}"
                               class="text-xs px-2 py-1 rounded-lg border hover:bg-[var(--bg-soft)] transition-colors"
                               style="color:var(--primary); border-color:var(--primary)">
                                Detail
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-sm" style="color:var(--text-soft)">
                            <i data-lucide="inbox" class="w-10 h-10 mx-auto mb-3 opacity-20"></i>
                            <p>Belum ada log aktivitas.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div class="px-4 py-3 border-t" style="border-color:var(--border)">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
@endsection
