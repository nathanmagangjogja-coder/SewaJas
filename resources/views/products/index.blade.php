@extends('Layouts.app')

@section('title', 'Produk Jas')

@section('content')
<div class="space-y-6">

    {{-- ── HEADER ──────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-playfair text-2xl font-bold" style="color: var(--text-dark)">Produk Jas</h1>
            <p class="text-sm mt-0.5" style="color: var(--text-soft)">Kelola stok dan produk rental</p>
        </div>
        <a href="{{ route('products.create') }}" class="btn-primary">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Tambah Produk
        </a>
    </div>

    {{-- ── STATS ────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="stat-card stat-card-blue">
            <p class="text-xs font-medium uppercase tracking-wide" style="color:var(--text-soft)">Total Produk</p>
            <p class="text-2xl font-bold mt-1" style="color:var(--text-dark)">{{ $products->total() }}</p>
        </div>
        <div class="stat-card stat-card-green">
            <p class="text-xs font-medium uppercase tracking-wide" style="color:var(--text-soft)">Tersedia</p>
            <p class="text-2xl font-bold mt-1" style="color:var(--color-emerald)">{{ \App\Models\Product::where('status','available')->count() }}</p>
        </div>
        <div class="stat-card stat-card-purple">
            <p class="text-xs font-medium uppercase tracking-wide" style="color:var(--text-soft)">Sedang Disewa</p>
            <p class="text-2xl font-bold mt-1" style="color:var(--color-violet)">{{ \App\Models\Rental::whereIn('rental_status', ['active', 'overdue'])->count() }}</p>
        </div>
        <div class="stat-card stat-card-pink">
            <p class="text-xs font-medium uppercase tracking-wide" style="color:var(--text-soft)">Maintenance</p>
            <p class="text-2xl font-bold mt-1" style="color:var(--color-rose)">{{ \App\Models\Product::where('status','maintenance')->count() }}</p>
        </div>
    </div>

    {{-- ── FILTER & SEARCH ──────────────────────────────────── --}}
    <div class="card p-4">
        <form method="GET" action="{{ route('products.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" style="color:var(--text-soft)"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari nama, kode produk..."
                    class="form-input pl-9">
            </div>
            <select name="category" class="form-input sm:w-44">
                <option value="">Semua Kategori</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
                @endforeach
            </select>
            <select name="status" class="form-input sm:w-40">
                <option value="">Semua Status</option>
                <option value="available"   {{ request('status') === 'available'   ? 'selected' : '' }}>Tersedia</option>
                <option value="rented"      {{ request('status') === 'rented'      ? 'selected' : '' }}>Disewa</option>
                <option value="maintenance" {{ request('status') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
            </select>
            <select name="size" class="form-input sm:w-32">
                <option value="">Semua Ukuran</option>
                @foreach(['XS','S','M','L','XL','XXL'] as $sz)
                <option value="{{ $sz }}" {{ request('size') === $sz ? 'selected' : '' }}>{{ $sz }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn-primary whitespace-nowrap">
                <i data-lucide="filter" class="w-4 h-4"></i> Filter
            </button>
            @if(request()->hasAny(['search','category','status','size']))
            <a href="{{ route('products.index') }}" class="btn-secondary whitespace-nowrap">
                <i data-lucide="x" class="w-4 h-4"></i> Reset
            </a>
            @endif
        </form>
    </div>

    {{-- ── TOGGLE VIEW + COUNT ───────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <p class="text-sm" style="color:var(--text-soft)">
            {{ $products->total() }} produk ditemukan
        </p>
        <div class="flex items-center gap-1 p-1 rounded-xl" style="background:var(--secondary)">
            <button id="btn-card"
                onclick="setView('card')"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-all"
                style="background:var(--card-bg); color:var(--text-dark); box-shadow:0 1px 3px rgba(0,0,0,.08)">
                <i data-lucide="layout-grid" class="w-4 h-4"></i> Kartu
            </button>
            <button id="btn-table"
                onclick="setView('table')"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-all"
                style="color:var(--text-soft)">
                <i data-lucide="table" class="w-4 h-4"></i> Tabel
            </button>
        </div>
    </div>

    @if($products->isEmpty())
    {{-- ── EMPTY STATE ─────────────────────────────────────── --}}
    <div class="card p-12 text-center">
        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4" style="background:var(--secondary)">
            <i data-lucide="shirt" class="w-8 h-8" style="color:var(--primary)"></i>
        </div>
        <p class="font-semibold text-lg" style="color:var(--text-dark)">Belum ada produk</p>
        <p class="text-sm mt-1 mb-4" style="color:var(--text-soft)">Tambahkan produk jas pertama Anda</p>
        <a href="{{ route('products.create') }}" class="btn-primary">
            <i data-lucide="plus" class="w-4 h-4"></i> Tambah Produk
        </a>
    </div>
    @else

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- ── VIEW: CARD GRID ─────────────────────────────────── --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div id="view-card">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            @foreach($products as $product)
            <div class="card overflow-hidden group flex flex-col">

                {{-- Gambar Produk --}}
                <div class="relative overflow-hidden" style="aspect-ratio: 3/4; background:var(--secondary)">
                    @if($product->photo)
                        <img src="{{ $product->photo_url }}"
                             alt="{{ $product->name }}"
                             class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                    @else
                        <div class="w-full h-full flex flex-col items-center justify-center gap-2">
                            <i data-lucide="shirt" class="w-12 h-12" style="color:var(--primary); opacity:.35"></i>
                            <span class="text-xs" style="color:var(--text-soft)">Tidak ada foto</span>
                        </div>
                    @endif

                    {{-- Badge Status di atas gambar --}}
                    <div class="absolute top-2 left-2">
                        @if($product->status === 'available')
                            <span class="badge badge-green text-xs">Tersedia</span>
                        @elseif($product->status === 'rented')
                            <span class="badge badge-blue text-xs">Disewa</span>
                        @elseif($product->status === 'maintenance')
                            <span class="badge badge-red text-xs">Maintenance</span>
                        @else
                            <span class="badge badge-gray text-xs">{{ $product->status }}</span>
                        @endif
                    </div>

                    {{-- Overlay Aksi hover --}}
                    <div class="absolute inset-0 flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                         style="background:rgba(0,0,0,.35)">
                        <a href="{{ route('products.show', $product) }}"
                           class="w-9 h-9 rounded-full bg-white flex items-center justify-center hover:scale-110 transition-transform"
                           title="Detail">
                            <i data-lucide="eye" class="w-4 h-4" style="color:var(--text-dark)"></i>
                        </a>
                        <a href="{{ route('products.edit', $product) }}"
                           class="w-9 h-9 rounded-full bg-white flex items-center justify-center hover:scale-110 transition-transform"
                           title="Edit">
                            <i data-lucide="pencil" class="w-4 h-4" style="color:var(--primary)"></i>
                        </a>
                        <form method="POST" action="{{ route('products.destroy', $product) }}"
                              onsubmit="return confirm('Hapus produk ini?')" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="w-9 h-9 rounded-full bg-white flex items-center justify-center hover:scale-110 transition-transform"
                                    title="Hapus">
                                <i data-lucide="trash-2" class="w-4 h-4 text-red-400"></i>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Info Produk --}}
                <div class="p-3 flex flex-col gap-1 flex-1">
                    <p class="font-semibold text-sm leading-tight" style="color:var(--text-dark)">{{ $product->name }}</p>
                    <p class="text-xs" style="color:var(--text-soft)">{{ $product->code }}</p>

                    {{-- Tags: Ukuran, Warna, Kategori --}}
                    <div class="flex flex-wrap gap-1 mt-1">
                        @if($product->size)
                        <span class="badge badge-gray text-xs">{{ $product->size }}</span>
                        @endif
                        @if($product->color)
                        <span class="badge badge-gray text-xs">{{ $product->color }}</span>
                        @endif
                        @if($product->category)
                        <span class="badge badge-gray text-xs">{{ $product->category->name }}</span>
                        @endif
                    </div>

                    {{-- Harga & Stok --}}
                    <div class="flex items-end justify-between mt-auto pt-2 border-t" style="border-color:var(--border)">
                        <div>
                            <p class="font-bold text-sm" style="color:var(--text-dark)">
                                Rp {{ number_format($product->rental_price, 0, ',', '.') }}
                            </p>
                            <p class="text-xs" style="color:var(--text-soft)">/hari</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-medium" style="color:var(--text-dark)">
                                {{ $product->stock_available }}/{{ $product->stock_total }}
                            </p>
                            <p class="text-xs" style="color:var(--text-soft)">stok</p>
                        </div>
                    </div>
                </div>

            </div>
            @endforeach
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- ── VIEW: TABLE ─────────────────────────────────────── --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div id="view-table" class="card overflow-hidden hidden">
        <div class="overflow-x-auto">
            <table class="elegant-table w-full">
                <thead>
                    <tr>
                        <th class="text-left">Produk</th>
                        <th class="text-left">Kategori</th>
                        <th class="text-center">Ukuran</th>
                        <th class="text-center">Warna</th>
                        <th class="text-center">Stok</th>
                        <th class="text-right">Harga Sewa</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Kondisi</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                    <tr>
                        {{-- Produk + thumbnail kecil --}}
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex-shrink-0 overflow-hidden"
                                     style="background:var(--secondary)">
                                    @if($product->photo)
                                        <img src="{{ $product->photo_url }}"
                                             alt="{{ $product->name }}"
                                             class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <i data-lucide="shirt" class="w-5 h-5" style="color:var(--primary)"></i>
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <p class="font-semibold text-sm" style="color:var(--text-dark)">{{ $product->name }}</p>
                                    <p class="text-xs" style="color:var(--text-soft)">{{ $product->code }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="text-sm" style="color:var(--text-soft)">{{ $product->category?->name ?? '-' }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-gray">{{ $product->size ?? '-' }}</span>
                        </td>
                        <td class="text-center">
                            <span class="text-sm" style="color:var(--text-soft)">{{ $product->color ?? '-' }}</span>
                        </td>
                        <td class="text-center">
                            <div class="text-sm font-medium" style="color:var(--text-dark)">
                                {{ $product->stock_available }}/{{ $product->stock_total }}
                            </div>
                            <div class="text-xs" style="color:var(--text-soft)">tersedia</div>
                        </td>
                        <td class="text-right">
                            <span class="font-semibold text-sm" style="color:var(--text-dark)">
                                Rp {{ number_format($product->rental_price, 0, ',', '.') }}
                            </span>
                            <div class="text-xs" style="color:var(--text-soft)">/hari</div>
                        </td>
                        <td class="text-center">
                            @if($product->status === 'available')
                                <span class="badge badge-green">Tersedia</span>
                            @elseif($product->status === 'rented')
                                <span class="badge badge-blue">Disewa</span>
                            @elseif($product->status === 'maintenance')
                                <span class="badge badge-red">Maintenance</span>
                            @else
                                <span class="badge badge-gray">{{ $product->status }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($product->condition === 'excellent')
                                <span class="badge badge-gold">Excellent</span>
                            @elseif($product->condition === 'good')
                                <span class="badge badge-green">Good</span>
                            @elseif($product->condition === 'fair')
                                <span class="badge badge-yellow">Fair</span>
                            @else
                                <span class="badge badge-red">Poor</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('products.show', $product) }}"
                                   class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors" title="Detail">
                                    <i data-lucide="eye" class="w-4 h-4" style="color:var(--text-soft)"></i>
                                </a>
                                <a href="{{ route('products.edit', $product) }}"
                                   class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors" title="Edit">
                                    <i data-lucide="pencil" class="w-4 h-4" style="color:var(--primary)"></i>
                                </a>
                                <form method="POST" action="{{ route('products.destroy', $product) }}"
                                      onsubmit="return confirm('Hapus produk ini?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="p-1.5 rounded-lg hover:bg-red-50 transition-colors" title="Hapus">
                                        <i data-lucide="trash-2" class="w-4 h-4 text-red-400"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination (tabel) --}}
        @if($products->hasPages())
        <div class="px-6 py-4 border-t" style="border-color:var(--border)">
            {{ $products->links() }}
        </div>
        @endif
    </div>

    {{-- Pagination (card) --}}
    <div id="pagination-card">
        @if($products->hasPages())
        <div class="mt-2">
            {{ $products->links() }}
        </div>
        @endif
    </div>

    @endif
</div>

@push('scripts')
<script>
    lucide.createIcons();

    // ── View toggle ────────────────────────────────────────────
    const STORAGE_KEY = 'maison_product_view';

    function setView(v) {
        const isCard = v === 'card';

        document.getElementById('view-card').classList.toggle('hidden', !isCard);
        document.getElementById('view-table').classList.toggle('hidden', isCard);
        document.getElementById('pagination-card').classList.toggle('hidden', !isCard);

        // Styling tombol aktif
        const btnCard  = document.getElementById('btn-card');
        const btnTable = document.getElementById('btn-table');

        if (isCard) {
            btnCard.style.cssText  = 'background:var(--card-bg);color:var(--text-dark);box-shadow:0 1px 3px rgba(0,0,0,.08)';
            btnTable.style.cssText = 'background:transparent;color:var(--text-soft)';
        } else {
            btnTable.style.cssText = 'background:var(--card-bg);color:var(--text-dark);box-shadow:0 1px 3px rgba(0,0,0,.08)';
            btnCard.style.cssText  = 'background:transparent;color:var(--text-soft)';
        }

        localStorage.setItem(STORAGE_KEY, v);
    }

    // Restore pilihan terakhir user
    const saved = localStorage.getItem(STORAGE_KEY) || 'card';
    setView(saved);
</script>
@endpush
@endsection