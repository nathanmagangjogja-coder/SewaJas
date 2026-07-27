@extends('layouts.app')
@section('title', 'Laporan Pengembalian')
@section('page-title', 'Laporan Pengembalian')
@section('subtitle', 'Monitoring pengembalian, keterlambatan, dan denda per paket')

@section('content')
<div class="space-y-6">

    {{-- ── SUMMARY CARDS ──────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="card p-4 flex items-center gap-3 lg:col-span-1">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background:#10B98115">
                <i data-lucide="check-circle" class="w-5 h-5" style="color:#10B981"></i>
            </div>
            <div>
                <p class="text-xs" style="color:var(--text-soft)">Sudah Dikembalikan</p>
                <p class="text-2xl font-bold font-playfair" style="color:var(--text-dark)">
                    {{ $summary['returned'] }}
                </p>
            </div>
        </div>
        <div class="card p-4 flex items-center gap-3 lg:col-span-1">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background:#F59E0B15">
                <i data-lucide="clock" class="w-5 h-5" style="color:#F59E0B"></i>
            </div>
            <div>
                <p class="text-xs" style="color:var(--text-soft)">Jatuh Tempo Hari Ini</p>
                <p class="text-2xl font-bold font-playfair" style="color:var(--text-dark)">
                    {{ $summary['due_today'] }}
                </p>
            </div>
        </div>
        <div class="card p-4 flex items-center gap-3 lg:col-span-1">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background:#EF444415">
                <i data-lucide="alert-circle" class="w-5 h-5" style="color:#EF4444"></i>
            </div>
            <div>
                <p class="text-xs" style="color:var(--text-soft)">Terlambat</p>
                <p class="text-2xl font-bold font-playfair text-red-500">{{ $summary['overdue'] }}</p>
            </div>
        </div>
        <div class="card p-4 flex items-center gap-3 lg:col-span-1">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background:#D9770615">
                <i data-lucide="banknote" class="w-5 h-5" style="color:#D97706"></i>
            </div>
            <div>
                <p class="text-xs" style="color:var(--text-soft)">Total Denda Terkumpul</p>
                <p class="text-lg font-bold font-playfair" style="color:#D97706">
                    Rp {{ number_format($summary['total_late_fee'], 0, ',', '.') }}
                </p>
            </div>
        </div>
        <div class="card p-4 flex items-center gap-3 lg:col-span-1">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background:#8B5CF615">
                <i data-lucide="file-warning" class="w-5 h-5" style="color:#8B5CF6"></i>
            </div>
            <div>
                <p class="text-xs" style="color:var(--text-soft)">Pernah Terlambat</p>
                <p class="text-2xl font-bold font-playfair" style="color:#8B5CF6">
                    {{ $summary['total_late_count'] }}
                </p>
            </div>
        </div>
    </div>

    {{-- ── TERLAMBAT — BUTUH TINDAKAN ─────────────────────────────────── --}}
    @if($overdue->count() > 0)
    <div class="card overflow-hidden border-l-4" style="border-left-color:#EF4444">
        <div class="p-4 border-b flex items-center justify-between" style="border-color:var(--border)">
            <div class="flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-red-500"></i>
                <h2 class="font-semibold text-sm text-red-600">
                    Terlambat Dikembalikan ({{ $overdue->count() }})
                </h2>
            </div>
            <span class="text-xs px-2 py-1 rounded-lg font-semibold"
                  style="background:#FEF2F2; color:#EF4444">
                Butuh Tindakan Segera
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full elegant-table">
                <thead>
                    <tr>
                        <th class="text-left">Invoice</th>
                        <th class="text-left">Customer</th>
                        <th class="text-left">Paket</th>
                        <th class="text-left">Jatuh Tempo</th>
                        <th class="text-center">Terlambat</th>
                        <th class="text-right">Est. Denda</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($overdue as $r)
                    <tr class="bg-red-50/10">
                        <td class="font-mono text-xs font-semibold" style="color:var(--primary)">
                            {{ $r->invoice_number }}
                        </td>
                        <td>
                            <p class="font-medium text-sm" style="color:var(--text-dark)">
                                {{ $r->customer?->name ?? '-' }}
                            </p>
                            @if($r->customer?->phone)
                            <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/\D/', '', $r->customer->phone)) }}?text={{ urlencode('Halo ' . $r->customer->name . ', sewa jas ' . $r->invoice_number . ' sudah terlambat ' . $r->live_late_days . ' hari. Mohon segera dikembalikan.') }}"
                               target="_blank"
                               class="text-xs flex items-center gap-1 mt-0.5" style="color:#25D366">
                                <i data-lucide="message-circle" class="w-3 h-3"></i>
                                {{ $r->customer->phone }}
                            </a>
                            @endif
                        </td>
                        <td class="text-sm" style="color:var(--text-soft)">
                            {{ $r->package?->name ?? 'Tanpa Paket' }}
                        </td>
                        <td class="text-sm font-semibold text-red-500">
                            {{ $r->return_due_date?->format('d M Y') }}
                        </td>
                        <td class="text-center">
                            <span class="badge badge-red">
                                {{ $r->live_late_days }} hari
                            </span>
                        </td>
                        <td class="text-right font-bold" style="color:#D97706">
                            Rp {{ number_format($r->estimated_late_fee, 0, ',', '.') }}
                            @if($r->package)
                            <p class="text-xs font-normal" style="color:var(--text-soft)">
                                ({{ number_format($r->package->penalty_percent, 0) }}%/hari)
                            </p>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('rentals.show', $r) }}"
                               class="btn-sm btn-primary text-xs">
                                Lihat
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ── JATUH TEMPO HARI INI ─────────────────────────────────────────── --}}
    @if($dueToday->count() > 0)
    <div class="card overflow-hidden border-l-4" style="border-left-color:#F59E0B">
        <div class="p-4 border-b" style="border-color:var(--border)">
            <div class="flex items-center gap-2">
                <i data-lucide="clock" class="w-4 h-4 text-amber-500"></i>
                <h2 class="font-semibold text-sm text-amber-600">
                    Jatuh Tempo Hari Ini ({{ $dueToday->count() }})
                </h2>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full elegant-table">
                <thead>
                    <tr>
                        <th class="text-left">Invoice</th>
                        <th class="text-left">Customer</th>
                        <th class="text-left">Paket</th>
                        <th class="text-right">Total</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dueToday as $r)
                    <tr>
                        <td class="font-mono text-xs font-semibold" style="color:var(--primary)">
                            {{ $r->invoice_number }}
                        </td>
                        <td>
                            <p class="font-medium text-sm" style="color:var(--text-dark)">
                                {{ $r->customer?->name }}
                            </p>
                            @if($r->customer?->phone)
                            <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/\D/', '', $r->customer->phone)) }}?text={{ urlencode('Halo ' . $r->customer->name . ', sewa jas ' . $r->invoice_number . ' jatuh tempo hari ini. Mohon segera dikembalikan.') }}"
                               target="_blank" class="text-xs" style="color:#25D366">
                                <i data-lucide="message-circle" class="w-3 h-3 inline"></i>
                                {{ $r->customer->phone }}
                            </a>
                            @endif
                        </td>
                        <td class="text-sm" style="color:var(--text-soft)">
                            {{ $r->package?->name ?? '-' }}
                        </td>
                        <td class="text-right font-semibold" style="color:var(--text-dark)">
                            Rp {{ number_format($r->total_amount, 0, ',', '.') }}
                        </td>
                        <td class="text-center">
                            <span class="badge badge-yellow">{{ $r->status_label }}</span>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('rentals.show', $r) }}"
                               class="btn-sm btn-primary text-xs">Proses</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ── DENDA PER PAKET ─────────────────────────────────────────────── --}}
    @if($lateByPackage->count() > 0)
    <div class="card p-5">
        <div class="flex items-center gap-2 mb-4">
            <i data-lucide="pie-chart" class="w-4 h-4" style="color:var(--primary)"></i>
            <h3 class="font-playfair font-semibold" style="color:var(--text-dark)">
                Statistik Keterlambatan per Paket
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr style="background:var(--bg-soft)">
                        <th class="text-left px-4 py-2.5 text-xs font-semibold" style="color:var(--text-soft)">Paket</th>
                        <th class="text-right px-4 py-2.5 text-xs font-semibold" style="color:var(--text-soft)">Total Transaksi</th>
                        <th class="text-right px-4 py-2.5 text-xs font-semibold" style="color:var(--text-soft)">Pernah Terlambat</th>
                        <th class="text-right px-4 py-2.5 text-xs font-semibold" style="color:var(--text-soft)">% Terlambat</th>
                        <th class="text-right px-4 py-2.5 text-xs font-semibold" style="color:var(--text-soft)">Rata-rata Hari Telat</th>
                        <th class="text-right px-4 py-2.5 text-xs font-semibold" style="color:var(--text-soft)">Total Denda</th>
                    </tr>
                </thead>
                <tbody class="divide-y" style="border-color:var(--border)">
                    @foreach($lateByPackage as $row)
                    <tr class="hover:bg-[var(--bg-soft)]">
                        <td class="px-4 py-3 font-medium" style="color:var(--text-dark)">
                            {{ $row->package?->name ?? 'Tanpa Paket' }}
                            @if($row->package)
                            <span class="text-xs ml-1" style="color:var(--text-soft)">
                                ({{ $row->package->duration_label }})
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">{{ $row->total_rentals }}</td>
                        <td class="px-4 py-3 text-right text-amber-600 font-medium">{{ $row->late_count }}</td>
                        <td class="px-4 py-3 text-right">
                            @php $pct = $row->total_rentals > 0 ? round(($row->late_count / $row->total_rentals) * 100) : 0; @endphp
                            <span class="px-2 py-0.5 rounded text-xs font-semibold
                                {{ $pct >= 30 ? 'bg-red-100 text-red-700' : ($pct >= 10 ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700') }}">
                                {{ $pct }}%
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right" style="color:var(--text-soft)">
                            {{ $row->late_count > 0 ? number_format($row->avg_late_days, 1) : '-' }} hari
                        </td>
                        <td class="px-4 py-3 text-right font-bold" style="color:#D97706">
                            Rp {{ number_format($row->total_late_fee, 0, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ── FILTER & RIWAYAT PENGEMBALIAN ──────────────────────────────── --}}
    <div class="card p-4">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            @if($isSuperAdmin)
            <div>
                <label class="block text-xs font-semibold mb-1.5" style="color:var(--text-soft)">Cabang</label>
                <select name="branch_id" class="form-input text-sm">
                    <option value="">Semua Cabang</option>
                    @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ $selectedBranchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div>
                <label class="block text-xs font-semibold mb-1.5" style="color:var(--text-soft)">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-input text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1.5" style="color:var(--text-soft)">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="form-input text-sm">
            </div>
            <button type="submit" class="btn-primary px-4 py-2 text-sm">Tampilkan</button>
            <a href="{{ route('reports.returns') }}" class="btn-secondary px-4 py-2 text-sm">Reset</a>
            <a href="{{ route('reports.returns.pdf', request()->all()) }}"
               class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold border transition-colors hover:bg-[var(--bg-soft)]"
               style="color:var(--text-soft); border-color:var(--border)">
                <i data-lucide="download" class="w-4 h-4"></i>
                Export PDF
            </a>
        </form>
    </div>

    {{-- Tabel Riwayat --}}
    <div class="card overflow-hidden">
        <div class="p-4 border-b" style="border-color:var(--border)">
            <h3 class="font-semibold text-sm" style="color:var(--text-dark)">
                Riwayat Pengembalian
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full elegant-table">
                <thead>
                    <tr>
                        <th class="text-left">Invoice</th>
                        <th class="text-left">Customer</th>
                        <th class="text-left">Paket</th>
                        <th class="text-left">Tgl Sewa</th>
                        <th class="text-left">Tgl Kembali</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Denda</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returned as $r)
                    <tr class="{{ $r->late_fee > 0 ? 'bg-amber-50/10' : '' }}">
                        <td class="font-mono text-xs font-semibold" style="color:var(--primary)">
                            <a href="{{ route('rentals.show', $r) }}" class="hover:underline">
                                {{ $r->invoice_number }}
                            </a>
                        </td>
                        <td>
                            <p class="font-medium text-sm" style="color:var(--text-dark)">
                                {{ $r->customer?->name }}
                            </p>
                        </td>
                        <td class="text-xs" style="color:var(--text-soft)">
                            {{ $r->package?->name ?? '-' }}
                        </td>
                        <td class="text-sm" style="color:var(--text-soft)">
                            {{ $r->rental_date?->format('d/m/Y') }}
                        </td>
                        <td class="text-sm" style="color:var(--text-soft)">
                            {{ $r->actual_return_date?->format('d/m/Y') ?? '-' }}
                            @if($r->overdue_days > 0)
                            <span class="badge badge-red ml-1 text-xs">
                                +{{ $r->overdue_days }} hari
                            </span>
                            @endif
                        </td>
                        <td class="text-right font-semibold" style="color:var(--text-dark)">
                            Rp {{ number_format($r->total_amount, 0, ',', '.') }}
                        </td>
                        <td class="text-right font-bold" style="color:{{ $r->late_fee > 0 ? '#D97706' : 'var(--text-soft)' }}">
                            {{ $r->late_fee > 0 ? 'Rp ' . number_format($r->late_fee, 0, ',', '.') : '-' }}
                        </td>
                        <td class="text-center">
                            <span class="badge badge-green text-xs">{{ $r->status_label }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-10 text-center text-sm" style="color:var(--text-soft)">
                            <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 opacity-20"></i>
                            <p>Belum ada data pengembalian.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($returned->hasPages())
        <div class="p-4 border-t" style="border-color:var(--border)">
            {{ $returned->links() }}
        </div>
        @endif
    </div>

</div>
@endsection