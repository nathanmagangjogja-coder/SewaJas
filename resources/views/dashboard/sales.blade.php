@extends('layouts.app')
@section('title', 'Dashboard Sales')
@section('page-title', 'Dashboard')
@section('subtitle', now()->isoFormat('dddd, D MMMM Y'))

@section('content')
<div class="space-y-5">

    <style>
    .accent { position: relative; padding-left: 1rem; }
    .accent::before { content: ''; position: absolute; left: 0; top: 0; height: 100%; width: 4px; background: var(--accent, #3B82F6); border-radius: 4px 0 0 4px; }
    .stat-card { background: var(--card); padding: 1rem; border-radius: .75rem; }
    .sparkline { position: absolute; right: .75rem; bottom: .5rem; width: 90px; height: 28px; opacity: .9 }
    .accent:hover { animation: shimmer 1.6s linear infinite; }
    @keyframes shimmer { 0%{filter:brightness(1)}50%{filter:brightness(1.04)}100%{filter:brightness(1)} }
    .mini-bar { width: 100%; border-radius: 6px 6px 2px 2px; transition: height .4s ease; min-height: 4px; }
    </style>

    {{-- Welcome --}}
    <div class="relative overflow-hidden rounded-2xl p-5 sm:p-6"
         style="background: linear-gradient(135deg, #4338CA, #7C3AED);">
        <div class="absolute top-0 right-0 w-48 h-48 rounded-full opacity-10"
             style="background:#FFFFFF; transform:translate(30%,-30%)"></div>
        <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-sm" style="color: rgba(255,255,255,.7)">Halo, Selamat datang kembalii </p>
                <h2 class="font-playfair text-xl sm:text-2xl font-bold text-white mt-0.5">{{ auth()->user()->name }}</h2>
                <div class="flex items-center gap-2 mt-2">
                    <span class="text-xs px-2.5 py-1 rounded-full font-semibold" style="background:rgba(255,255,255,.2);color:#FFFFFF">SALES</span>
                    <span class="text-xs flex items-center gap-1" style="color: rgba(255,255,255,.7)">
                        <i data-lucide="building-2" class="w-3.5 h-3.5"></i>
                        {{ auth()->user()->branch?->name }}
                    </span>
                </div>
            </div>
            <div class="flex gap-4 sm:gap-6">
                <div class="text-center sm:text-right">
                    <p class="text-2xl font-bold font-playfair text-white">{{ $stats['my_rentals_month'] }}</p>
                    <p class="text-[11px]" style="color: rgba(255,255,255,.7)">Transaksi Saya Bulan Ini</p>
                </div>
                <div class="w-px" style="background: rgba(255,255,255,.2)"></div>
                <div class="text-center sm:text-right">
                    <p class="text-2xl font-bold font-playfair text-white">{{ $stats['my_rentals_today'] }}</p>
                    <p class="text-[11px]" style="color: rgba(255,255,255,.7)">Transaksi Saya Hari Ini</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Hari Ini --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="stat-card text-center">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center mx-auto mb-3" style="background:#D6B98C20">
                <i data-lucide="calendar-plus" class="w-5 h-5" style="color:#D6B98C"></i>
            </div>
            <p class="text-3xl font-bold font-playfair" style="color:var(--text-dark)">{{ $stats['today_rentals'] }}</p>
            <p class="text-xs mt-1" style="color:var(--text-soft)">Sewa Hari Ini</p>
        </div>
        <div class="stat-card text-center">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center mx-auto mb-3" style="background:#10B98120">
                <i data-lucide="package-check" class="w-5 h-5" style="color:#10B981"></i>
            </div>
            <p class="text-3xl font-bold font-playfair" style="color:var(--text-dark)">{{ $stats['today_returns'] }}</p>
            <p class="text-xs mt-1" style="color:var(--text-soft)">Kembali Hari Ini</p>
        </div>
        <div class="stat-card text-center">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center mx-auto mb-3" style="background:#F59E0B20">
                <i data-lucide="clock" class="w-5 h-5" style="color:#F59E0B"></i>
            </div>
            <p class="text-3xl font-bold font-playfair" style="color:{{ $stats['due_today_count'] > 0 ? '#C0392B' : 'var(--text-dark)' }}">
                {{ $stats['due_today_count'] }}
            </p>
            <p class="text-xs mt-1" style="color:var(--text-soft)">Harus Kembali</p>
        </div>
        <div class="stat-card text-center">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center mx-auto mb-3" style="background:#8B5CF620">
                <i data-lucide="user-plus" class="w-5 h-5" style="color:#8B5CF6"></i>
            </div>
            <p class="text-3xl font-bold font-playfair" style="color:var(--text-dark)">{{ $stats['new_customers_month'] }}</p>
            <p class="text-xs mt-1" style="color:var(--text-soft)">Customer Baru Bulan Ini</p>
        </div>
    </div>

    {{-- Aksi Cepat - sesuai hak akses sales.
         FIX (redesign): sebelumnya grid-cols-2 tetap (4 kartu besar berukuran
         sama, monoton, dan di layar HP jadi 2x2 kotak-kotak tinggi yang
         makan banyak scroll). Sekarang dibuat hierarki visual: 1 tombol
         utama besar & menonjol (aksi paling sering dipakai sales) + 3 tombol
         kompak sebaris di bawahnya — tetap 3 kolom bahkan di layar paling
         sempit sekalipun, jadi jauh lebih ringkas & tidak monoton. --}}
    <div class="card p-5 sm:p-6">
        <h3 class="font-playfair font-semibold text-base mb-4" style="color:var(--text-dark)">Aksi Cepat</h3>

        {{-- Aksi utama — ditonjolkan beda dari yang lain (background gelap +
             ikon emas, ada panah "lanjut" yang bergerak saat hover/tap) --}}
        <a href="{{ route('rentals.create') }}"
           class="group relative flex items-center gap-4 p-4 sm:p-5 rounded-2xl overflow-hidden mb-3 transition-transform active:scale-[0.98]"
           style="background:linear-gradient(135deg, var(--primary-dark), var(--primary))">
            <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full pointer-events-none" style="background:rgba(255,255,255,0.08)"></div>
            <div class="absolute -right-2 bottom-0 w-16 h-16 rounded-full pointer-events-none" style="background:rgba(255,255,255,0.06)"></div>
            <div class="relative w-12 h-12 sm:w-14 sm:h-14 rounded-2xl flex items-center justify-center flex-shrink-0" style="background:linear-gradient(135deg,#FDE68A,#F59E0B)">
                <i data-lucide="plus-circle" class="w-6 h-6 sm:w-7 sm:h-7" style="color:#78350F"></i>
            </div>
            <div class="relative flex-1 min-w-0">
                <p class="font-bold text-white text-sm sm:text-base">Buat Penyewaan</p>
                <p class="text-xs mt-0.5" style="color:rgba(255,255,255,0.7)">Input transaksi baru</p>
            </div>
            <i data-lucide="arrow-right" class="relative w-5 h-5 flex-shrink-0 transition-transform group-hover:translate-x-1" style="color:rgba(255,255,255,0.6)"></i>
        </a>

        {{-- Aksi sekunder — kompak, tetap 3 kolom sebaris walau di layar
             sempit, tiap tombol punya warna aksen sendiri biar tidak
             monoton tapi ukurannya jauh lebih kecil dari aksi utama. --}}
        <div class="grid grid-cols-3 gap-2 sm:gap-3">

            <a href="{{ route('rentals.scan') }}"
               class="flex flex-col items-center gap-2 py-3 px-1.5 sm:py-4 rounded-xl transition-all active:scale-95 hover:-translate-y-0.5"
               style="background:var(--surf-sky)">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center" style="background:var(--color-sky)">
                    <i data-lucide="scan-qr-code" class="w-4.5 h-4.5 sm:w-5 sm:h-5 text-white"></i>
                </div>
                <p class="text-[10.5px] sm:text-xs font-semibold text-center leading-tight" style="color:var(--text-dark)">Scan QR /<br>Kembali</p>
            </a>

            <a href="{{ route('customers.index') }}"
               class="flex flex-col items-center gap-2 py-3 px-1.5 sm:py-4 rounded-xl transition-all active:scale-95 hover:-translate-y-0.5"
               style="background:var(--surf-emerald)">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center" style="background:var(--color-emerald)">
                    <i data-lucide="users" class="w-4.5 h-4.5 sm:w-5 sm:h-5 text-white"></i>
                </div>
                <p class="text-[10.5px] sm:text-xs font-semibold text-center leading-tight" style="color:var(--text-dark)">Cari<br>Customer</p>
            </a>

            <a href="{{ route('customers.create') }}"
               class="flex flex-col items-center gap-2 py-3 px-1.5 sm:py-4 rounded-xl transition-all active:scale-95 hover:-translate-y-0.5"
               style="background:rgba(139,92,246,0.10)">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center" style="background:var(--color-violet)">
                    <i data-lucide="user-plus" class="w-4.5 h-4.5 sm:w-5 sm:h-5 text-white"></i>
                </div>
                <p class="text-[10.5px] sm:text-xs font-semibold text-center leading-tight" style="color:var(--text-dark)">Tambah<br>Customer</p>
            </a>
        </div>
    </div>

    {{-- Performa Saya 7 Hari Terakhir + Produk Favorit --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Mini bar chart --}}
        <div class="lg:col-span-2 card p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-playfair font-semibold text-sm" style="color:var(--text-dark)">Performa Saya — 7 Hari Terakhir</h3>
                <span class="text-xs font-semibold" style="color:var(--primary)">
                    Total: {{ collect($stats['weekly_activity'])->sum('count') }} transaksi
                </span>
            </div>
            @php $maxCount = max(1, collect($stats['weekly_activity'])->max('count')); @endphp
            <div class="flex items-end justify-between gap-2 sm:gap-3" style="height:120px">
                @foreach($stats['weekly_activity'] as $day)
                <div class="flex-1 flex flex-col items-center justify-end h-full">
                    <span class="text-[10px] font-semibold mb-1" style="color:var(--text-soft)">{{ $day['count'] }}</span>
                    <div class="mini-bar" style="height:{{ $day['count'] > 0 ? max(10, ($day['count'] / $maxCount) * 88) : 4 }}px;
                        background: {{ $day['date'] === now()->format('d/m') ? 'var(--primary)' : 'var(--primary-tint)' }}"></div>
                    <span class="text-[10px] mt-1.5" style="color:var(--text-soft)">{{ $day['label'] }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Produk favorit saya bulan ini --}}
        <div class="card p-5">
            <h3 class="font-playfair font-semibold text-sm mb-4" style="color:var(--text-dark)">Produk Sering Saya Sewakan</h3>
            @forelse($stats['top_products_mine'] as $i => $p)
            <div class="flex items-center gap-3 {{ !$loop->last ? 'mb-3' : '' }}">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold flex-shrink-0"
                     style="background:var(--primary-tint); color:var(--primary)">{{ $i + 1 }}</div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium truncate" style="color:var(--text-dark)">{{ $p->product_name }}</p>
                </div>
                <span class="text-xs font-semibold flex-shrink-0" style="color:var(--text-soft)">{{ $p->total_qty }}x</span>
            </div>
            @empty
            <div class="py-6 text-center">
                <i data-lucide="shirt" class="w-8 h-8 mx-auto mb-2" style="color:var(--border)"></i>
                <p class="text-xs" style="color:var(--text-soft)">Belum ada data bulan ini</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Barang Harus Kembali Hari Ini --}}
    @if($stats['due_today']->count() > 0)
    <div class="card overflow-hidden">
        <div class="p-4 border-b flex items-center gap-2" style="border-color:var(--border); background:#FFF8E7">
            <i data-lucide="alert-triangle" class="w-4 h-4" style="color:#B7791F"></i>
            <h3 class="font-semibold text-sm" style="color:#B7791F">Harus Kembali Hari Ini — {{ $stats['due_today']->count() }} Item</h3>
        </div>
        @foreach($stats['due_today'] as $r)
        <div class="flex items-center gap-3 p-4 border-b hover:bg-[var(--bg-soft)] transition-colors"
             style="border-color:var(--border)">
            <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                 style="background: {{ $r->rental_status==='overdue' ? '#FFF1F0' : '#FFF8E7' }}">
                <i data-lucide="{{ $r->rental_status==='overdue' ? 'alert-circle' : 'clock' }}" class="w-5 h-5"
                   style="color: {{ $r->rental_status==='overdue' ? '#C0392B' : '#B7791F' }}"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-sm" style="color:var(--text-dark)">{{ $r->customer?->name ?? '—' }}</p>
                <p class="text-xs font-mono" style="color:var(--primary)">{{ $r->invoice_number }}</p>
                <div class="flex flex-wrap gap-1 mt-1">
                    @foreach($r->items->take(2) as $item)
                    <span class="text-[10px] px-1.5 py-0.5 rounded" style="background:var(--secondary);color:var(--text-soft)">{{ $item->product_name }}</span>
                    @endforeach
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('rentals.scan') }}" class="btn-primary py-1.5 px-3 text-xs">
                    <i data-lucide="scan-qr-code" class="w-3.5 h-3.5"></i>
                    Scan
                </a>
                <a href="{{ route('rentals.show', $r) }}" class="btn-secondary py-1.5 px-3 text-xs">
                    Detail
                </a>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Transaksi Saya Hari Ini --}}
        <div class="card overflow-hidden">
            <div class="p-4 border-b" style="border-color:var(--border)">
                <h3 class="font-playfair font-semibold text-sm" style="color:var(--text-dark)">Transaksi Saya Hari Ini</h3>
            </div>
            <div class="overflow-y-auto overscroll-contain" style="max-height:360px; -webkit-overflow-scrolling:touch;">
            @forelse($stats['my_transactions'] as $r)
            <a href="{{ route('rentals.show', $r) }}"
               class="flex items-center gap-3 p-3 border-b hover:bg-[var(--bg-soft)] transition-colors" style="border-color:var(--border)">
                <div class="flex-1 min-w-0">
                    <p class="font-mono text-xs font-semibold" style="color:var(--primary)">{{ $r->invoice_number }}</p>
                    <p class="text-sm font-medium" style="color:var(--text-dark)">{{ $r->customer?->name ?? '—' }}</p>
                </div>
                <div class="text-right">
                    <p class="font-semibold text-sm" style="color:var(--text-dark)">Rp {{ number_format($r->total_amount,0,',','.') }}</p>
                    <span class="badge badge-{{ $r->status_badge_color }} text-[9px]">{{ $r->status_label }}</span>
                </div>
            </a>
            @empty
            <div class="py-10 text-center">
                <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2" style="color:var(--border)"></i>
                <p class="text-sm" style="color:var(--text-soft)">Belum ada transaksi hari ini</p>
                <a href="{{ route('rentals.create') }}" class="btn-primary mt-3 inline-flex">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Buat Penyewaan
                </a>
            </div>
            @endforelse
            </div>
        </div>

        {{-- Aktivitas Terbaru Cabang --}}
        <div class="card overflow-hidden">
            <div class="p-4 border-b flex items-center justify-between" style="border-color:var(--border)">
                <h3 class="font-playfair font-semibold text-sm" style="color:var(--text-dark)">Aktivitas Terbaru Cabang</h3>
                <a href="{{ route('rentals.index') }}" class="text-xs font-semibold" style="color:var(--primary)">Lihat Semua</a>
            </div>
            <div class="overflow-y-auto overscroll-contain" style="max-height:360px; -webkit-overflow-scrolling:touch;">
            @forelse($stats['recent_rentals'] as $r)
            <a href="{{ route('rentals.show', $r) }}"
               class="flex items-center gap-3 p-3 border-b hover:bg-[var(--bg-soft)] transition-colors" style="border-color:var(--border)">
                <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0" style="background:var(--secondary)">
                    <i data-lucide="shirt" class="w-4 h-4" style="color:var(--primary)"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium truncate" style="color:var(--text-dark)">{{ $r->customer?->name ?? '—' }}</p>
                    <p class="text-[11px]" style="color:var(--text-soft)">{{ $r->created_at->diffForHumans() }}</p>
                </div>
                <span class="badge badge-{{ $r->status_badge_color }} text-[9px] flex-shrink-0">{{ $r->status_label }}</span>
            </a>
            @empty
            <div class="py-10 text-center">
                <i data-lucide="activity" class="w-8 h-8 mx-auto mb-2" style="color:var(--border)"></i>
                <p class="text-sm" style="color:var(--text-soft)">Belum ada aktivitas</p>
            </div>
            @endforelse
            </div>
        </div>
    </div>

    {{-- Info: Fitur yang TIDAK bisa diakses Sales --}}
    <div class="card p-4" style="background:var(--bg-main); border:1px dashed var(--border)">
        <p class="text-xs font-semibold mb-2" style="color:var(--text-soft)">
            <i data-lucide="info" class="w-3.5 h-3.5 inline mr-1"></i>
            Informasi Hak Akses Role Sales
        </p>
        <div class="flex flex-wrap gap-2">
            @foreach(['Kelola Produk', 'Laporan Keuangan', 'Blacklist Customer', 'Export Excel/PDF', 'Manajemen User', 'Kelola Cabang'] as $restricted)
            <span class="text-[10px] px-2 py-1 rounded-full flex items-center gap-1" style="background:#FFF1F0;color:#C0392B;border:1px solid #FECACA">
                <i data-lucide="lock" class="w-2.5 h-2.5"></i>
                {{ $restricted }}
            </span>
            @endforeach
        </div>
    </div>

</div>
@endsection