{{--
    layouts/sidebar.blade.php
    Redesigned with light glassmorphism and colorful accents.
--}}

{{-- ── LOGO & TOGGLE ── --}}
<div class="sidebar-header flex items-center px-4 py-5 border-b flex-shrink-0"
     style="border-color:var(--sidebar-divider); min-height:72px">
    <div class="sidebar-brand flex items-center gap-3 overflow-hidden min-w-0">

        {{-- Monogram --}}
        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background: var(--sidebar-logo-grad);">
            <i data-lucide="crown" class="w-5 h-5" style="color:#FFFFFF"></i>
        </div>

        {{-- Brand text --}}
        <div x-show="sidebarOpen || sidebarMobileOpen"
             x-transition:enter="transition-opacity duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="overflow-hidden">
            <p class="font-playfair font-bold text-base leading-tight whitespace-nowrap tracking-wide"
               style="color:var(--sidebar-text);">
                SewaJas
            </p>
            <p class="text-[10px] whitespace-nowrap uppercase tracking-widest"
               style="color:var(--sidebar-accent); letter-spacing:0.15em">System</p>
        </div>
    </div>

    {{-- Desktop collapse --}}
    <button @click="sidebarOpen = !sidebarOpen"
            class="sidebar-header-toggle ml-auto p-1.5 rounded-lg hover:bg-[var(--sidebar-hover-bg)] transition flex-shrink-0 hidden lg:flex"
            :title="sidebarOpen ? 'Tutup sidebar' : 'Buka sidebar'">
        <i data-lucide="panel-left-close" x-show="sidebarOpen"   class="w-4 h-4" style="color:var(--sidebar-text-muted)"></i>
        <i data-lucide="panel-left-open"  x-show="!sidebarOpen"  class="w-4 h-4" style="color:var(--sidebar-text-muted)"></i>
    </button>

    {{-- Mobile close --}}
    <button @click="sidebarMobileOpen = false"
            class="ml-auto p-1.5 rounded-lg hover:bg-[var(--sidebar-hover-bg)] transition flex-shrink-0 lg:hidden">
        <i data-lucide="x" class="w-4 h-4" style="color:var(--sidebar-text-muted)"></i>
    </button>
</div>

{{-- ── ROLE BADGE ── --}}
@auth
<div x-show="sidebarOpen || sidebarMobileOpen"
     class="px-4 py-3 border-b flex-shrink-0" style="border-color:var(--sidebar-divider)">
    @if(auth()->user()->isSuperAdmin())
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full flex-shrink-0" style="background:var(--sidebar-accent)"></span>
            <div>
                <p class="text-[11px] font-bold leading-tight tracking-widest" style="color:var(--sidebar-accent)">SUPER ADMIN</p>
                <p class="text-[10px]" style="color:var(--sidebar-text-muted)">Akses penuh semua cabang</p>
            </div>
        </div>
    @elseif(auth()->user()->isAdminToko())
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full flex-shrink-0" style="background:var(--sb-sky)"></span>
            <div>
                <p class="text-[11px] font-bold leading-tight tracking-widest" style="color:var(--sb-sky)">ADMIN TOKO</p>
                <p class="text-[10px] truncate" style="color:var(--sidebar-text-muted)">{{ auth()->user()->branch?->name }}</p>
            </div>
        </div>
    @else
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full flex-shrink-0" style="background:var(--sb-mint)"></span>
            <div>
                <p class="text-[11px] font-bold leading-tight tracking-widest" style="color:var(--sb-mint)">SALES</p>
                <p class="text-[10px] truncate" style="color:var(--sidebar-text-muted)">{{ auth()->user()->branch?->name }}</p>
            </div>
        </div>
    @endif
</div>
@endauth

{{-- ════════════════════════════════════════════════
     NAVIGASI UTAMA
════════════════════════════════════════════════ --}}
<nav class="flex-1 py-3 overflow-y-auto overflow-x-hidden sidebar-nav">

    {{-- ─── DASHBOARD ─── --}}
    <div class="mb-1">
        <a href="{{ route('dashboard') }}"
           class="sidebar-item flex items-center gap-3 px-3 py-2.5 text-sm
                  {{ request()->routeIs('dashboard') ? 'active' : '' }}"
           @click="sidebarMobileOpen = false">
            <i data-lucide="layout-dashboard" class="w-4 h-4 flex-shrink-0 nav-icon-blue"></i>
            <span x-show="sidebarOpen || sidebarMobileOpen" class="whitespace-nowrap">Dashboard</span>
            <span x-show="!sidebarOpen && !sidebarMobileOpen" class="sidebar-tooltip">Dashboard</span>
        </a>
    </div>

    {{-- ─── TRANSAKSI ─── --}}
    <div x-show="sidebarOpen || sidebarMobileOpen" class="px-3 pt-4 pb-1">
        <p class="nav-section-label">Transaksi</p>
    </div>
    <div x-show="!sidebarOpen && !sidebarMobileOpen"
         class="mx-3 my-1.5 border-t hidden lg:block" style="border-color:var(--sidebar-divider)"></div>

    <div class="space-y-0.5">
        @php
            $ov = \App\Models\Rental::where('rental_status', 'overdue')
                ->when(!auth()->user()->isSuperAdmin(), fn($q) => $q->where('branch_id', auth()->user()->branch_id))
                ->count();
        @endphp

        {{-- Daftar Penyewaan --}}
        <a href="{{ route('rentals.index') }}"
           class="sidebar-item group relative flex items-center gap-3 px-3 py-2.5 text-sm
                  {{ request()->routeIs('rentals.index') ? 'active active-blue' : '' }}"
           @click="sidebarMobileOpen = false">
            <i data-lucide="shirt" class="w-4 h-4 flex-shrink-0 nav-icon-blue"></i>
            <span x-show="sidebarOpen || sidebarMobileOpen" class="flex-1 whitespace-nowrap">Penyewaan</span>
            @if($ov > 0)
                <span x-show="sidebarOpen || sidebarMobileOpen"
                      class="text-[10px] px-1.5 py-0.5 rounded-full font-bold leading-none text-white"
                      style="background:var(--danger)">
                    {{ $ov > 99 ? '99+' : $ov }}
                </span>
                <span x-show="!sidebarOpen && !sidebarMobileOpen"
                      class="absolute top-2 right-2 w-2 h-2 rounded-full hidden lg:block"
                      style="background:var(--danger)"></span>
            @endif
            <span x-show="!sidebarOpen && !sidebarMobileOpen" class="sidebar-tooltip">Penyewaan</span>
        </a>

        {{-- Buat Penyewaan --}}
        <a href="{{ route('rentals.create') }}"
           class="sidebar-item group relative flex items-center gap-3 px-3 py-2.5 text-sm
                  {{ request()->routeIs('rentals.create') ? 'active active-green' : '' }}"
           @click="sidebarMobileOpen = false">
            <i data-lucide="circle-plus" class="w-4 h-4 flex-shrink-0 nav-icon-green"></i>
            <span x-show="sidebarOpen || sidebarMobileOpen" class="whitespace-nowrap">Buat Penyewaan</span>
            <span x-show="!sidebarOpen && !sidebarMobileOpen" class="sidebar-tooltip">Buat Penyewaan</span>
        </a>

        {{-- Scan QR --}}
        <a href="{{ route('rentals.scan') }}"
           class="sidebar-item group relative flex items-center gap-3 px-3 py-2.5 text-sm
                  {{ request()->routeIs('rentals.scan') ? 'active active-teal' : '' }}"
           @click="sidebarMobileOpen = false">
            <i data-lucide="scan-qr-code" class="w-4 h-4 flex-shrink-0 nav-icon-teal"></i>
            <span x-show="sidebarOpen || sidebarMobileOpen" class="whitespace-nowrap">Scan QR / Kembali</span>
            <span x-show="!sidebarOpen && !sidebarMobileOpen" class="sidebar-tooltip">Scan QR</span>
        </a>
    </div>

    {{-- ─── LAUNDRY ─── --}}
    @unless(auth()->user()->isSales())
    @php
        $laundryTotal = \App\Models\Laundry::query()
            ->when(!auth()->user()->isSuperAdmin(),
                fn($q) => $q->whereHas('transaksi', fn($r) => $r->where('branch_id', auth()->user()->branch_id))
            )
            ->whereIn('status', ['menunggu_laundry', 'dalam_laundry', 'siap_disewakan'])
            ->count();
    @endphp

    <div x-show="sidebarOpen || sidebarMobileOpen" class="px-3 pt-4 pb-1">
        <p class="nav-section-label">Laundry</p>
    </div>
    <div x-show="!sidebarOpen && !sidebarMobileOpen"
         class="mx-3 my-1.5 border-t hidden lg:block" style="border-color:var(--sidebar-divider)"></div>

    <div class="space-y-0.5">
        <a href="{{ route('laundry.index') }}"
           class="sidebar-item group relative flex items-center gap-3 px-3 py-2.5 text-sm
                  {{ request()->routeIs('laundry.*') ? 'active active-teal' : '' }}"
           @click="sidebarMobileOpen = false">
            <i data-lucide="washing-machine" class="w-4 h-4 flex-shrink-0 nav-icon-teal"></i>
            <span x-show="sidebarOpen || sidebarMobileOpen" class="flex-1 whitespace-nowrap">Laundry</span>
            @if($laundryTotal > 0)
                <span x-show="sidebarOpen || sidebarMobileOpen"
                      class="text-[10px] px-1.5 py-0.5 rounded-full font-bold leading-none text-white"
                      style="background:var(--color-seafoam)">
                    {{ $laundryTotal > 99 ? '99+' : $laundryTotal }}
                </span>
                <span x-show="!sidebarOpen && !sidebarMobileOpen"
                      class="absolute top-2 right-2 w-2 h-2 rounded-full hidden lg:block"
                      style="background:var(--color-seafoam)"></span>
            @endif
            <span x-show="!sidebarOpen && !sidebarMobileOpen" class="sidebar-tooltip">Laundry</span>
        </a>
    </div>
    @endunless

    {{-- ─── MASTER DATA ─── --}}
    @unless(auth()->user()->isSales())
    <div x-show="sidebarOpen || sidebarMobileOpen" class="px-3 pt-4 pb-1">
        <p class="nav-section-label">Master Data</p>
    </div>
    <div x-show="!sidebarOpen && !sidebarMobileOpen"
         class="mx-3 my-1.5 border-t hidden lg:block" style="border-color:var(--sidebar-divider)"></div>

    <div class="space-y-0.5">
        <a href="{{ route('customers.index') }}"
           class="sidebar-item group relative flex items-center gap-3 px-3 py-2.5 text-sm
                  {{ request()->routeIs('customers.*') ? 'active active-blue' : '' }}"
           @click="sidebarMobileOpen = false">
            <i data-lucide="users" class="w-4 h-4 flex-shrink-0 nav-icon-blue"></i>
            <span x-show="sidebarOpen || sidebarMobileOpen" class="whitespace-nowrap">Customer</span>
            <span x-show="!sidebarOpen && !sidebarMobileOpen" class="sidebar-tooltip">Customer</span>
        </a>

        <a href="{{ route('products.index') }}"
           class="sidebar-item group relative flex items-center gap-3 px-3 py-2.5 text-sm
                  {{ request()->routeIs('products.*') ? 'active active-ameth' : '' }}"
           @click="sidebarMobileOpen = false">
            <i data-lucide="package" class="w-4 h-4 flex-shrink-0 nav-icon-amethyst"></i>
            <span x-show="sidebarOpen || sidebarMobileOpen" class="whitespace-nowrap">Produk Jas</span>
            <span x-show="!sidebarOpen && !sidebarMobileOpen" class="sidebar-tooltip">Produk Jas</span>
        </a>

        @if(Route::has('broadcasts.index'))
        <a href="{{ route('broadcasts.index') }}"
           class="sidebar-item group relative flex items-center gap-3 px-3 py-2.5 text-sm
                  {{ request()->routeIs('broadcasts.*') ? 'active active-green' : '' }}"
           @click="sidebarMobileOpen = false">
            <i data-lucide="radio" class="w-4 h-4 flex-shrink-0 nav-icon-green"></i>
            <span x-show="sidebarOpen || sidebarMobileOpen" class="whitespace-nowrap">Broadcast WA</span>
            <span x-show="!sidebarOpen && !sidebarMobileOpen" class="sidebar-tooltip">Broadcast</span>
        </a>
        @endif

        @if(auth()->user()->isSuperAdmin())
        <a href="{{ route('categories.index') }}"
           class="sidebar-item group relative flex items-center gap-3 px-3 py-2.5 text-sm
                  {{ request()->routeIs('categories.*') ? 'active active-blue' : '' }}"
           @click="sidebarMobileOpen = false">
            <i data-lucide="tag" class="w-4 h-4 flex-shrink-0 nav-icon-blue"></i>
            <span x-show="sidebarOpen || sidebarMobileOpen" class="whitespace-nowrap">Kategori</span>
            <span x-show="!sidebarOpen && !sidebarMobileOpen" class="sidebar-tooltip">Kategori</span>
        </a>
        @endif
    </div>
    @endunless

    {{-- ─── DATA untuk SALES ─── --}}
    @if(auth()->user()->isSales())
    <div x-show="sidebarOpen || sidebarMobileOpen" class="px-3 pt-4 pb-1">
        <p class="nav-section-label">Data</p>
    </div>
    <div x-show="!sidebarOpen && !sidebarMobileOpen"
         class="mx-3 my-1.5 border-t hidden lg:block" style="border-color:var(--sidebar-divider)"></div>
    <div class="space-y-0.5">
        <a href="{{ route('customers.index') }}"
           class="sidebar-item group relative flex items-center gap-3 px-3 py-2.5 text-sm
                  {{ request()->routeIs('customers.*') ? 'active active-blue' : '' }}"
           @click="sidebarMobileOpen = false">
            <i data-lucide="users" class="w-4 h-4 flex-shrink-0 nav-icon-blue"></i>
            <span x-show="sidebarOpen || sidebarMobileOpen" class="whitespace-nowrap">Customer</span>
            <span x-show="!sidebarOpen && !sidebarMobileOpen" class="sidebar-tooltip">Customer</span>
        </a>
    </div>
    @endif

    {{-- ─── MANAJEMEN ─── --}}
    @if(auth()->user()->isSuperAdmin())
    <div x-show="sidebarOpen || sidebarMobileOpen" class="px-3 pt-4 pb-1">
        <p class="nav-section-label">Manajemen</p>
    </div>
    <div x-show="!sidebarOpen && !sidebarMobileOpen"
         class="mx-3 my-1.5 border-t hidden lg:block" style="border-color:var(--sidebar-divider)"></div>

    <div class="space-y-0.5">
        <a href="{{ route('branches.index') }}"
           class="sidebar-item group relative flex items-center gap-3 px-3 py-2.5 text-sm
                  {{ request()->routeIs('branches.*') ? 'active' : '' }}"
           @click="sidebarMobileOpen = false">
            <i data-lucide="building-2" class="w-4 h-4 flex-shrink-0 nav-icon-blue"></i>
            <span x-show="sidebarOpen || sidebarMobileOpen" class="whitespace-nowrap">Kelola Cabang</span>
            <span x-show="!sidebarOpen && !sidebarMobileOpen" class="sidebar-tooltip">Kelola Cabang</span>
        </a>

        <a href="{{ route('users.index') }}"
           class="sidebar-item group relative flex items-center gap-3 px-3 py-2.5 text-sm
                  {{ request()->routeIs('users.*') ? 'active active-rose' : '' }}"
           @click="sidebarMobileOpen = false">
            <i data-lucide="user-cog" class="w-4 h-4 flex-shrink-0 nav-icon-rouge"></i>
            <span x-show="sidebarOpen || sidebarMobileOpen" class="whitespace-nowrap">Kelola Pengguna</span>
            <span x-show="!sidebarOpen && !sidebarMobileOpen" class="sidebar-tooltip">Kelola Pengguna</span>
        </a>

        <a href="{{ route('packages.index') }}"
           class="sidebar-item group relative flex items-center gap-3 px-3 py-2.5 text-sm
                  {{ request()->routeIs('packages.*') ? 'active active-ameth' : '' }}"
           @click="sidebarMobileOpen = false">
            <i data-lucide="layers" class="w-4 h-4 flex-shrink-0 nav-icon-amethyst"></i>
            <span x-show="sidebarOpen || sidebarMobileOpen" class="whitespace-nowrap">Paket Sewa</span>
            <span x-show="!sidebarOpen && !sidebarMobileOpen" class="sidebar-tooltip">Paket Sewa</span>
        </a>

        <a href="{{ route('invoice-settings.edit') }}"
           class="sidebar-item group relative flex items-center gap-3 px-3 py-2.5 text-sm
                  {{ request()->routeIs('invoice-settings.*') ? 'active active-green' : '' }}"
           @click="sidebarMobileOpen = false">
            <i data-lucide="file-cog" class="w-4 h-4 flex-shrink-0 nav-icon-green"></i>
            <span x-show="sidebarOpen || sidebarMobileOpen" class="whitespace-nowrap">Custom Invoice</span>
            <span x-show="!sidebarOpen && !sidebarMobileOpen" class="sidebar-tooltip">Custom Invoice</span>
        </a>

        <a href="{{ route('payment-settings.edit') }}"
           class="sidebar-item group relative flex items-center gap-3 px-3 py-2.5 text-sm
                  {{ request()->routeIs('payment-settings.*') ? 'active active-green' : '' }}"
           @click="sidebarMobileOpen = false">
            <i data-lucide="wallet" class="w-4 h-4 flex-shrink-0 nav-icon-green"></i>
            <span x-show="sidebarOpen || sidebarMobileOpen" class="whitespace-nowrap">Metode Pembayaran</span>
            <span x-show="!sidebarOpen && !sidebarMobileOpen" class="sidebar-tooltip">Metode Pembayaran</span>
        </a>

        <a href="{{ route('audit.index') }}"
           class="sidebar-item group relative flex items-center gap-3 px-3 py-2.5 text-sm
                  {{ request()->routeIs('audit.*') ? 'active active-ameth' : '' }}"
           @click="sidebarMobileOpen = false">
            <i data-lucide="shield-check" class="w-4 h-4 flex-shrink-0 nav-icon-amethyst"></i>
            <span x-show="sidebarOpen || sidebarMobileOpen" class="whitespace-nowrap">Audit Log</span>
            <span x-show="!sidebarOpen && !sidebarMobileOpen" class="sidebar-tooltip">Audit Log</span>
        </a>
    </div>
    @endif

    {{-- ─── LAPORAN ─── --}}
    @unless(auth()->user()->isSales())
    <div x-show="sidebarOpen || sidebarMobileOpen" class="px-3 pt-4 pb-1">
        <p class="nav-section-label">Laporan</p>
    </div>
    <div x-show="!sidebarOpen && !sidebarMobileOpen"
         class="mx-3 my-1.5 border-t hidden lg:block" style="border-color:var(--sidebar-divider)"></div>

    <div class="space-y-0.5">
        <a href="{{ route('reports.revenue') }}"
           class="sidebar-item group relative flex items-center gap-3 px-3 py-2.5 text-sm
                  {{ request()->routeIs('reports.revenue') ? 'active active-green' : '' }}"
           @click="sidebarMobileOpen = false">
            <i data-lucide="trending-up" class="w-4 h-4 flex-shrink-0 nav-icon-green"></i>
            <span x-show="sidebarOpen || sidebarMobileOpen" class="whitespace-nowrap">Laporan Pendapatan</span>
            <span x-show="!sidebarOpen && !sidebarMobileOpen" class="sidebar-tooltip">Pendapatan</span>
        </a>

        <a href="{{ route('reports.transactions') }}"
           class="sidebar-item group relative flex items-center gap-3 px-3 py-2.5 text-sm
                  {{ request()->routeIs('reports.transactions') ? 'active active-blue' : '' }}"
           @click="sidebarMobileOpen = false">
            <i data-lucide="receipt" class="w-4 h-4 flex-shrink-0 nav-icon-blue"></i>
            <span x-show="sidebarOpen || sidebarMobileOpen" class="whitespace-nowrap">Laporan Transaksi</span>
            <span x-show="!sidebarOpen && !sidebarMobileOpen" class="sidebar-tooltip">Transaksi</span>
        </a>

        <a href="{{ route('reports.returns') }}"
           class="sidebar-item group relative flex items-center gap-3 px-3 py-2.5 text-sm
                  {{ request()->routeIs('reports.returns') ? 'active active-teal' : '' }}"
           @click="sidebarMobileOpen = false">
            <i data-lucide="package-check" class="w-4 h-4 flex-shrink-0 nav-icon-teal"></i>
            <span x-show="sidebarOpen || sidebarMobileOpen" class="whitespace-nowrap">Laporan Pengembalian</span>
            <span x-show="!sidebarOpen && !sidebarMobileOpen" class="sidebar-tooltip">Pengembalian</span>
        </a>

        @if(auth()->user()->isSuperAdmin())
        <a href="{{ route('reports.stock') }}"
           class="sidebar-item group relative flex items-center gap-3 px-3 py-2.5 text-sm
                  {{ request()->routeIs('reports.stock') ? 'active active-ameth' : '' }}"
           @click="sidebarMobileOpen = false">
            <i data-lucide="shirt" class="w-4 h-4 flex-shrink-0 nav-icon-amethyst"></i>
            <span x-show="sidebarOpen || sidebarMobileOpen" class="whitespace-nowrap">Laporan Stok</span>
            <span x-show="!sidebarOpen && !sidebarMobileOpen" class="sidebar-tooltip">Stok</span>
        </a>

        <a href="{{ route('reports.outstanding') }}"
           class="sidebar-item group relative flex items-center gap-3 px-3 py-2.5 text-sm
                  {{ request()->routeIs('reports.outstanding') ? 'active active-rouge' : '' }}"
           @click="sidebarMobileOpen = false">
            <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0 nav-icon-rouge"></i>
            <span x-show="sidebarOpen || sidebarMobileOpen" class="whitespace-nowrap">Piutang</span>
            <span x-show="!sidebarOpen && !sidebarMobileOpen" class="sidebar-tooltip">Piutang</span>
        </a>
        @endif
    </div>
    @endunless

</nav>

{{-- ── USER FOOTER ── --}}
<div class="border-t flex-shrink-0" style="border-color:var(--sidebar-divider)">

    {{-- Expanded --}}
    <div x-show="sidebarOpen || sidebarMobileOpen" class="p-3">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0
                        font-bold text-xs text-white"
                 style="background: var(--sidebar-logo-grad);">
                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold truncate" style="color:var(--sidebar-text)">{{ auth()->user()->name }}</p>
                <p class="text-[10px] truncate" style="color:var(--sidebar-text-muted)">
                    {{ auth()->user()->role }}
                </p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="p-1.5 rounded-lg hover:bg-[var(--sb-danger-surf)] transition" title="Logout">
                    <i data-lucide="log-out" class="w-3.5 h-3.5" style="color:var(--sidebar-text-muted)"></i>
                </button>
            </form>
        </div>
    </div>

    {{-- Collapsed --}}
    <div x-show="!sidebarOpen && !sidebarMobileOpen" class="p-2 hidden lg:block">
        <button @click="sidebarOpen = true"
                class="w-full flex justify-center p-2 rounded-lg hover:bg-[var(--sidebar-hover-bg)] transition"
                title="Buka Sidebar">
            <i data-lucide="panel-left-open" class="w-4 h-4" style="color:var(--sidebar-text-muted)"></i>
        </button>
    </div>
</div>