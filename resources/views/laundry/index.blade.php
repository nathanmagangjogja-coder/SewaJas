@extends('layouts.app')

@section('title', 'Manajemen Laundry')

@section('content')
<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="font-playfair text-2xl font-bold" style="color:var(--text-dark)">
                Manajemen Laundry
            </h1>
            <p class="text-sm mt-0.5" style="color:var(--text-soft)">
                Kelola status laundry semua jas
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('laundry.menunggu') }}"
               class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold border transition-colors"
               style="color:var(--text-soft); border-color:var(--border)">
                <i data-lucide="clock" class="w-4 h-4"></i>
                <span>Menunggu</span>
            </a>
            <a href="{{ route('laundry.dalam') }}"
               class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold border transition-colors"
               style="color:var(--text-soft); border-color:var(--border)">
                <i data-lucide="washing-machine" class="w-4 h-4"></i>
                <span>Dalam Laundry</span>
            </a>
            <a href="{{ route('laundry.siap') }}"
               class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold border transition-colors"
               style="color:var(--text-soft); border-color:var(--border)">
                <i data-lucide="check-circle" class="w-4 h-4"></i>
                <span>Siap Disewakan</span>
            </a>
            <a href="{{ route('laundry.riwayat') }}"
               class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold border transition-colors"
               style="color:var(--text-soft); border-color:var(--border)">
                <i data-lucide="history" class="w-4 h-4"></i>
                <span>Riwayat</span>
            </a>
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
    @if(session('error'))
    <div class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium"
         style="background:#FFF1F0; color:#C0392B; border:1px solid #FECACA">
        <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
        {{ session('error') }}
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

    {{-- Tabs --}}
    <div class="overflow-x-auto -mx-4 px-4 sm:mx-0 sm:px-0">
        <div class="flex items-center gap-1 p-1 rounded-xl w-max" style="background: var(--secondary)">
            @php
                $statuses = [
                    'semua' => 'Semua',
                    'menunggu_laundry' => 'Menunggu',
                    'dalam_laundry' => 'Dalam Laundry',
                    'siap_disewakan' => 'Siap Disewakan',
                ];
            @endphp
            @foreach($statuses as $val => $label)
            <a href="{{ route('laundry.index', ['status' => $val === 'semua' ? null : $val]) }}"
               class="px-4 py-2 rounded-xl text-sm font-medium transition-all whitespace-nowrap {{ $status === $val ? 'shadow-sm' : '' }}"
               style="{{ $status === $val ? 'background: #FFFFFF; color: var(--text-dark);' : 'color: var(--text-soft);' }}">
                {{ $label }}
            </a>
            @endforeach
        </div>
    </div>

    {{-- Tabel / Card List --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b flex items-center justify-between" style="border-color:var(--border)">
            <div class="flex items-center gap-2">
                <i data-lucide="list" class="w-4 h-4" style="color:#D6B98C"></i>
                <h3 class="font-playfair font-semibold" style="color:var(--text-dark)">Semua Laundry</h3>
                <span class="badge text-[10px]" style="background:#D6B98C;color:#444">{{ $laundries->total() }}</span>
            </div>
        </div>

        @if($laundries->isEmpty())
        <div class="py-16 text-center">
            <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-3" style="color:#D6B98C; opacity:.5"></i>
            <p class="text-sm" style="color:var(--text-soft)">Tidak ada data laundry</p>
        </div>

        @else

        {{-- Desktop Table --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full elegant-table">
                <thead>
                    <tr>
                        <th class="text-left w-10">#</th>
                        <th class="text-left">Produk</th>
                        <th class="text-left">Customer</th>
                        <th class="text-left">Transaksi</th>
                        <th class="text-left">Dikembalikan</th>
                        <th class="text-left">Mulai Laundry</th>
                        <th class="text-left">Selesai</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($laundries as $laundry)
                    <tr>
                        <td class="text-sm" style="color:var(--text-soft)">{{ $laundries->firstItem() + $loop->index }}</td>
                        <td>
                            <p class="font-medium text-sm" style="color:var(--text-dark)">
                                {{ $laundry->produk->name ?? '-' }}
                            </p>
                            <p class="text-[11px]" style="color:var(--text-soft)">
                                {{ $laundry->produk->size ?? '' }}
                                {{ $laundry->produk->color ?? '' }}
                            </p>
                        </td>
                        <td>
                            <p class="text-sm font-medium" style="color:var(--text-dark)">
                                {{ $laundry->transaksi->customer->name ?? '-' }}
                            </p>
                            <p class="text-xs" style="color:var(--text-soft)">
                                {{ $laundry->transaksi->customer->phone ?? '' }}
                            </p>
                        </td>
                        <td>
                            <a href="{{ route('rentals.show', $laundry->transaksi_id) }}"
                               class="font-mono text-sm font-semibold hover:underline" style="color: #D6B98C">
                                {{ $laundry->transaksi->invoice_number ?? '#' . $laundry->transaksi_id }}
                            </a>
                        </td>
                        <td class="text-sm whitespace-nowrap" style="color:var(--text-dark)">
                            {{ $laundry->dikembalikan_at ? $laundry->dikembalikan_at->format('d/m/Y') : '-' }}
                        </td>
                        <td class="text-sm whitespace-nowrap" style="color:var(--text-dark)">
                            {{ $laundry->mulai_laundry_at ? $laundry->mulai_laundry_at->format('d/m/Y') : '-' }}
                        </td>
                        <td class="text-sm whitespace-nowrap" style="color:var(--text-dark)">
                            {{ $laundry->selesai_laundry_at ? $laundry->selesai_laundry_at->format('d/m/Y') : '-' }}
                        </td>
                        <td class="text-center">
                            @php
                                $badgeClass = match($laundry->status) {
                                    'menunggu_laundry' => 'badge-yellow',
                                    'dalam_laundry' => 'badge-blue',
                                    'siap_disewakan' => 'badge-green',
                                    default => 'badge-gray',
                                };
                                $badgeLabel = match($laundry->status) {
                                    'menunggu_laundry' => 'Menunggu',
                                    'dalam_laundry' => 'Dalam Laundry',
                                    'siap_disewakan' => 'Siap Disewakan',
                                    default => $laundry->status,
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                        </td>
                        <td>
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('laundry.show', $laundry) }}"
                                   class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors" title="Detail"
                                   style="color:var(--text-soft)">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile Card List --}}
        <div class="md:hidden space-y-3">
            @foreach($laundries as $laundry)
            @php
                $badgeClass = match($laundry->status) {
                    'menunggu_laundry' => 'badge-yellow',
                    'dalam_laundry' => 'badge-blue',
                    'siap_disewakan' => 'badge-green',
                    default => 'badge-gray',
                };
                $badgeLabel = match($laundry->status) {
                    'menunggu_laundry' => 'Menunggu',
                    'dalam_laundry' => 'Dalam Laundry',
                    'siap_disewakan' => 'Siap Disewakan',
                    default => $laundry->status,
                };
            @endphp
            <div class="card p-4">
                <div class="flex items-start justify-between gap-2 mb-3">
                    <div class="min-w-0">
                        <p class="font-semibold text-sm truncate" style="color:var(--text-dark)">
                            {{ $laundry->produk->name ?? '-' }}
                        </p>
                        <p class="text-xs mt-0.5" style="color:var(--text-soft)">
                            {{ $laundry->produk->size ?? '' }}
                            {{ $laundry->produk->color ?? '' }}
                        </p>
                    </div>
                    <span class="badge {{ $badgeClass }} flex-shrink-0">{{ $badgeLabel }}</span>
                </div>

                <div class="flex items-center justify-between gap-2 mb-3 pb-3" style="border-bottom: 1px solid var(--border)">
                    <div>
                        <p class="text-sm font-medium" style="color:var(--text-dark)">
                            {{ $laundry->transaksi->customer->name ?? '-' }}
                        </p>
                        <p class="text-xs" style="color:var(--text-soft)">
                            {{ $laundry->transaksi->customer->phone ?? '' }}
                        </p>
                    </div>
                    <a href="{{ route('rentals.show', $laundry->transaksi_id) }}"
                       class="font-mono text-xs font-semibold hover:underline flex-shrink-0" style="color: #D6B98C">
                        {{ $laundry->transaksi->invoice_number ?? '#' . $laundry->transaksi_id }}
                    </a>
                </div>

                {{-- Dates --}}
                <div class="grid grid-cols-3 gap-2 mb-3 text-center">
                    <div>
                        <p class="text-[10px]" style="color:var(--text-soft)">Dikembalikan</p>
                        <p class="text-xs font-medium mt-0.5" style="color:var(--text-dark)">
                            {{ $laundry->dikembalikan_at ? $laundry->dikembalikan_at->format('d M') : '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[10px]" style="color:var(--text-soft)">Mulai Laundry</p>
                        <p class="text-xs font-medium mt-0.5" style="color:var(--text-dark)">
                            {{ $laundry->mulai_laundry_at ? $laundry->mulai_laundry_at->format('d M') : '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[10px]" style="color:var(--text-soft)">Selesai</p>
                        <p class="text-xs font-medium mt-0.5" style="color:var(--text-dark)">
                            {{ $laundry->selesai_laundry_at ? $laundry->selesai_laundry_at->format('d M') : '-' }}
                        </p>
                    </div>
                </div>

                <a href="{{ route('laundry.show', $laundry) }}" class="btn-secondary flex-1 justify-center text-xs py-1.5">
                    <i data-lucide="eye" class="w-3.5 h-3.5"></i> Detail
                </a>
            </div>
            @endforeach

            @if($laundries->hasPages())
            <div class="pt-2">
                {{ $laundries->links('components.pagination') }}
            </div>
            @endif
        </div>

        @if($laundries->hasPages())
        <div class="px-5 py-4 border-t flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
             style="border-color:var(--border)">
            <p class="text-xs" style="color:var(--text-soft)">
                Menampilkan {{ $laundries->firstItem() }}–{{ $laundries->lastItem() }}
                dari {{ $laundries->total() }} data
            </p>
            {{ $laundries->links() }}
        </div>
        @endif
        @endif
    </div>
</div>
@endsection
