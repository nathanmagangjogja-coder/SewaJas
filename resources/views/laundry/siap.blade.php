@extends('layouts.app')

@section('title', 'Siap Disewakan')

@section('content')
<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="font-playfair text-2xl font-bold" style="color:var(--text-dark)">
                Siap Disewakan
            </h1>
            <p class="text-sm mt-0.5" style="color:var(--text-soft)">
                Jas yang sudah selesai laundry dan siap untuk disewa kembali
            </p>
        </div>
    </div>

    {{-- Alert --}}
    @if(session('success'))
    <div class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium"
         style="background:#F0FDF4; color:#15803D; border:1px solid #BBF7D0">
        <i data-lucide="check-circle-2" class="w-4 h-4 flex-shrink-0"></i>
        {{ session('success') }}
    </div>
    @endif

    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        <a href="{{ route('laundry.menunggu') }}" class="stat-card block hover:scale-105 transition-transform"
           style="border-left: 4px solid #F59E0B;">
            <div class="flex items-start justify-between mb-3">
                <div class="p-2.5 rounded-xl" style="background:#FEF3C720">
                    <i data-lucide="clock" class="w-5 h-5" style="color:#F59E0B"></i>
                </div>
                <span class="badge badge-yellow text-[10px]">Antrian</span>
            </div>
            <p class="text-3xl font-bold font-playfair" style="color:var(--text-dark)">
                {{ $stats['menunggu_laundry'] }}
            </p>
            <p class="text-xs mt-1" style="color:var(--text-soft)">Menunggu Laundry</p>
        </a>

        <a href="{{ route('laundry.dalam') }}" class="stat-card block hover:scale-105 transition-transform"
           style="border-left: 4px solid #3B82F6;">
            <div class="flex items-start justify-between mb-3">
                <div class="p-2.5 rounded-xl" style="background:#EFF6FF">
                    <i data-lucide="loader-2" class="w-5 h-5" style="color:#3B82F6"></i>
                </div>
                <span class="badge text-[10px]" style="background:#DBEAFE;color:#1D4ED8">Proses</span>
            </div>
            <p class="text-3xl font-bold font-playfair" style="color:var(--text-dark)">
                {{ $stats['dalam_laundry'] }}
            </p>
            <p class="text-xs mt-1" style="color:var(--text-soft)">Dalam Laundry</p>
        </a>

        <a href="{{ route('laundry.siap') }}" class="stat-card block hover:scale-105 transition-transform"
           style="border-left: 4px solid #10B981;">
            <div class="flex items-start justify-between mb-3">
                <div class="p-2.5 rounded-xl" style="background:#F0FDF4">
                    <i data-lucide="check-circle-2" class="w-5 h-5" style="color:#10B981"></i>
                </div>
                <span class="badge badge-green text-[10px]">Siap</span>
            </div>
            <p class="text-3xl font-bold font-playfair" style="color:var(--text-dark)">
                {{ $stats['siap_disewakan'] }}
            </p>
            <p class="text-xs mt-1" style="color:var(--text-soft)">Siap Disewakan</p>
        </a>

    </div>

    {{-- Tabel --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b flex items-center gap-2" style="border-color:var(--border)">
            <i data-lucide="check-circle-2" class="w-4 h-4" style="color:#10B981"></i>
            <h3 class="font-playfair font-semibold" style="color:var(--text-dark)">Jas Siap Disewakan</h3>
            <span class="badge badge-green text-[10px]">{{ $laundries->total() }}</span>
        </div>

        @if($laundries->isEmpty())
        <div class="py-16 text-center">
            <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-3" style="color:#D6B98C; opacity:.5"></i>
            <p class="text-sm" style="color:var(--text-soft)">Belum ada jas yang siap disewakan</p>
        </div>

        @else

        {{-- Desktop Table --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full elegant-table">
                <thead>
                    <tr>
                        <th class="text-left">#</th>
                        <th class="text-left">Jas / Produk</th>
                        <th class="text-left">Customer Sebelumnya</th>
                        <th class="text-left">Selesai Laundry</th>
                        <th class="text-left">Total Proses</th>
                        <th class="text-left">Stok Tersedia</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($laundries as $index => $laundry)
                    @php
                        $total = ($laundry->dikembalikan_at && $laundry->selesai_laundry_at)
                            ? (int) $laundry->dikembalikan_at->diffInHours($laundry->selesai_laundry_at)
                            : null;
                    @endphp
                    <tr>
                        <td class="text-sm" style="color:var(--text-soft)">
                            {{ $laundries->firstItem() + $index }}
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                @if($laundry->produk->photo ?? false)
                                <img src="{{ asset('storage/' . $laundry->produk->photo) }}"
                                     class="w-9 h-9 rounded-lg object-cover flex-shrink-0">
                                @else
                                <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0"
                                     style="background:#F5F0EA">
                                    <i data-lucide="shirt" class="w-4 h-4" style="color:#D6B98C"></i>
                                </div>
                                @endif
                                <div>
                                    <p class="text-sm font-semibold" style="color:var(--text-dark)">
                                        {{ $laundry->produk->name ?? '-' }}
                                    </p>
                                    <p class="text-[11px]" style="color:var(--text-soft)">
                                        {{ $laundry->produk->size ?? '' }}
                                        {{ $laundry->produk->color ?? '' }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="text-sm" style="color:var(--text-dark)">
                            {{ $laundry->transaksi->customer->name ?? '-' }}
                        </td>
                        <td>
                            <p class="text-sm" style="color:var(--text-dark)">
                                {{ $laundry->selesai_laundry_at?->format('d/m/Y') ?? '-' }}
                            </p>
                            <p class="text-[11px]" style="color:var(--text-soft)">
                                {{ $laundry->selesai_laundry_at?->format('H:i') ?? '' }}
                            </p>
                        </td>
                        <td>
                            @if($total !== null)
                            <span class="badge text-[10px]" style="background:#F3F4F6;color:#6B7280">
                                {{ $total < 1 ? 'Baru saja' : $total . ' jam' }}
                            </span>
                            @else
                            <span class="text-xs" style="color:var(--text-soft)">-</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-green text-[10px]">
                                {{ $laundry->produk->stock_available ?? 0 }} unit
                            </span>
                        </td>
                        <td>
                            <div class="flex items-center justify-center gap-1.5">
                                <a href="{{ route('laundry.show', $laundry) }}"
                                   class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border transition-colors"
                                   style="color:var(--text-soft); border-color:var(--border)">
                                    <i data-lucide="eye" class="w-3 h-3"></i> Detail
                                </a>
                                <a href="{{ route('rentals.create', ['produk_id' => $laundry->produk_id]) }}"
                                   class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white transition-all hover:opacity-90"
                                   style="background:linear-gradient(135deg,#D6B98C,#C4A478)">
                                    <i data-lucide="plus" class="w-3 h-3"></i> Sewa Baru
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile Card List --}}
        <div class="md:hidden divide-y" style="border-color:var(--border)">
            @foreach($laundries as $laundry)
            @php
                $total = ($laundry->dikembalikan_at && $laundry->selesai_laundry_at)
                    ? (int) $laundry->dikembalikan_at->diffInHours($laundry->selesai_laundry_at)
                    : null;
            @endphp
            <div class="p-4">
                <div class="flex items-start gap-3">
                    @if($laundry->produk->photo ?? false)
                    <img src="{{ asset('storage/' . $laundry->produk->photo) }}"
                         class="w-12 h-12 rounded-xl object-cover flex-shrink-0">
                    @else
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                         style="background:#F5F0EA">
                        <i data-lucide="shirt" class="w-5 h-5" style="color:#D6B98C"></i>
                    </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-semibold text-sm truncate" style="color:var(--text-dark)">
                                {{ $laundry->produk->name ?? '-' }}
                            </p>
                            <span class="badge badge-green text-[10px] flex-shrink-0">
                                {{ $laundry->produk->stock_available ?? 0 }} unit
                            </span>
                        </div>
                        <p class="text-xs mt-0.5" style="color:var(--text-soft)">
                            {{ $laundry->produk->size ?? '' }} {{ $laundry->produk->color ?? '' }}
                        </p>
                        <p class="text-xs mt-1" style="color:var(--text-soft)">
                            Customer: {{ $laundry->transaksi->customer->name ?? '-' }}
                        </p>
                        @if($laundry->selesai_laundry_at)
                        <p class="text-xs mt-0.5" style="color:var(--text-soft)">
                            Selesai: {{ $laundry->selesai_laundry_at->format('d/m/Y H:i') }}
                            @if($total !== null) · {{ $total < 1 ? 'Baru saja' : $total . ' jam' }} @endif
                        </p>
                        @endif
                        <div class="flex items-center gap-2 mt-3">
                            <a href="{{ route('laundry.show', $laundry) }}"
                               class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border"
                               style="color:var(--text-soft); border-color:var(--border)">
                                <i data-lucide="eye" class="w-3 h-3"></i> Detail
                            </a>
                            <a href="{{ route('rentals.create', ['produk_id' => $laundry->produk_id]) }}"
                               class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white"
                               style="background:linear-gradient(135deg,#D6B98C,#C4A478)">
                                <i data-lucide="plus" class="w-3 h-3"></i> Sewa Baru
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="px-5 py-4 border-t flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
             style="border-color:var(--border)">
            <p class="text-xs" style="color:var(--text-soft)">
                Menampilkan {{ $laundries->firstItem() }}–{{ $laundries->lastItem() }}
                dari {{ $laundries->total() }} data
            </p>
            {{ $laundries->links() }}
        </div>

        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    lucide.createIcons();
});
</script>
@endpush