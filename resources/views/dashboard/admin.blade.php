@extends('layouts.app')
@section('title', 'Dashboard Admin Toko')
@section('page-title', 'Dashboard')
@section('subtitle', auth()->user()->branch?->name . ' — ' . now()->isoFormat('dddd, D MMMM Y'))

@section('content')
<div class="space-y-5">

    <style>
    .accent { position: relative; padding-left: 1rem; }
    .accent::before { content: ''; position: absolute; left: 0; top: 0; height: 100%; width: 4px; background: var(--accent, #3B82F6); border-radius: 4px 0 0 4px; }
    .stat-card { background: var(--card); padding: 1rem; border-radius: .75rem; }
    .sparkline { position: absolute; right: .75rem; bottom: .5rem; width: 90px; height: 28px; opacity: .9 }
    .accent:hover { animation: shimmer 1.6s linear infinite; }
    @keyframes shimmer { 0%{filter:brightness(1)}50%{filter:brightness(1.04)}100%{filter:brightness(1)} }
    </style>

    {{-- Welcome Banner --}}
    <div class="relative overflow-hidden rounded-2xl p-6"
         style="background: linear-gradient(135deg, #4338CA, #7C3AED);">
        <div class="absolute top-0 right-0 w-48 h-48 rounded-full opacity-10"
             style="background: #FFFFFF; transform: translate(30%, -30%)"></div>
        <div class="relative">
            <p class="text-sm font-medium" style="color: rgba(255,255,255,.7)">Selamat datang kembali,</p>
            <h2 class="font-playfair text-2xl font-bold text-white mt-0.5">{{ auth()->user()->name }}</h2>
            <p class="text-xs mt-2 text-white/70 flex items-center gap-1">
                <i data-lucide="building-2" class="w-3.5 h-3.5"></i>
                {{ auth()->user()->branch?->name }}
            </p>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">

        {{-- Penyewaan Hari Ini --}}
        <a href="{{ route('rentals.index', ['status' => 'today']) }}" class="stat-card block hover:scale-105 transition-transform cursor-pointer">
            <div class="flex items-start justify-between mb-3">
                <div class="p-2.5 rounded-xl" style="background:#D6B98C20">
                    <i data-lucide="calendar-plus" class="w-5 h-5" style="color:#D6B98C"></i>
                </div>
                <span class="badge badge-gold text-[10px]">Hari Ini</span>
            </div>
            <p class="text-3xl font-bold font-playfair" style="color:var(--text-dark)">{{ $stats['today_rentals'] }}</p>
            <p class="text-xs mt-1" style="color:var(--text-soft)">Penyewaan Masuk</p>
        </a>

        {{-- Pengembalian Hari Ini --}}
        <a href="{{ route('rentals.index', ['status' => 'return-today']) }}" class="stat-card block hover:scale-105 transition-transform cursor-pointer">
            <div class="flex items-start justify-between mb-3">
                <div class="p-2.5 rounded-xl" style="background:#10B98120">
                    <i data-lucide="package-check" class="w-5 h-5" style="color:#10B981"></i>
                </div>
                <span class="badge badge-green text-[10px]">Hari Ini</span>
            </div>
            <p class="text-3xl font-bold font-playfair" style="color:var(--text-dark)">{{ $stats['today_returns'] }}</p>
            <p class="text-xs mt-1" style="color:var(--text-soft)">Pengembalian</p>
        </a>

        {{-- Barang Sedang Disewa --}}
        <a href="{{ route('rentals.index', ['status' => 'active']) }}" class="stat-card block hover:scale-105 transition-transform cursor-pointer">
            <div class="flex items-start justify-between mb-3">
                <div class="p-2.5 rounded-xl" style="background:#3B82F620">
                    <i data-lucide="shirt" class="w-5 h-5" style="color:#3B82F6"></i>
                </div>
                @if($stats['overdue_rentals'] > 0)
                <span class="badge badge-red text-[10px]">{{ $stats['overdue_rentals'] }} Telat</span>
                @endif
            </div>
            <p class="text-3xl font-bold font-playfair" style="color:var(--text-dark)">{{ $stats['active_rentals'] }}</p>
            <p class="text-xs mt-1" style="color:var(--text-soft)">Sedang Disewa</p>
        </a>

        {{-- Pendapatan Bulan Ini --}}
        <a href="{{ route('reports.revenue') }}" class="stat-card block hover:scale-105 transition-transform cursor-pointer">
            <div class="flex items-start justify-between mb-3">
                <div class="p-2.5 rounded-xl" style="background:#F59E0B20">
                    <i data-lucide="banknote" class="w-5 h-5" style="color:#F59E0B"></i>
                </div>
                <span class="badge badge-yellow text-[10px]">Bulan Ini</span>
            </div>
            <p class="text-lg font-bold font-playfair" style="color:var(--text-dark)">
                Rp {{ number_format($stats['monthly_revenue'], 0, ',', '.') }}
            </p>
            <p class="text-xs mt-1" style="color:var(--text-soft)">Pendapatan</p>
        </a>

        {{-- Total Piutang --}}
        <a href="{{ route('reports.outstanding') }}" class="stat-card block hover:scale-105 transition-transform cursor-pointer">
            <div class="flex items-start justify-between mb-3">
                <div class="p-2.5 rounded-xl" style="background:#EF444420">
                    <i data-lucide="hand-coins" class="w-5 h-5" style="color:#EF4444"></i>
                </div>
                @if($stats['piutang_count'] > 0)
                <span class="badge badge-red text-[10px]">{{ $stats['piutang_count'] }}</span>
                @endif
            </div>
            <p class="text-lg font-bold font-playfair" style="color:{{ $stats['total_piutang'] > 0 ? '#C0392B' : 'var(--text-dark)' }}">
                Rp {{ number_format($stats['total_piutang'], 0, ',', '.') }}
            </p>
            <p class="text-xs mt-1" style="color:var(--text-soft)">Total Piutang</p>
        </a>
    </div>

    {{-- Baris kedua --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('products.index', ['filter' => 'available']) }}" class="card p-4 flex items-center gap-3 hover:scale-105 transition-transform cursor-pointer">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:#F0FDF4">
                <i data-lucide="package" class="w-5 h-5" style="color:#15803D"></i>
            </div>
            <div>
                <p class="text-xl font-bold" style="color:var(--text-dark)">{{ $stats['products_available'] }}</p>
                <p class="text-xs" style="color:var(--text-soft)">Stok Tersedia</p>
            </div>
        </a>
        <a href="{{ route('rentals.index', ['status' => 'active']) }}" class="card p-4 flex items-center gap-3 hover:scale-105 transition-transform cursor-pointer">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:#EFF6FF">
                <i data-lucide="package-open" class="w-5 h-5" style="color:#1D4ED8"></i>
            </div>
            <div>
                <p class="text-xl font-bold" style="color:var(--text-dark)">{{ $stats['products_rented'] }}</p>
                <p class="text-xs" style="color:var(--text-soft)">Barang Dipinjam</p>
            </div>
        </a>
        <a href="{{ $stats['overdue_rentals'] > 0 ? route('rentals.index', ['status'=>'overdue']) : '#' }}" class="card p-4 flex items-center gap-3 col-span-2 hover:scale-105 transition-transform cursor-pointer">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#FFF1F0">
                <i data-lucide="alert-triangle" class="w-5 h-5" style="color:#C0392B"></i>
            </div>
            <div>
                <p class="text-xl font-bold" style="color:{{ $stats['overdue_rentals'] > 0 ? '#C0392B' : 'var(--text-dark)' }}">
                    {{ $stats['overdue_rentals'] }} Penyewaan
                </p>
                <p class="text-xs" style="color:var(--text-soft)">Melewati Jatuh Tempo</p>
            </div>
            @if($stats['overdue_rentals'] > 0)
            <span class="ml-auto btn-secondary text-xs">
                Lihat →
            </span>
            @endif
        </a>
    </div>

    {{-- Chart + Harus Kembali Hari Ini --}}
    <div class="grid lg:grid-cols-5 gap-5">

        {{-- Grafik --}}
        <div class="lg:col-span-3 card p-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="font-playfair font-semibold" style="color:var(--text-dark)">Grafik Cabang</h3>
                    <p class="text-xs mt-0.5" style="color:var(--text-soft)">6 bulan terakhir — {{ auth()->user()->branch?->name }}</p>
                </div>
            </div>
            <div id="branch-chart" style="height:220px"></div>
        </div>

        {{-- Harus Kembali Hari Ini --}}
        <div class="lg:col-span-2 card overflow-hidden">
            <div class="p-4 border-b flex items-center justify-between" style="border-color:var(--border)">
                <h3 class="font-playfair font-semibold text-sm" style="color:var(--text-dark)">Harus Kembali Hari Ini</h3>
                <span class="badge {{ $stats['due_today']->count() > 0 ? 'badge-red' : 'badge-green' }}">
                    {{ $stats['due_today']->count() }}
                </span>
            </div>
            <div class="overflow-y-auto" style="max-height:280px">
                @forelse($stats['due_today'] as $r)
                <a href="{{ route('rentals.show', $r) }}"
                   class="flex items-center gap-3 p-3 border-b hover:bg-amber-50/30 transition-colors"
                   style="border-color:var(--border)">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                         style="background: {{ $r->rental_status === 'overdue' ? '#FFF1F0' : '#FFF8E7' }}">
                        <i data-lucide="{{ $r->rental_status === 'overdue' ? 'alert-circle' : 'clock' }}"
                           class="w-4 h-4"
                           style="color: {{ $r->rental_status === 'overdue' ? '#C0392B' : '#B7791F' }}"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold truncate" style="color:var(--text-dark)">{{ $r->customer?->name ?? '—' }}</p>
                        <p class="text-[10px] font-mono" style="color:var(--primary)">{{ $r->invoice_number }}</p>
                    </div>
                    <span class="badge badge-{{ $r->status_badge_color }} text-[9px]">{{ $r->status_label }}</span>
                </a>
                @empty
                <div class="py-10 text-center">
                    <i data-lucide="check-circle-2" class="w-8 h-8 mx-auto mb-2" style="color:#D6B98C"></i>
                    <p class="text-xs" style="color:var(--text-soft)">Tidak ada yang jatuh tempo hari ini</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Status Laundry (super_admin & admin_toko) --}}
    @unless(auth()->user()->isSales())
    @php
    $laundryStats = [
        'menunggu_laundry' => \App\Models\Laundry::menungguLaundry()->count(),
        'dalam_laundry'    => \App\Models\Laundry::dalamLaundry()->count(),
        'siap_disewakan'   => \App\Models\Laundry::siapDisewakan()->count(),
    ];
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        {{-- Menunggu Laundry --}}
        <a href="{{ route('laundry.menunggu') }}" class="stat-card block hover:scale-105 transition-transform">
            <div class="flex items-start justify-between mb-3">
                <div class="p-2.5 rounded-xl" style="background:#F59E0B20">
                    <i data-lucide="clock" class="w-5 h-5" style="color:#F59E0B"></i>
                </div>
                <span class="badge badge-yellow text-[10px]">Perlu Diproses</span>
            </div>
            <p class="text-3xl font-bold font-playfair" style="color:var(--text-dark)">
                {{ $laundryStats['menunggu_laundry'] }}
            </p>
            <p class="text-xs mt-1" style="color:var(--text-soft)">Menunggu Laundry</p>
        </a>

        {{-- Dalam Laundry --}}
        <a href="{{ route('laundry.dalam') }}" class="stat-card block hover:scale-105 transition-transform">
            <div class="flex items-start justify-between mb-3">
                <div class="p-2.5 rounded-xl" style="background:#3B82F620">
                    <i data-lucide="loader-2" class="w-5 h-5" style="color:#3B82F6"></i>
                </div>
                <span class="badge badge-blue text-[10px]">Sedang Diproses</span>
            </div>
            <p class="text-3xl font-bold font-playfair" style="color:var(--text-dark)">
                {{ $laundryStats['dalam_laundry'] }}
            </p>
            <p class="text-xs mt-1" style="color:var(--text-soft)">Dalam Laundry</p>
        </a>

        {{-- Siap Disewakan --}}
        <a href="{{ route('laundry.siap') }}" class="stat-card block hover:scale-105 transition-transform">
            <div class="flex items-start justify-between mb-3">
                <div class="p-2.5 rounded-xl" style="background:#10B98120">
                    <i data-lucide="check-circle-2" class="w-5 h-5" style="color:#10B981"></i>
                </div>
                <span class="badge badge-green text-[10px]">Stok Siap</span>
            </div>
            <p class="text-3xl font-bold font-playfair" style="color:var(--text-dark)">
                {{ $laundryStats['siap_disewakan'] }}
            </p>
            <p class="text-xs mt-1" style="color:var(--text-soft)">Siap Disewakan</p>
        </a>

    </div>
    @endunless

    {{-- Transaksi Terbaru --}}
    <div class="card overflow-hidden">
        <div class="p-5 border-b flex items-center justify-between" style="border-color:var(--border)">
            <h3 class="font-playfair font-semibold" style="color:var(--text-dark)">Transaksi Terbaru</h3>
            <a href="{{ route('rentals.index') }}" class="text-xs" style="color:var(--primary)">Lihat Semua →</a>
        </div>
        <table class="w-full elegant-table">
            <thead>
                <tr>
                    <th class="text-left">Invoice</th>
                    <th class="text-left">Customer</th>
                    <th class="text-left">Jatuh Tempo</th>
                    <th class="text-right">Total</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Bayar</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stats['recent_transactions'] as $r)
                <tr>
                    <td>
                        <a href="{{ route('rentals.show', $r) }}" class="font-mono text-xs font-semibold hover:underline" style="color:var(--primary)">
                            {{ $r->invoice_number }}
                        </a>
                    </td>
                    <td class="text-sm font-medium" style="color:var(--text-dark)">{{ $r->customer?->name ?? '—' }}</td>
                    <td class="text-sm {{ $r->rental_status === 'overdue' ? 'text-red-500 font-semibold' : '' }}"
                        style="{{ $r->rental_status !== 'overdue' ? 'color:var(--text-soft)' : '' }}">
                        {{ $r->return_due_date->format('d M Y') }}
                    </td>
                    <td class="text-right font-semibold text-sm" style="color:var(--text-dark)">
                        Rp {{ number_format($r->total_amount, 0, ',', '.') }}
                    </td>
                    <td class="text-center"><span class="badge badge-{{ $r->status_badge_color }}">{{ $r->status_label }}</span></td>
                    <td class="text-center">
                        <span class="badge {{ match($r->payment_status){ 'paid'=>'badge-green','partial'=>'badge-yellow',default=>'badge-red' } }}">
                            {{ $r->payment_status_label }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="py-10 text-center text-sm" style="color:var(--text-soft)">Belum ada transaksi</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach([
            ['route' => 'rentals.create',   'icon' => 'plus-circle',   'label' => 'Buat Penyewaan', 'color' => '#D6B98C'],
            ['route' => 'rentals.scan',      'icon' => 'scan-qr-code',  'label' => 'Scan QR',        'color' => '#3B82F6'],
            ['route' => 'customers.create',  'icon' => 'user-plus',     'label' => 'Tambah Customer','color' => '#10B981'],
            ['route' => 'products.create',   'icon' => 'package-plus',  'label' => 'Tambah Produk',  'color' => '#F59E0B'],
        ] as $action)
        <a href="{{ route($action['route']) }}"
           class="card p-4 flex flex-col items-center gap-2 text-center transition-all hover:scale-105 cursor-pointer">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center"
                 style="background: {{ $action['color'] }}20">
                <i data-lucide="{{ $action['icon'] }}" class="w-6 h-6" style="color:{{ $action['color'] }}"></i>
            </div>
            <p class="text-xs font-semibold" style="color:var(--text-dark)">{{ $action['label'] }}</p>
        </a>
        @endforeach
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
const d = @json($stats['monthly_chart']);
new ApexCharts(document.getElementById('branch-chart'), {
    series: [
        { name: 'Pendapatan', type: 'area', data: d.map(x => x.revenue) },
        { name: 'Transaksi',  type: 'line', data: d.map(x => x.count) }
    ],
    chart: { height: 220, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
    colors: ['#D6B98C', '#3B82F6'],
    fill: { type: ['gradient','solid'], gradient: { opacityFrom: 0.4, opacityTo: 0 } },
    stroke: { curve: 'smooth', width: [2.5, 2] },
    xaxis: { categories: d.map(x => x.month), labels: { style: { colors: '#6B6B6B', fontSize: '10px' } } },
    yaxis: [
        { labels: { formatter: v => 'Rp ' + new Intl.NumberFormat('id-ID').format(v), style: { colors: '#6B6B6B', fontSize: '9px' } } },
        { opposite: true, labels: { formatter: v => v + ' trx', style: { colors: '#6B6B6B', fontSize: '9px' } } }
    ],
    grid: { borderColor: '#F0EBE4', strokeDashArray: 4 },
    tooltip: { y: [{ formatter: v => 'Rp ' + new Intl.NumberFormat('id-ID').format(v) }, { formatter: v => v + ' transaksi' }] },
    dataLabels: { enabled: false },
    legend: { position: 'top', fontSize: '11px' }
}).render();
lucide.createIcons();
</script>
@endpush