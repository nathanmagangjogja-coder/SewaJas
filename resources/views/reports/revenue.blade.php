{{-- resources/views/reports/revenue.blade.php --}}
@extends('layouts.app')

@section('title', 'Laporan Pendapatan')

@section('content')

{{-- ── PAGE HEADER ── --}}
<div class="flex items-start justify-between mb-6">
    <div>
        <h1 class="font-playfair text-2xl font-semibold" style="color: var(--text-dark)">
            Laporan Pendapatan
        </h1>
        <p class="text-sm mt-0.5" style="color: var(--text-soft)">
            @if ($isSuperAdmin)
                @if ($selectedBranchId)
                    Cabang: <span class="font-semibold" style="color: var(--text-dark)">
                        {{ $branches->firstWhere('id', $selectedBranchId)?->name }}
                    </span>
                @else
                    Menampilkan data <span class="font-semibold" style="color: var(--text-dark)">semua cabang</span>
                @endif
            @else
                Cabang: <span class="font-semibold" style="color: var(--text-dark)">
                    {{ auth()->user()->branch?->name ?? '-' }}
                </span>
            @endif
        </p>
    </div>
</div>

{{-- ── FILTER BAR ── --}}
@include('reports.partials.filter-bar')

{{-- ── STAT CARDS ── --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

    {{-- Total Pendapatan --}}
    <div class="stat-card">
        <div class="flex items-start justify-between relative z-10">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider mb-1" style="color: var(--text-soft)">
                    Total Pendapatan
                </p>
                <p class="font-playfair text-2xl font-bold" style="color: var(--text-dark)">
                    Rp {{ number_format($totalRevenue, 0, ',', '.') }}
                </p>
            </div>
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background: var(--gold-light)">
                <i data-lucide="trending-up" class="w-5 h-5" style="color: var(--gold)"></i>
            </div>
        </div>
    </div>

    {{-- Total Denda --}}
    <div class="stat-card">
        <div class="flex items-start justify-between relative z-10">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider mb-1" style="color: var(--text-soft)">
                    Total Denda Keterlambatan
                </p>
                <p class="font-playfair text-2xl font-bold" style="color: var(--text-dark)">
                    Rp {{ number_format($totalLateFee ?? 0, 0, ',', '.') }}
                </p>
            </div>
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background: #FFF1F0">
                <i data-lucide="alert-triangle" class="w-5 h-5" style="color: #C0392B"></i>
            </div>
        </div>
    </div>

    {{-- Total Hari Transaksi --}}
    <div class="stat-card">
        <div class="flex items-start justify-between relative z-10">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider mb-1" style="color: var(--text-soft)">
                    Total Hari Transaksi
                </p>
                <p class="font-playfair text-2xl font-bold" style="color: var(--text-dark)">
                    {{ $revenueData->count() }}
                    <span class="text-base font-normal" style="color: var(--text-soft)">hari</span>
                </p>
            </div>
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background: #EFF6FF">
                <i data-lucide="calendar-days" class="w-5 h-5" style="color: #1D4ED8"></i>
            </div>
        </div>
    </div>

</div>

{{-- ── TABEL DATA ── --}}
<div class="card overflow-hidden">
    <div class="px-5 py-4 flex items-center justify-between"
         style="border-bottom: 1px solid var(--border)">
        <h2 class="font-semibold text-sm" style="color: var(--text-dark)">
            Rincian Pendapatan per Hari
        </h2>
        <span class="text-xs" style="color: var(--text-soft)">
            {{ $revenueData->count() }} entri
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="elegant-table w-full">
            <thead>
                <tr>
                    <th class="text-left">Tanggal</th>
                    <th class="text-right">Total Rental</th>
                    <th class="text-right">Denda</th>
                    <th class="text-right">Pendapatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($revenueData as $row)
                    <tr>
                        <td style="color: var(--text-dark)">
                            {{ \Carbon\Carbon::parse($row->date)->isoFormat('dddd, D MMMM Y') }}
                        </td>
                        <td class="text-right" style="color: var(--text-soft)">
                            {{ number_format($row->total_rentals) }}
                        </td>
                        <td class="text-right">
                            @if ($row->total_late_fee > 0)
                                <span style="color: #C0392B" class="font-medium">
                                    Rp {{ number_format($row->total_late_fee, 0, ',', '.') }}
                                </span>
                            @else
                                <span style="color: var(--text-soft)">—</span>
                            @endif
                        </td>
                        <td class="text-right font-semibold" style="color: #15803D">
                            Rp {{ number_format($row->total_revenue, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-12">
                            <div class="flex flex-col items-center gap-2">
                                <i data-lucide="inbox" class="w-8 h-8 opacity-30"
                                   style="color: var(--text-soft)"></i>
                                <p class="text-sm" style="color: var(--text-soft)">
                                    Tidak ada data untuk periode ini
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>

            @if ($revenueData->isNotEmpty())
                <tfoot>
                    <tr style="background: linear-gradient(135deg, #deaf69ff, #deaf69ff);
                                border-top: 2px solid var(--border)">
                        <td class="font-bold text-sm px-4 py-3" style="color: var(--text-dark)">
                            Total Keseluruhan
                        </td>
                        <td class="text-right font-bold text-sm px-4 py-3" style="color: var(--text-dark)">
                            {{ number_format($revenueData->sum('total_rentals')) }}
                        </td>
                        <td class="text-right font-bold text-sm px-4 py-3" style="color: #C0392B">
                            Rp {{ number_format($totalLateFee ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="text-right font-bold text-sm px-4 py-3" style="color: #15803D">
                            Rp {{ number_format($totalRevenue, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

@push('scripts')
<script>lucide.createIcons();</script>
@endpush

@endsection