@extends('layouts.app')
@section('title', 'Laporan Stok Jas')
@section('page-title', 'Laporan Stok Jas')
@section('subtitle', 'Kondisi stok dan ketersediaan jas')

@section('content')
<div class="space-y-6">

    {{-- ── SUMMARY CARDS ───────────────────────────────────────── --}}
    {{-- controller: $totalProducts, $totalStock, $activeRentals, $outOfStock, $lowStock --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="card p-5 flex items-center gap-3">
            <div class="w-11 h-11 rounded-2xl flex items-center justify-center" style="background:var(--secondary)">
                <i data-lucide="shirt" class="w-5 h-5" style="color:var(--primary)"></i>
            </div>
            <div>
                <p class="text-xs" style="color:var(--text-soft)">Total Produk</p>
                <p class="text-2xl font-bold font-playfair" style="color:var(--text-dark)">{{ $totalProducts }}</p>
            </div>
        </div>
        <div class="card p-5 flex items-center gap-3">
            <div class="w-11 h-11 rounded-2xl flex items-center justify-center" style="background:#10B98115">
                <i data-lucide="package" class="w-5 h-5" style="color:#10B981"></i>
            </div>
            <div>
                <p class="text-xs" style="color:var(--text-soft)">Total Stok</p>
                <p class="text-2xl font-bold font-playfair" style="color:var(--text-dark)">{{ $totalStock }}</p>
            </div>
        </div>
        <div class="card p-5 flex items-center gap-3">
            <div class="w-11 h-11 rounded-2xl flex items-center justify-center" style="background:#3B82F615">
                <i data-lucide="check-circle" class="w-5 h-5" style="color:#3B82F6"></i>
            </div>
            <div>
                <p class="text-xs" style="color:var(--text-soft)">Stok Tersedia</p>
                <p class="text-2xl font-bold font-playfair" style="color:var(--text-dark)">{{ $totalAvail }}</p>
            </div>
        </div>
        <div class="card p-5 flex items-center gap-3">
            <div class="w-11 h-11 rounded-2xl flex items-center justify-center" style="background:#F59E0B15">
                <i data-lucide="arrow-up-right" class="w-5 h-5" style="color:#F59E0B"></i>
            </div>
            <div>
                <p class="text-xs" style="color:var(--text-soft)">Sedang Disewa</p>
                {{-- controller: $activeRentals --}}
                <p class="text-2xl font-bold font-playfair" style="color:var(--text-dark)">{{ $activeRentals }}</p>
            </div>
        </div>
        <div class="card p-5 flex items-center gap-3">
            <div class="w-11 h-11 rounded-2xl flex items-center justify-center" style="background:#EF444415">
                <i data-lucide="alert-triangle" class="w-5 h-5" style="color:#EF4444"></i>
            </div>
            <div>
                <p class="text-xs" style="color:var(--text-soft)">Stok Habis</p>
                {{-- controller: $outOfStock --}}
                <p class="text-2xl font-bold font-playfair" style="color:var(--text-dark)">{{ $outOfStock }}</p>
            </div>
        </div>
    </div>

    {{-- Low Stock Alert --}}
    @if($lowStock > 0)
    <div class="card p-4 flex items-center gap-3 border-l-4" style="border-left-color:#F59E0B">
        <i data-lucide="alert-triangle" class="w-5 h-5" style="color:#F59E0B"></i>
        <p class="text-sm" style="color:var(--text-dark)">
            {{-- controller: $lowStock (stock_available <= 2) --}}
            <span class="font-semibold">{{ $lowStock }}</span> produk hampir habis stoknya (sisa ≤ 2 unit)
        </p>
    </div>
    @endif

    {{-- ── FILTER ─────────────────────────────────────────────── --}}
    <div class="card p-5">
        <form method="GET" action="{{ route('reports.stock') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium mb-1" style="color:var(--text-soft)">Cari Produk</label>
                {{-- controller: search by name / code --}}
                <input type="text" name="search" value="{{ $search }}" placeholder="Nama / kode..." class="form-input">
            </div>
            <div>
                <label class="block text-xs font-medium mb-1" style="color:var(--text-soft)">Kategori</label>
                {{-- controller: filter param 'category', query by category_id --}}
                <select name="category" class="form-input">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $k)
                        <option value="{{ $k->id }}" {{ $category == $k->id ? 'selected' : '' }}>{{ $k->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-primary">
                <i data-lucide="search" class="w-4 h-4"></i> Filter
            </button>
            <a href="{{ route('reports.stock') }}" class="btn-secondary">Reset</a>
        </form>
    </div>

    {{-- ── TABEL STOK ──────────────────────────────────────────── --}}
    <div class="card overflow-hidden">
        <div class="p-5 border-b" style="border-color:var(--border)">
            <h2 class="font-semibold text-sm" style="color:var(--text-dark)">Daftar Stok Produk</h2>
        </div>
        <div class="overflow-x-auto">
        <table class="w-full elegant-table">
            <thead>
                <tr>
                    <th class="text-left">#</th>
                    <th class="text-left">Nama Produk</th>
                    <th class="text-left">Kode</th>
                    <th class="text-left">Kategori</th>
                    <th class="text-center">Stok Total</th>
                    <th class="text-center">Tersedia</th>
                    <th class="text-center">Kondisi</th>
                </tr>
            </thead>
            <tbody>
                {{-- controller: $products (paginated), relasi product->category --}}
                @forelse($products as $i => $product)
                <tr>
                    <td class="text-xs" style="color:var(--text-soft)">{{ $products->firstItem() + $i }}</td>
                    <td>
                        <p class="font-medium text-sm" style="color:var(--text-dark)">{{ $product->name }}</p>
                    </td>
                    <td class="font-mono text-xs" style="color:var(--text-soft)">{{ $product->code ?? '-' }}</td>
                    {{-- controller: with('category'), relasi category --}}
                    <td class="text-sm" style="color:var(--text-soft)">{{ $product->category?->name ?? '-' }}</td>
                    {{-- controller: stock_total --}}
                    <td class="text-center font-semibold text-sm" style="color:var(--text-dark)">{{ $product->stock_total }}</td>
                    {{-- controller: stock_available --}}
                    <td class="text-center">
                        <span class="font-bold text-lg font-playfair {{ $product->stock_available == 0 ? 'text-red-500' : ($product->stock_available <= 2 ? 'text-yellow-500' : '') }}"
                              style="{{ $product->stock_available > 2 ? 'color:var(--text-dark)' : '' }}">
                            {{ $product->stock_available }}
                        </span>
                    </td>
                    <td class="text-center">
                        @if($product->stock_available == 0)
                            <span class="badge badge-red">Habis</span>
                        @elseif($product->stock_available <= 2)
                            <span class="badge badge-gold">Hampir Habis</span>
                        @else
                            <span class="badge badge-green">Tersedia</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-12 text-center">
                        <i data-lucide="package-x" class="w-8 h-8 mx-auto mb-2" style="color:var(--border)"></i>
                        <p class="text-sm" style="color:var(--text-soft)">Tidak ada data produk</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        @if($products->hasPages())
        <div class="px-6 py-4 border-t" style="border-color:var(--border)">
            {{ $products->links('components.pagination') }}
        </div>
        @endif
    </div>
</div>
@push('scripts')
<script>lucide.createIcons();</script>
@endpush
@endsection