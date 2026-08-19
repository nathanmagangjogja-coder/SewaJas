@extends('Layouts.app')

@section('title', 'Detail Produk — ' . $product->name)

@section('content')
    <div class="space-y-6">

        {{-- ── HEADER ──────────────────────────────────────────── --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <a href="{{ route('products.index') }}"
                        class="text-sm flex items-center gap-1 hover:opacity-70 transition-opacity"
                        style="color:var(--text-soft)">
                        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Semua Produk
                    </a>
                </div>
                <h1 class="font-playfair text-2xl font-bold" style="color: var(--text-dark)">{{ $product->name }}</h1>
                <p class="text-sm mt-0.5" style="color: var(--text-soft)">{{ $product->code }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('products.edit', $product) }}" class="btn-primary">
                    <i data-lucide="pencil" class="w-4 h-4"></i> Edit Produk
                </a>
                <form method="POST" action="{{ route('products.destroy', $product) }}"
                    onsubmit="return confirm('Yakin ingin menghapus produk ini?')" class="inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-secondary text-red-500 hover:bg-red-50">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                        <span class="hidden sm:inline">Hapus</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- ── MAIN CONTENT ──────────────────────────────────────── --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- ── KOLOM KIRI ───────────────────────────────────── --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Hero Card --}}
                <div class="card overflow-hidden">
                    <div class="grid grid-cols-1 sm:grid-cols-2">
                        {{-- Foto --}}
                        <div class="relative bg-gray-50 flex items-center justify-center min-h-56"
                            style="background: var(--secondary)">
                            @if ($product->photo)
                                <img src="{{ $product->photo_url }}" alt="{{ $product->name }}"
                                    class="w-full h-full object-cover">
                            @else
                                <div class="flex flex-col items-center gap-3 p-8 text-center">
                                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center"
                                        style="background: rgba(var(--primary-rgb), 0.1)">
                                        <i data-lucide="shirt" class="w-8 h-8" style="color:var(--primary)"></i>
                                    </div>
                                    <p class="text-xs" style="color:var(--text-soft)">Belum ada foto</p>
                                </div>
                            @endif

                            {{-- Status overlay badge --}}
                            <div class="absolute top-3 left-3">
                                @if ($product->status === 'available')
                                    <span class="badge badge-green">Tersedia</span>
                                @elseif($product->status === 'rented')
                                    <span class="badge badge-blue">Disewa</span>
                                @elseif($product->status === 'maintenance')
                                    <span class="badge badge-red">Maintenance</span>
                                @else
                                    <span class="badge badge-gray">{{ $product->status }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- Quick info --}}
                        <div class="p-6 flex flex-col justify-between">
                            <div class="space-y-4">
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wide mb-0.5"
                                        style="color:var(--text-soft)">Harga Sewa</p>
                                    <p class="text-3xl font-bold font-playfair" style="color:var(--text-dark)">
                                        Rp {{ number_format($product->rental_price, 0, ',', '.') }}
                                    </p>
                                    <p class="text-xs" style="color:var(--text-soft)">per hari</p>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-xl p-3" style="background:var(--secondary)">
                                        <p class="text-xs" style="color:var(--text-soft)">Ukuran</p>
                                        <p class="font-semibold text-sm mt-0.5" style="color:var(--text-dark)">
                                            {{ $product->size ?? '—' }}</p>
                                    </div>
                                    <div class="rounded-xl p-3" style="background:var(--secondary)">
                                        <p class="text-xs" style="color:var(--text-soft)">Warna</p>
                                        <p class="font-semibold text-sm mt-0.5" style="color:var(--text-dark)">
                                            {{ $product->color ?? '—' }}</p>
                                    </div>
                                    <div class="rounded-xl p-3" style="background:var(--secondary)">
                                        <p class="text-xs" style="color:var(--text-soft)">Stok</p>
                                        <p class="font-semibold text-sm mt-0.5" style="color:var(--text-dark)">
                                            {{ $product->stock_available }}/{{ $product->stock_total }}
                                        </p>
                                    </div>
                                    <div class="rounded-xl p-3" style="background:var(--secondary)">
                                        <p class="text-xs" style="color:var(--text-soft)">Kondisi</p>
                                        <div class="mt-0.5">
                                            @if ($product->condition === 'excellent')
                                                <span class="badge badge-gold text-xs">Excellent</span>
                                            @elseif($product->condition === 'good')
                                                <span class="badge badge-green text-xs">Good</span>
                                            @elseif($product->condition === 'fair')
                                                <span class="badge badge-yellow text-xs">Fair</span>
                                            @else
                                                <span class="badge badge-red text-xs">Poor</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Stok progress --}}
                            <div class="mt-4">
                                <div class="flex items-center justify-between mb-1">
                                    <p class="text-xs" style="color:var(--text-soft)">Ketersediaan stok</p>
                                    <p class="text-xs font-medium" style="color:var(--text-dark)">
                                        {{ $product->stock_total > 0 ? round(($product->stock_available / $product->stock_total) * 100) : 0 }}%
                                    </p>
                                </div>
                                <div class="w-full h-1.5 rounded-full overflow-hidden" style="background:var(--border)">
                                    @php
                                        $pct =
                                            $product->stock_total > 0
                                                ? round(($product->stock_available / $product->stock_total) * 100)
                                                : 0;
                                        $barColor = $pct > 50 ? '#22c55e' : ($pct > 20 ? '#f59e0b' : '#ef4444');
                                    @endphp
                                    <div class="h-full rounded-full transition-all"
                                        style="width:{{ $pct }}%; background:{{ $barColor }}"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Deskripsi --}}
                @if ($product->description)
                    <div class="card p-6">
                        <div class="flex items-center gap-2 pb-3 mb-4 border-b" style="border-color:var(--border)">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center"
                                style="background:var(--secondary)">
                                <i data-lucide="align-left" class="w-3.5 h-3.5" style="color:var(--primary)"></i>
                            </div>
                            <h2 class="font-semibold text-sm" style="color:var(--text-dark)">Deskripsi</h2>
                        </div>
                        <p class="text-sm leading-relaxed" style="color:var(--text-soft)">{{ $product->description }}</p>
                    </div>
                @endif
            
                {{-- QR Code --}}
                <div class="card p-6">
                    <div class="flex items-center gap-2 pb-3 mb-4 border-b" style="border-color:var(--border)">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center"
                            style="background:var(--secondary)">
                            <i data-lucide="qr-code" class="w-3.5 h-3.5" style="color:var(--primary)"></i>
                        </div>
                        <h2 class="font-semibold text-sm" style="color:var(--text-dark)">QR Code Produk</h2>
                    </div>

                    @if ($product->qr_code)
                        <div class="flex flex-col items-center">
                            <div class="p-4 rounded-2xl mb-4" style="background:var(--secondary)">
                                <img src="{{ asset('storage/' . $product->qr_code) }}"
                                    alt="QR Code {{ $product->name }}"
                                    class="w-40 h-40 sm:w-48 sm:h-48 object-contain rounded-lg bg-white p-2">
                            </div>
                            <p class="text-xs mb-4 text-center" style="color:var(--text-soft)">
                                Scan untuk akses cepat detail produk ini
                            </p>
                            <a href="{{ route('products.qr.download', $product->id) }}" class="btn-primary w-full justify-center">
                                <i data-lucide="download" class="w-4 h-4"></i> Download QR
                            </a>
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-3"
                                style="background:var(--secondary)">
                                <i data-lucide="qr-code" class="w-5 h-5" style="color:var(--text-soft)"></i>
                            </div>
                            <p class="text-sm font-medium" style="color:var(--text-dark)">QR Code belum tersedia</p>
                            <p class="text-xs mt-1" style="color:var(--text-soft)">QR akan dibuat otomatis saat produk disimpan</p>
                        </div>
                    @endif
                </div>

                {{-- Riwayat Rental --}}
                <div class="card overflow-hidden">
                    <div class="p-6 pb-4 flex items-center justify-between border-b" style="border-color:var(--border)">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center"
                                style="background:var(--secondary)">
                                <i data-lucide="history" class="w-3.5 h-3.5" style="color:var(--primary)"></i>
                            </div>
                            <h2 class="font-semibold text-sm" style="color:var(--text-dark)">Riwayat Rental</h2>
                        </div>
                        @if (isset($rentals) && $rentals->isNotEmpty())
                            <span class="badge badge-gray">{{ $rentals->total() }} transaksi</span>
                        @endif
                    </div>

                    @if (isset($rentals) && $rentals->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="elegant-table w-full">
                                <thead>
                                    <tr>
                                        <th class="text-left">Pelanggan</th>
                                        <th class="text-center">Tanggal Sewa</th>
                                        <th class="text-center">Tanggal Kembali</th>
                                        <th class="text-right">Total</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rentals as $rental)
                                        <tr>
                                            <td>
                                                <p class="text-sm font-medium" style="color:var(--text-dark)">
                                                    {{ $rental->customer->name ?? '-' }}</p>
                                                <p class="text-xs" style="color:var(--text-soft)">
                                                    {{ $rental->customer->phone ?? '' }}</p>
                                            </td>
                                            <td class="text-center text-sm" style="color:var(--text-soft)">
                                                {{ $rental->rental_date?->format('d M Y') ?? '-' }}
                                            </td>
                                            <td class="text-center text-sm" style="color:var(--text-soft)">
                                                {{ ($rental->actual_return_date ?? $rental->return_due_date)?->format('d M Y') ?? '-' }}
                                            </td>
                                            <td class="text-right text-sm font-semibold" style="color:var(--text-dark)">
                                                Rp {{ number_format($rental->total_amount, 0, ',', '.') }}
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-{{ $rental->status_badge_color }}">
                                                    {{ $rental->status_label }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if ($rentals->hasPages())
                            <div class="px-6 py-4 border-t" style="border-color:var(--border)">
                                {{ $rentals->links() }}
                            </div>
                        @endif
                    @else
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-3"
                                style="background:var(--secondary)">
                                <i data-lucide="calendar-x" class="w-5 h-5" style="color:var(--text-soft)"></i>
                            </div>
                            <p class="text-sm font-medium" style="color:var(--text-dark)">Belum ada riwayat rental</p>
                            <p class="text-xs mt-1" style="color:var(--text-soft)">Produk ini belum pernah disewa</p>
                        </div>
                    @endif
                </div>

            </div>

            {{-- ── KOLOM KANAN ──────────────────────────────────── --}}
            <div class="space-y-6">

                {{-- Detail Info --}}
                <div class="card p-6 space-y-4">
                    <div class="flex items-center gap-2 pb-3 border-b" style="border-color:var(--border)">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center"
                            style="background:var(--secondary)">
                            <i data-lucide="list" class="w-3.5 h-3.5" style="color:var(--primary)"></i>
                        </div>
                        <h2 class="font-semibold text-sm" style="color:var(--text-dark)">Detail Produk</h2>
                    </div>

                    @php
                        $details = [
                            ['label' => 'Kode Produk', 'value' => $product->code, 'icon' => 'tag'],
                            ['label' => 'Kategori', 'value' => $product->category?->name ?? '—', 'icon' => 'folder'],
                            ['label' => 'Ukuran', 'value' => $product->size ?? '—', 'icon' => 'ruler'],
                            ['label' => 'Warna', 'value' => $product->color ?? '—', 'icon' => 'palette'],
                            ['label' => 'Stok Total', 'value' => $product->stock_total, 'icon' => 'layers'],
                            [
                                'label' => 'Stok Tersedia',
                                'value' => $product->stock_available,
                                'icon' => 'check-circle',
                            ],
                        ];
                    @endphp

                    <div class="space-y-3">
                        @foreach ($details as $d)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="{{ $d['icon'] }}" class="w-3.5 h-3.5 flex-shrink-0"
                                        style="color:var(--text-soft)"></i>
                                    <span class="text-sm" style="color:var(--text-soft)">{{ $d['label'] }}</span>
                                </div>
                                <span class="text-sm font-medium"
                                    style="color:var(--text-dark)">{{ $d['value'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Catatan --}}
                @if ($product->notes)
                    <div class="card p-6">
                        <div class="flex items-center gap-2 pb-3 mb-3 border-b" style="border-color:var(--border)">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center"
                                style="background:var(--secondary)">
                                <i data-lucide="file-text" class="w-3.5 h-3.5" style="color:var(--primary)"></i>
                            </div>
                            <h2 class="font-semibold text-sm" style="color:var(--text-dark)">Catatan Internal</h2>
                        </div>
                        <p class="text-sm leading-relaxed" style="color:var(--text-soft)">{{ $product->notes }}</p>
                    </div>
                @endif

                {{-- Timestamps --}}
                <div class="card p-5 space-y-3">
                    <div class="flex items-center gap-2">
                        <i data-lucide="clock" class="w-3.5 h-3.5" style="color:var(--text-soft)"></i>
                        <div>
                            <p class="text-xs" style="color:var(--text-soft)">Diperbarui</p>
                            <p class="text-sm font-medium" style="color:var(--text-dark)">
                                {{ $product->updated_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <i data-lucide="calendar-plus" class="w-3.5 h-3.5" style="color:var(--text-soft)"></i>
                        <div>
                            <p class="text-xs" style="color:var(--text-soft)">Ditambahkan</p>
                            <p class="text-sm font-medium" style="color:var(--text-dark)">
                                {{ $product->created_at->format('d M Y, H:i') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="card p-5 space-y-3">
                    <h3 class="text-xs font-semibold uppercase tracking-wide" style="color:var(--text-soft)">Aksi Cepat
                    </h3>
                    <a href="{{ route('products.edit', $product) }}" class="btn-primary w-full justify-center">
                        <i data-lucide="pencil" class="w-4 h-4"></i> Edit Produk
                    </a>
                    {{-- Tombol ubah status langsung --}}
                    @if ($product->status !== 'maintenance')
                        <form method="POST" action="{{ route('products.update', $product) }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="status" value="maintenance">
                            <input type="hidden" name="name" value="{{ $product->name }}">
                            <input type="hidden" name="code" value="{{ $product->code }}">
                            <input type="hidden" name="rental_price" value="{{ $product->rental_price }}">
                            <input type="hidden" name="stock_total" value="{{ $product->stock_total }}">
                            <input type="hidden" name="stock_available" value="{{ $product->stock_available }}">
                            <button type="submit"
                                class="btn-secondary w-full justify-center text-orange-500 hover:bg-orange-50">
                                <i data-lucide="wrench" class="w-4 h-4"></i> Tandai Maintenance
                            </button>
                        </form>
                    @endif
                    @if ($product->status !== 'available')
                        <form method="POST" action="{{ route('products.update', $product) }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="status" value="available">
                            <input type="hidden" name="name" value="{{ $product->name }}">
                            <input type="hidden" name="code" value="{{ $product->code }}">
                            <input type="hidden" name="rental_price" value="{{ $product->rental_price }}">
                            <input type="hidden" name="stock_total" value="{{ $product->stock_total }}">
                            <input type="hidden" name="stock_available" value="{{ $product->stock_available }}">
                            <button type="submit"
                                class="btn-secondary w-full justify-center text-green-600 hover:bg-green-50">
                                <i data-lucide="check-circle" class="w-4 h-4"></i> Tandai Tersedia
                            </button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('products.destroy', $product) }}"
                        onsubmit="return confirm('Yakin hapus produk ini? Tindakan tidak dapat dibatalkan.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-secondary w-full justify-center text-red-500 hover:bg-red-50">
                            <i data-lucide="trash-2" class="w-4 h-4"></i> Hapus Produk
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            lucide.createIcons();
        </script>
    @endpush
@endsection