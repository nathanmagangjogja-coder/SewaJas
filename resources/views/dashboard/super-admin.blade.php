@extends('layouts.app')

@section('title', 'Dashboard Super Admin')
@section('page-title', 'Dashboard')
@section('subtitle', 'Selamat datang kembali, ' . auth()->user()->name)

@section('content')
    <div class="space-y-6">

        {{-- Welcome Banner --}}
        <div class="relative overflow-hidden rounded-2xl p-6"
             style="background: linear-gradient(135deg, #4338CA, #7C3AED);">
            <div class="absolute top-0 right-0 w-48 h-48 rounded-full opacity-10"
                 style="background: #FFFFFF; transform: translate(30%, -30%)"></div>
            <div class="relative">
                <p class="text-sm font-medium" style="color: rgba(255,255,255,.7)">Selamat datang kembali,</p>
                <h2 class="font-playfair text-2xl font-bold text-white mt-0.5">{{ auth()->user()->name }}</h2>
                <p class="text-xs mt-2 text-white/70 flex items-center gap-1">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                    Super Admin
                </p>
            </div>
        </div>
                <style>
                .accent { position: relative; padding-left: 1rem; }
                .accent::before { content: ''; position: absolute; left: 0; top: 0; height: 100%; width: 4px; background: var(--accent, #3B82F6); border-radius: 4px 0 0 4px; }
                .stat-card { background: var(--card); padding: 1rem; border-radius: .75rem; }
                .sparkline { position: absolute; right: .75rem; bottom: .5rem; width: 90px; height: 28px; opacity: .9 }

                /* shimmer on hover */
                .accent:hover { animation: shimmer 1.6s linear infinite; }
                @keyframes shimmer {
                    0% { filter: brightness(1); }
                    50% { filter: brightness(1.04); }
                    100% { filter: brightness(1); }
                }
                </style>

        <!-- Stat Cards Row 1 -->
        <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">

            <div class="stat-card stat-card-blue accent" style="--accent: var(--primary)">
                <div class="flex items-start justify-between mb-3">
                    <div class="icon-box icon-box-blue">
                        <i data-lucide="building-2" class="w-5 h-5"></i>
                    </div>
                    <span class="badge badge-blue text-[10px]">Aktif</span>
                </div>
                <p class="text-2xl font-bold font-playfair" style="color: var(--text-dark)">
                    {{ number_format($stats['total_branches']) }}</p>
                <p class="text-xs mt-1" style="color: var(--text-soft)">Total Cabang</p>
            </div>

            <div class="stat-card stat-card-green accent" style="--accent: var(--color-emerald)">
                <div class="flex items-start justify-between mb-3">
                    <div class="icon-box icon-box-green">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                </div>
                <p class="text-2xl font-bold font-playfair" style="color: var(--text-dark)">
                    {{ number_format($stats['total_customers']) }}</p>
                <p class="text-xs mt-1" style="color: var(--text-soft)">Total Customer</p>
            </div>

            <div class="stat-card stat-card-teal accent" style="--accent: var(--color-cyan)">
                <div class="flex items-start justify-between mb-3">
                    <div class="icon-box icon-box-teal">
                        <i data-lucide="shirt" class="w-5 h-5"></i>
                    </div>
                    @if ($stats['overdue_rentals'] > 0)
                        <span class="badge badge-red text-[10px]">{{ $stats['overdue_rentals'] }} Telat</span>
                    @endif
                </div>
                <p class="text-2xl font-bold font-playfair" style="color: var(--text-dark)">
                    {{ number_format($stats['active_rentals']) }}</p>
                <p class="text-xs mt-1" style="color: var(--text-soft)">Penyewaan Aktif</p>
            </div>

            <div class="stat-card stat-card-purple accent" style="--accent: var(--color-violet)">
                <div class="flex items-start justify-between mb-3">
                    <div class="icon-box icon-box-purple">
                        <i data-lucide="receipt-text" class="w-5 h-5"></i>
                    </div>
                    <span class="badge badge-purple text-[10px]">All Time</span>
                </div>
                <p class="text-2xl font-bold font-playfair" style="color: var(--text-dark)">
                    {{ number_format($stats['total_transactions']) }}
                </p>
                <p class="text-xs mt-1" style="color: var(--text-soft)">Total Transaksi</p>
            </div>

            <div class="stat-card stat-card-gold accent" style="--accent: var(--warning)">
                <div class="flex items-start justify-between mb-3">
                    <div class="icon-box icon-box-gold">
                        <i data-lucide="banknote" class="w-5 h-5"></i>
                    </div>
                    <span class="badge badge-amber text-[10px]">Bulan Ini</span>
                </div>
                <p class="text-xl font-bold font-playfair" style="color: var(--text-dark)">Rp
                    {{ number_format($stats['month_revenue'], 0, ',', '.') }}</p>
                <p class="text-xs mt-1" style="color: var(--text-soft)">Pendapatan Bulan Ini</p>
            </div>

            <div class="stat-card stat-card-red accent" style="--accent: #EF4444">
                <div class="flex items-start justify-between mb-3">
                    <div class="icon-box" style="background:#EF444420">
                        <i data-lucide="hand-coins" class="w-5 h-5" style="color:#EF4444"></i>
                    </div>
                    @if($stats['piutang_count'] > 0)
                    <span class="badge badge-red text-[10px]">{{ $stats['piutang_count'] }} Invoice</span>
                    @endif
                </div>
                <p class="text-lg font-bold font-playfair" style="color: {{ $stats['total_piutang'] > 0 ? '#C0392B' : 'var(--text-dark)' }}">
                    Rp {{ number_format($stats['total_piutang'], 0, ',', '.') }}
                </p>
                <p class="text-xs mt-1" style="color: var(--text-soft)">Total Piutang</p>
                <a href="{{ route('reports.outstanding') }}" class="text-[10px] font-medium mt-1 inline-block" style="color: var(--primary)">Lihat Detail →</a>
            </div>
        </div>

        <!-- Secondary Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="card p-4 flex items-center gap-4 accent" style="--accent: var(--color-emerald)">
                <div class="icon-box icon-box-green">
                    <i data-lucide="package-check" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="text-lg font-bold" style="color: var(--text-dark)">
                        {{ number_format($stats['products_available']) }}</p>
                    <p class="text-xs" style="color: var(--text-soft)">Stok Tersedia</p>
                </div>
            </div>

            <div class="card p-4 flex items-center gap-4 accent" style="--accent: var(--primary)">
                <div class="icon-box icon-box-blue">
                    <i data-lucide="package-open" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="text-lg font-bold" style="color: var(--text-dark)">
                        {{ number_format($stats['products_rented']) }}</p>
                    <p class="text-xs" style="color: var(--text-soft)">Sedang Disewa</p>
                </div>
            </div>

            <div class="card p-4 flex items-center gap-4 accent" style="--accent: var(--color-rose)">
                <div class="icon-box icon-box-pink">
                    <i data-lucide="calendar-plus" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="text-lg font-bold" style="color: var(--text-dark)">
                        {{ number_format($stats['today_rentals']) }}</p>
                    <p class="text-xs" style="color: var(--text-soft)">Sewa Hari Ini</p>
                </div>
            </div>

            <div class="card p-4 flex items-center gap-4 accent" style="--accent: var(--color-amber)">
                <div class="icon-box icon-box-orange">
                    <i data-lucide="rotate-ccw" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="text-lg font-bold" style="color: var(--text-dark)">
                        {{ number_format($stats['today_returns']) }}</p>
                    <p class="text-xs" style="color: var(--text-soft)">Kembali Hari Ini</p>
                </div>
            </div>
        </div>

        <!-- Chart & Top Tables -->
        <div class="grid lg:grid-cols-3 gap-6">

            <!-- Monthly Chart -->
            <div class="lg:col-span-2 card p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="font-playfair font-semibold text-base" style="color: var(--text-dark)">Grafik Transaksi
                        </h3>
                        <p class="text-xs mt-0.5" style="color: var(--text-soft)">6 bulan terakhir</p>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="switchChart('revenue')" id="btn-revenue"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all active-chart-btn"
                            style="background: var(--primary); color: #1E1A16;">
                            Pendapatan
                        </button>
                        <button onclick="switchChart('count')" id="btn-count"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
                            style="background: var(--secondary); color: var(--text-soft);">
                            Jumlah
                        </button>
                    </div>
                </div>
                <div id="chart-container" style="height: 240px;"></div>
            </div>

            <!-- Top Products -->
            <div class="card p-6">
                <h3 class="font-playfair font-semibold text-base mb-4" style="color: var(--text-dark)">Jas Paling Laris
                </h3>
                <div class="space-y-3">
                    @foreach ($stats['top_products'] as $i => $product)
                        <div class="flex items-center gap-3">
                            <div class="w-6 h-6 rounded-lg flex items-center justify-center text-xs font-bold flex-shrink-0"
                                style="background: {{ $i === 0 ? 'linear-gradient(135deg, #D6B98C, #C4A478)' : 'var(--secondary)' }}; color: {{ $i === 0 ? '#1E1A16' : 'var(--text-soft)' }}">
                                {{ $i + 1 }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate" style="color: var(--text-dark)">
                                    {{ $product->name }}</p>
                                <div class="mt-1 h-1.5 rounded-full overflow-hidden" style="background: var(--secondary)">
                                    <div class="h-full rounded-full transition-all duration-500"
                                        style="background: linear-gradient(90deg, #D6B98C, #C4A478); width: {{ min(100, ($product->total_rented / max(1, $stats['top_products'][0]->total_rented)) * 100) }}%">
                                    </div>
                                </div>
                            </div>
                            <span class="text-xs font-semibold flex-shrink-0"
                                style="color: var(--primary)">{{ $product->total_rented }}x</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Status Chart & Active Customers -->
        <div class="grid lg:grid-cols-3 gap-6">

            <!-- Donut Chart Status Transaksi -->
            <div class="card p-6">
                <div class="mb-4">
                    <h3 class="font-playfair font-semibold text-base" style="color: var(--text-dark)">
                        Status Transaksi
                    </h3>
                    <p class="text-xs mt-0.5" style="color: var(--text-soft)">Distribusi saat ini</p>
                </div>
                <div id="chart-status" style="height: 220px;"></div>
                <!-- Legend -->
                <div class="grid grid-cols-2 gap-2 mt-4">
                    <div class="flex items-center gap-2">
                        <div class="w-2.5 h-2.5 rounded-full" style="background:#3B82F6"></div>
                        <span class="text-xs" style="color:var(--text-soft)">Disewa</span>
                        <span class="text-xs font-bold ml-auto" style="color:var(--text-dark)">
                            {{ $stats['status_counts']['active'] ?? 0 }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-2.5 h-2.5 rounded-full" style="background:#EF4444"></div>
                        <span class="text-xs" style="color:var(--text-soft)">Telat</span>
                        <span class="text-xs font-bold ml-auto" style="color:var(--text-dark)">
                            {{ $stats['status_counts']['overdue'] ?? 0 }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-2.5 h-2.5 rounded-full" style="background:#10B981"></div>
                        <span class="text-xs" style="color:var(--text-soft)">Dikembalikan</span>
                        <span class="text-xs font-bold ml-auto" style="color:var(--text-dark)">
                            {{ $stats['status_counts']['returned'] ?? 0 }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-2.5 h-2.5 rounded-full" style="background:#9CA3AF"></div>
                        <span class="text-xs" style="color:var(--text-soft)">Dibatalkan</span>
                        <span class="text-xs font-bold ml-auto" style="color:var(--text-dark)">
                            {{ $stats['status_counts']['cancelled'] ?? 0 }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tabel Customer Aktif -->
            <div class="lg:col-span-2 card p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="font-playfair font-semibold text-base" style="color: var(--text-dark)">
                            Customer Aktif
                        </h3>
                        <p class="text-xs mt-0.5" style="color: var(--text-soft)">Sedang menyewa saat ini</p>
                    </div>
                    <a href="{{ route('rentals.index') }}" class="text-xs font-medium" style="color: var(--primary)">
                        Lihat Semua →
                    </a>
                </div>
                <div class="overflow-x-auto overflow-y-auto overscroll-contain" style="max-height:420px; -webkit-overflow-scrolling:touch;">
                    <table class="w-full elegant-table" style="min-width:640px">
                        <thead>
                            <tr>
                                <th class="text-left">Customer</th>
                                <th class="text-left">Barang</th>
                                <th class="text-left">Cabang</th>
                                <th class="text-left">Jatuh Tempo</th>
                                <th class="text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stats['active_customers'] as $rental)
                                <tr>
                                    <td>
                                        <div>
                                            <p class="text-sm font-medium" style="color: var(--text-dark)">
                                                {{ $rental->customer->name }}
                                            </p>
                                            <p class="text-xs" style="color: var(--text-soft)">
                                                {{ $rental->customer->whatsapp }}
                                            </p>
                                        </div>
                                    </td>
                                    <td class="text-sm" style="color: var(--text-dark)">
                                        {{ $rental->items->first()->product->name ?? '-' }}
                                        @if ($rental->items->count() > 1)
                                            <span class="text-xs" style="color: var(--text-soft)">
                                                +{{ $rental->items->count() - 1 }} lainnya
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-gold text-xs">
                                            {{ $rental->branch->code ?? '-' }}
                                        </span>
                                    </td>
                                    {{-- AFTER --}}
                                    <td class="text-sm">
                                        <span
                                            style="color: {{ $rental->return_due_date->isPast() ? '#EF4444' : 'var(--text-dark)' }}">
                                            {{ $rental->return_due_date->format('d M Y') }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($rental->rental_status === 'overdue')
                                            <span class="badge badge-red text-xs">Telat</span>
                                        @else
                                            <span class="badge badge-green text-xs">Disewa</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-6" style="color: var(--text-soft)">
                                        <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 opacity-30"></i>
                                        <p class="text-sm">Tidak ada customer aktif</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Top Branches -->
        <div class="card p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-playfair font-semibold text-base" style="color: var(--text-dark)">Performa Cabang</h3>
                <a href="{{ route('branches.index') }}" class="text-xs font-medium" style="color: var(--primary)">Lihat
                    Semua →</a>
            </div>
            <div class="overflow-x-auto overscroll-contain" style="-webkit-overflow-scrolling:touch;">
                <table class="w-full elegant-table" style="min-width:560px">
                    <thead>
                        <tr>
                            <th class="text-left">#</th>
                            <th class="text-left">Cabang</th>
                            <th class="text-left">Kode</th>
                            <th class="text-right">Total Transaksi</th>
                            <th class="text-right">Total Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($stats['top_branches'] as $i => $branch)
                            <tr>
                                <td>
                                    <span
                                        class="w-6 h-6 rounded-lg flex items-center justify-center text-xs font-bold inline-flex"
                                        style="background: {{ $i === 0 ? 'linear-gradient(135deg, #D6B98C, #C4A478)' : 'var(--secondary)' }}; color: {{ $i === 0 ? '#1E1A16' : 'var(--text-soft)' }}">
                                        {{ $i + 1 }}
                                    </span>
                                </td>
                                <td class="font-medium" style="color: var(--text-dark)">{{ $branch['name'] }}</td>
                                <td><span class="badge badge-gold">{{ $branch['code'] }}</span></td>
                                <td class="text-right font-semibold" style="color: var(--text-dark)">
                                    {{ number_format($branch['rentals_count']) }}</td>
                                <td class="text-right font-semibold" style="color: #D6B98C">
                                    Rp {{ number_format($branch['rentals_sum_total_amount'] ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        const chartData = @json($stats['monthly_chart']);
        const categories = chartData.map(d => d.month);
        const revenues = chartData.map(d => d.revenue);
        const counts = chartData.map(d => d.count);

        let chart;

        function initChart(type = 'revenue') {
            if (chart) chart.destroy();

            const series = type === 'revenue' ? [{
                name: 'Pendapatan',
                data: revenues
            }] : [{
                name: 'Jumlah',
                data: counts
            }];

            chart = new ApexCharts(document.getElementById('chart-container'), {
                series,
                chart: {
                    type: 'area',
                    height: 240,
                    toolbar: {
                        show: false
                    },
                    fontFamily: 'Inter, sans-serif'
                },
                colors: ['#6366F1'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0,
                        stops: [0, 90, 100]
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 2.5
                },
                xaxis: {
                    categories,
                    labels: {
                        style: {
                            colors: '#6B6B6B',
                            fontSize: '11px'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#6B6B6B',
                            fontSize: '11px'
                        },
                        formatter: v => type === 'revenue' ? 'Rp ' + new Intl.NumberFormat('id-ID').format(v) : v
                    }
                },
                grid: {
                    borderColor: '#F0EBE4',
                    strokeDashArray: 4
                },
                tooltip: {
                    y: {
                        formatter: v => type === 'revenue' ? 'Rp ' + new Intl.NumberFormat('id-ID').format(v) : v +
                            ' transaksi'
                    }
                },
                dataLabels: {
                    enabled: false
                },
            });
            chart.render();
        }

        function switchChart(type) {
            initChart(type);
            document.getElementById('btn-revenue').style.cssText = type === 'revenue' ?
                'background: var(--primary); color: #1E1A16;' :
                'background: var(--secondary); color: var(--text-soft);';
            document.getElementById('btn-count').style.cssText = type === 'count' ?
                'background: var(--primary); color: #1E1A16;' :
                'background: var(--secondary); color: var(--text-soft);';
        }

        initChart('revenue');

        // Donut Chart Status Transaksi
        const statusData = @json($stats['status_counts']);

        new ApexCharts(document.getElementById('chart-status'), {
            series: [
                statusData.active ?? 0, // ✅ FIXED: dari 'rented' → 'active'
                statusData.overdue ?? 0,
                statusData.returned ?? 0,
                statusData.cancelled ?? 0,
            ],
            labels: ['Aktif', 'Telat', 'Dikembalikan', 'Dibatalkan'], // ✅ FIXED
            chart: {
                type: 'donut',
                height: 220,
                toolbar: {
                    show: false
                },
                fontFamily: 'Inter, sans-serif'
            },
            colors: ['#6366F1', '#EF4444', '#10B981', '#9CA3AF'],
            legend: {
                show: false
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total',
                                fontSize: '12px',
                                color: '#6B6B6B',
                                formatter: w => w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                            }
                        }
                    }
                }
            },
            dataLabels: {
                enabled: false
            },
            tooltip: {
                y: {
                    formatter: v => v + ' transaksi'
                }
            }
        }).render();

        lucide.createIcons(); // ✅ Pindah ke PALING BAWAH agar semua icon ter-render
    </script>
@endpush