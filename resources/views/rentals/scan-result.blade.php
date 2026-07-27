@extends('layouts.app')

@section('title', 'Hasil Scan - ' . $rental->invoice_number)
@section('page-title', 'Hasil Scan QR')
@section('subtitle', $rental->invoice_number)

@section('content')
<div class="max-w-3xl mx-auto space-y-5" id="scan-result-content">

    {{-- ─── BACK & ACTIONS ─────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('rentals.scan') }}" class="btn-secondary">
            <i data-lucide="scan-qr-code" class="w-4 h-4"></i>
            Scan Lagi
        </a>
        <a href="{{ route('rentals.show', $rental) }}" class="btn-secondary">
            <i data-lucide="external-link" class="w-4 h-4"></i>
            Detail Lengkap
        </a>
    </div>

    {{-- ─── STEPPER: penanda progres alur retur ───────────────────────
         PATCH BARU: supaya kasir/petugas tidak scroll panjang tanpa arah --}}
    @php
        $stepActive = in_array($rental->rental_status, ['active', 'overdue']) && $rental->payment_status === 'paid';
        $stepDone   = $rental->rental_status === 'returned';
    @endphp
    <div class="flex items-center justify-center gap-1 sm:gap-2 px-2">
        @foreach([
            ['label' => 'Detail Transaksi', 'icon' => 'file-text'],
            ['label' => 'Cek Kondisi Barang', 'icon' => 'shirt'],
            ['label' => 'Konfirmasi', 'icon' => 'check-circle-2'],
        ] as $idx => $step)
        @php
            $isDone = $stepDone || ($idx === 0);
            $isCurrent = !$stepDone && $idx === 1 && $stepActive;
        @endphp
        <div class="flex items-center gap-1.5 sm:gap-2 {{ $idx < 2 ? 'flex-1' : '' }}">
            <div class="flex flex-col items-center gap-1 flex-shrink-0">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all"
                     style="{{ $isDone
                        ? 'background:#10B981;color:#fff;'
                        : ($isCurrent
                            ? 'background:var(--primary);color:#fff;box-shadow:0 0 0 4px var(--primary-tint);'
                            : 'background:var(--bg-main);color:var(--text-soft);border:1.5px solid var(--border);') }}">
                    @if($isDone && $idx < 2)
                        <i data-lucide="check" class="w-4 h-4"></i>
                    @else
                        <i data-lucide="{{ $step['icon'] }}" class="w-3.5 h-3.5"></i>
                    @endif
                </div>
                <span class="text-[10px] font-medium text-center hidden sm:block"
                      style="color: {{ $isDone || $isCurrent ? 'var(--text-dark)' : 'var(--text-soft)' }}">
                    {{ $step['label'] }}
                </span>
            </div>
            @if($idx < 2)
            <div class="h-0.5 flex-1 rounded-full" style="background: {{ $isDone ? '#10B981' : 'var(--border)' }}"></div>
            @endif
        </div>
        @endforeach
    </div>

    {{-- ─── STATUS CARD ─────────────────────────────────────────────── --}}
    <div class="card p-6">
        <div class="flex items-start justify-between mb-6">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider" style="color: #D6B98C">Invoice</p>
                <h2 class="font-playfair font-bold text-2xl mt-1" style="color: var(--text-dark)">
                    {{ $rental->invoice_number }}
                </h2>
            </div>
            <div class="flex flex-col gap-2 items-end">
                <span class="badge badge-{{ $rental->status_badge_color }} text-sm px-4 py-1.5">
                    {{ $rental->status_label }}
                </span>
                <span class="badge {{ match($rental->payment_status) {
                    'paid'    => 'badge-green',
                    'partial' => 'badge-yellow',
                    default   => 'badge-red'
                } }}">
                    {{ $rental->payment_status_label }}
                </span>
            </div>
        </div>

        {{-- Alert terlambat --}}
        @if($rental->rental_status === 'overdue')
        <div class="flex items-center gap-3 p-4 rounded-xl mb-5"
             style="background: #FFF1F0; border: 1px solid #FECACA">
            <i data-lucide="alert-triangle" class="w-5 h-5" style="color: #C0392B"></i>
            <div>
                <p class="font-semibold text-sm" style="color: #C0392B">
                    Penyewaan Terlambat {{ $feeData['late_days'] }} Hari!
                </p>
                <p class="text-xs" style="color: #E74C3C">
                    Jatuh tempo: {{ $rental->return_due_date->format('d M Y') }}
                </p>
            </div>
        </div>
        @endif

        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div class="p-4 rounded-xl" style="background: var(--bg-main)">
                <p class="text-xs" style="color: var(--text-soft)">Customer</p>
                <p class="font-bold mt-1" style="color: var(--text-dark)">{{ $rental->customer->name }}</p>
                <p class="text-sm" style="color: var(--text-soft)">{{ $rental->customer->phone }}</p>
            </div>
            <div class="p-4 rounded-xl" style="background: var(--bg-main)">
                <p class="text-xs" style="color: var(--text-soft)">Tanggal Sewa</p>
                <p class="font-bold mt-1" style="color: var(--text-dark)">
                    {{ $rental->rental_date->format('d M Y') }}
                </p>
                <p class="text-xs mt-0.5" style="color: var(--text-soft)">
                    {{ $rental->duration_days }} hari (paket)
                </p>
            </div>
            <div class="p-4 rounded-xl"
                 style="background: {{ $rental->rental_status === 'overdue' ? '#FFF1F0' : 'var(--bg-main)' }}">
                <p class="text-xs" style="color: var(--text-soft)">Jatuh Tempo</p>
                <p class="font-bold mt-1 {{ $rental->rental_status === 'overdue' ? 'text-red-500' : '' }}"
                   style="{{ $rental->rental_status !== 'overdue' ? 'color: var(--text-dark)' : '' }}">
                    {{ $rental->return_due_date->format('d M Y') }}
                </p>
            </div>
        </div>
    </div>

    {{-- ─── BARANG DISEWA ───────────────────────────────────────────── --}}
    <div class="card p-6" style="border-top: 3px solid var(--primary)">
        <h3 class="font-playfair font-semibold text-base mb-4 flex items-center gap-2" style="color: var(--text-dark)">
            <i data-lucide="shirt" class="w-4 h-4" style="color: var(--primary)"></i>
            Barang Disewa
        </h3>
        <div class="space-y-3">
            @foreach($rental->items as $item)
            <div class="flex items-center justify-between p-4 rounded-xl"
                 style="background: var(--bg-main); border: 1px solid var(--border)">
                <div class="flex items-center gap-3">
                    @if($item->product && $item->product->photo)
                    <img src="{{ asset('storage/' . $item->product->photo) }}"
                         class="w-12 h-12 rounded-xl object-cover">
                    @else
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center"
                         style="background: var(--secondary)">
                        <i data-lucide="shirt" class="w-6 h-6" style="color: var(--primary)"></i>
                    </div>
                    @endif
                    <div>
                        <p class="font-semibold" style="color: var(--text-dark)">{{ $item->product_name }}</p>
                        <p class="text-sm" style="color: var(--text-soft)">
                            Uk: {{ $item->product_size ?? '-' }} | Qty: {{ $item->quantity }}
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-bold" style="color: var(--text-dark)">
                        Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                    </p>
                    @if($item->is_returned)
                    <span class="badge badge-green mt-1">Sudah Kembali</span>
                    @else
                    <span class="badge badge-yellow mt-1">Belum Kembali</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-4 pt-4 border-t flex justify-between items-center"
             style="border-color: var(--border)">
            <span class="font-bold" style="color: var(--text-dark)">Total Sewa</span>
            <span class="text-xl font-bold font-playfair" style="color: var(--primary)">
                Rp {{ number_format($rental->total_amount, 0, ',', '.') }}
            </span>
        </div>
    </div>

    {{-- ─── JAMINAN ─────────────────────────────────────────────────── --}}
    @if($rental->guarantees->count() > 0)
    <div class="card p-6" style="border-top: 3px solid #D6A94A">
        <h3 class="font-playfair font-semibold text-base mb-4 flex items-center gap-2" style="color: var(--text-dark)">
            <i data-lucide="shield-check" class="w-4 h-4" style="color: #D6A94A"></i>
            Jaminan
        </h3>
        @foreach($rental->guarantees as $g)
        <div class="flex items-center justify-between p-3 rounded-xl" style="background: var(--bg-main)">
            <div>
                <p class="font-semibold text-sm" style="color: var(--text-dark)">{{ $g->type_label }}</p>
                @if($g->id_number)
                <p class="text-xs mt-0.5" style="color: var(--text-soft)">
                    {{ $g->id_number }} — {{ $g->id_name }}
                </p>
                @endif
                @if($g->deposit_amount > 0)
                <p class="text-xs mt-0.5" style="color: var(--text-soft)">
                    Deposit: Rp {{ number_format($g->deposit_amount, 0, ',', '.') }}
                </p>
                @endif
            </div>
            <span class="badge {{ match($g->status) {
                'held'      => 'badge-yellow',
                'returned'  => 'badge-green',
                'forfeited' => 'badge-red',
                default     => 'badge-gray'
            } }}">
                {{ match($g->status) {
                    'held'      => 'Ditahan',
                    'returned'  => 'Dikembalikan',
                    'forfeited' => 'Disita',
                    default     => $g->status
                } }}
            </span>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ─── DENDA (tampil jika terlambat & belum dikembalikan) ─────── --}}
    @if(in_array($rental->rental_status, ['active', 'overdue']) && $feeData['late_days'] > 0)
    <div class="card p-6" style="border-top: 3px solid #C0392B; background: #FFFBFB">
        <h3 class="font-playfair font-semibold text-base mb-4 flex items-center gap-2" style="color: #C0392B">
            <i data-lucide="alert-triangle" class="w-4 h-4"></i>
            Estimasi Denda Keterlambatan
        </h3>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between">
                <span style="color: var(--text-soft)">Terlambat</span>
                <span class="font-semibold" style="color: #C0392B">{{ $feeData['late_days'] }} hari</span>
            </div>
            <div class="flex justify-between">
                <span style="color: var(--text-soft)">Denda per hari</span>
                <span style="color: var(--text-dark)">Rp 10.000</span>
            </div>
            <div class="flex justify-between font-bold pt-2 border-t" style="border-color: #FECACA">
                <span style="color: var(--text-dark)">Total Denda</span>
                <span style="color: #C0392B">
                    Rp {{ number_format($feeData['late_fee'], 0, ',', '.') }}
                </span>
            </div>
        </div>
    </div>
    @endif

    {{-- ─── FORM PENGEMBALIAN ───────────────────────────────────────── --}}
    @if(in_array($rental->rental_status, ['active', 'overdue']) && $rental->payment_status === 'paid')
    @php
        $returnItems = $rental->items->where('is_returned', false)->values();
        $guaranteeAvailable = $rental->guarantees->where('status', 'held')->count() > 0;
    @endphp
    <div x-data="returnForm({{ $returnItems->pluck('id') }}, {{ (float) $rental->subtotal }}, {{ (float) ($feeData['late_fee'] ?? 0) }}, {{ $returnItems->pluck('subtotal', 'id') }})" class="space-y-5">

        {{-- ─── DISKON MANUAL (BARU) ────────────────────────────────────── --}}
        <div class="card p-6" style="border-top: 3px solid #0EA5E9">
            <h3 class="font-playfair font-semibold text-base mb-4 flex items-center gap-2" style="color: var(--text-dark)">
                <i data-lucide="badge-percent" class="w-4 h-4" style="color: #0EA5E9"></i>
                Diskon Manual
            </h3>
            <p class="text-xs mb-4" style="color: var(--text-soft)">
                Opsional — kosongkan kalau tidak ada diskon untuk pengembalian ini.
            </p>

            <div class="grid sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-soft)">Nama Diskon</label>
                    <input type="text" form="returnForm" name="discount_name" x-model="discountName"
                           list="discount-name-suggestions"
                           class="form-input" placeholder="Mis. Diskon Member, Voucher, Kompensasi...">
                    <datalist id="discount-name-suggestions">
                        <option value="Diskon Member">
                        <option value="Voucher">
                        <option value="Kompensasi">
                        <option value="Diskon Loyalitas">
                    </datalist>
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-soft)">Tipe Diskon</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center justify-center gap-1.5 py-2.5 rounded-xl border-2 cursor-pointer text-sm font-semibold select-none"
                               style="transition: all .15s ease;"
                               :style="discountType === 'nominal'
                                    ? 'border-color:#0EA5E9;background:#EFF9FF;color:#0369A1'
                                    : 'border-color:var(--border);color:var(--text-soft)'">
                            <input type="radio" form="returnForm" name="discount_type" value="nominal" x-model="discountType" class="sr-only">
                            <i data-lucide="banknote" class="w-3.5 h-3.5"></i>
                            Nominal
                        </label>
                        <label class="flex items-center justify-center gap-1.5 py-2.5 rounded-xl border-2 cursor-pointer text-sm font-semibold select-none"
                               style="transition: all .15s ease;"
                               :style="discountType === 'percent'
                                    ? 'border-color:#0EA5E9;background:#EFF9FF;color:#0369A1'
                                    : 'border-color:var(--border);color:var(--text-soft)'">
                            <input type="radio" form="returnForm" name="discount_type" value="percent" x-model="discountType" class="sr-only">
                            <i data-lucide="percent" class="w-3.5 h-3.5"></i>
                            Persen
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-soft)">Nilai Diskon</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold" style="color: var(--text-soft)"
                              x-text="discountType === 'percent' ? '%' : 'Rp'"></span>
                        <input type="number" form="returnForm" name="discount_value" x-model.number="discountValue"
                               min="0" :max="discountType === 'percent' ? 100 : null" step="any"
                               class="form-input pl-10" placeholder="0">
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-soft)">Deskripsi (opsional)</label>
                    <textarea form="returnForm" name="discount_description" x-model="discountDescription" rows="2"
                              class="form-input" placeholder="Catatan tambahan tentang diskon ini..."></textarea>
                </div>
            </div>
        </div>

        {{-- ─── RINGKASAN PEMBAYARAN (BARU) ─────────────────────────────── --}}
        <div class="card p-6" style="border-top: 3px solid var(--primary)">
            <h3 class="font-playfair font-semibold text-base mb-4 flex items-center gap-2" style="color: var(--text-dark)">
                <i data-lucide="receipt" class="w-4 h-4" style="color: var(--primary)"></i>
                Ringkasan Pembayaran
            </h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span style="color: var(--text-soft)">Subtotal Sewa</span>
                    <span style="color: var(--text-dark)" x-text="fmt(subtotal)"></span>
                </div>
                <div class="flex justify-between" x-show="lateFee > 0">
                    <span style="color: var(--text-soft)">Denda Keterlambatan</span>
                    <span style="color: #C0392B" x-text="fmt(lateFee)"></span>
                </div>
                <div class="flex justify-between" x-show="totalDamageFee > 0">
                    <span style="color: var(--text-soft)">Denda Rusak/Hilang</span>
                    <span style="color: #C0392B" x-text="fmt(totalDamageFee)"></span>
                </div>
                <div class="flex justify-between" x-show="discountAmount > 0">
                    <span style="color: var(--text-soft)">Diskon Manual</span>
                    <span style="color: #0369A1" x-text="'-' + fmt(discountAmount)"></span>
                </div>
                <div class="flex justify-between font-bold pt-3 border-t" style="border-color: var(--border)">
                    <span style="color: var(--text-dark)">Grand Total</span>
                    <span class="text-lg font-playfair" style="color: var(--primary)" x-text="fmt(grandTotal)"></span>
                </div>
            </div>
        </div>

    {{-- ─── FORM PENGEMBALIAN (existing) ────────────────────────────── --}}
    <div class="card p-6" style="border-top: 3px solid #7C3AED">
        <div class="flex items-center justify-between flex-wrap gap-3 mb-2">
            <h3 class="font-playfair font-semibold text-base flex items-center gap-2" style="color: var(--text-dark)">
                <i data-lucide="clipboard-check" class="w-4 h-4" style="color: #7C3AED"></i>
                Form Pengembalian
            </h3>
            {{-- Bulk action --}}
            <button type="button" @click="markAllGood()"
                    class="text-xs font-semibold px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5"
                    style="background: #F0FDF4; color: #15803D; border: 1px solid #BBF7D0">
                <i data-lucide="check-check" class="w-3.5 h-3.5"></i>
                Tandai Semua Baik
            </button>
        </div>

        {{-- Badge ringkasan warna-warni, update live --}}
        <div class="flex gap-2 flex-wrap mb-5">
            <span class="text-xs font-bold px-2.5 py-1 rounded-full flex items-center gap-1"
                  style="background:#DCFCE7;color:#15803D">
                <i data-lucide="check-circle" class="w-3 h-3"></i>
                <span x-text="counts.good"></span> Baik
            </span>
            <span class="text-xs font-bold px-2.5 py-1 rounded-full flex items-center gap-1"
                  style="background:#FEF3C7;color:#B7791F">
                <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                <span x-text="counts.damaged"></span> Rusak
            </span>
            <span class="text-xs font-bold px-2.5 py-1 rounded-full flex items-center gap-1"
                  style="background:#FEE2E2;color:#C0392B">
                <i data-lucide="x-circle" class="w-3 h-3"></i>
                <span x-text="counts.lost"></span> Hilang
            </span>
        </div>

        @if(session('error'))
        <div class="p-4 rounded-xl mb-4" style="background: #FFF1F0; border: 1px solid #FECACA">
            <p class="text-sm font-semibold" style="color: #C0392B">{{ session('error') }}</p>
        </div>
        @endif

        <form method="POST" id="returnForm" action="{{ route('rentals.return', $rental) }}"
              @submit="isLoading = true">
            @csrf

            {{-- Loop per item --}}
            @foreach($returnItems as $index => $item)
            <div class="mb-4 p-4 rounded-xl" style="background: var(--bg-main); border: 1px solid var(--border)">
                <p class="font-semibold text-sm mb-3" style="color: var(--text-dark)">
                    {{ $item->product_name }}
                    @if($item->product_size) <span style="color:var(--text-soft)">(Uk: {{ $item->product_size }})</span> @endif
                </p>

                <input type="hidden" name="items[{{ $index }}][rental_item_id]" value="{{ $item->id }}">

                {{-- Kondisi — tombol berwarna, hidup begitu dipilih --}}
                <label class="block text-xs font-semibold mb-2" style="color: var(--text-soft)">
                    Kondisi <span class="text-red-400">*</span>
                </label>
                <div class="grid grid-cols-3 gap-2 mb-3">
                    @foreach([
                        'good'    => ['label' => 'Baik',   'icon' => 'check-circle',   'bg' => '#F0FDF4', 'border' => '#22C55E', 'text' => '#15803D'],
                        'damaged' => ['label' => 'Rusak',  'icon' => 'alert-triangle', 'bg' => '#FFFBEB', 'border' => '#F59E0B', 'text' => '#B7791F'],
                        'lost'    => ['label' => 'Hilang', 'icon' => 'x-circle',       'bg' => '#FEF2F2', 'border' => '#EF4444', 'text' => '#C0392B'],
                    ] as $val => $c)
                    <label class="relative flex flex-col items-center justify-center gap-1 py-2.5 rounded-xl border-2 cursor-pointer text-center select-none"
                           style="transition: all .15s ease; border-color: var(--border); color: var(--text-soft);"
                           :style="conditions[{{ $item->id }}] === '{{ $val }}'
                                ? 'transition:all .15s ease;background:{{ $c['bg'] }};border-color:{{ $c['border'] }};color:{{ $c['text'] }};transform:scale(1.03);box-shadow:0 2px 8px rgba(0,0,0,0.08)'
                                : 'transition:all .15s ease;border-color:var(--border);color:var(--text-soft)'">
                        <input type="radio" name="items[{{ $index }}][condition]"
                               value="{{ $val }}"
                               x-model="conditions[{{ $item->id }}]"
                               {{ $val === 'good' ? 'checked' : '' }}
                               class="sr-only">
                        <i data-lucide="{{ $c['icon'] }}" class="w-4 h-4"
                           :style="conditions[{{ $item->id }}] === '{{ $val }}' ? 'color:{{ $c['border'] }}' : 'color:var(--text-soft)'"></i>
                        <span class="text-[11px] font-bold">{{ $c['label'] }}</span>
                    </label>
                    @endforeach
                </div>

                {{-- BARU: Resolusi Kerugian — muncul otomatis kalau kondisi Rusak/Hilang --}}
                <div x-show="conditions[{{ $item->id }}] !== 'good'" x-cloak
                     class="mb-3 p-3 rounded-xl" style="background: #FFF8F0; border: 1px dashed #F0B860">
                    <label class="block text-xs font-semibold mb-2" style="color: #B7791F">
                        <i data-lucide="scale" class="w-3.5 h-3.5 inline mr-1"></i>
                        Resolusi Kerugian <span class="text-red-400">*</span>
                    </label>
                    <div class="grid grid-cols-1 {{ $guaranteeAvailable ? 'sm:grid-cols-2' : '' }} gap-2">
                        <label class="flex items-center gap-2 px-3 py-2.5 rounded-lg border-2 cursor-pointer text-xs font-semibold select-none"
                               style="transition: all .15s ease;"
                               :style="penaltyResolutions[{{ $item->id }}] === 'charge_double'
                                    ? 'border-color:#EF4444;background:#FEF2F2;color:#C0392B'
                                    : 'border-color:var(--border);color:var(--text-soft)'">
                            <input type="radio" name="items[{{ $index }}][penalty_resolution]" value="charge_double"
                                   x-model="penaltyResolutions[{{ $item->id }}]"
                                   :required="conditions[{{ $item->id }}] !== 'good'"
                                   class="sr-only">
                            <i data-lucide="banknote" class="w-3.5 h-3.5 flex-shrink-0"></i>
                            <span>Denda 2x Harga <span class="opacity-75">(Rp {{ number_format($item->subtotal * 2, 0, ',', '.') }})</span></span>
                        </label>
                        @if($guaranteeAvailable)
                        <label class="flex items-center gap-2 px-3 py-2.5 rounded-lg border-2 cursor-pointer text-xs font-semibold select-none"
                               style="transition: all .15s ease;"
                               :style="penaltyResolutions[{{ $item->id }}] === 'claim_guarantee'
                                    ? 'border-color:#7C3AED;background:#F5F3FF;color:#6D28D9'
                                    : 'border-color:var(--border);color:var(--text-soft)'">
                            <input type="radio" name="items[{{ $index }}][penalty_resolution]" value="claim_guarantee"
                                   x-model="penaltyResolutions[{{ $item->id }}]"
                                   :required="conditions[{{ $item->id }}] !== 'good'"
                                   class="sr-only">
                            <i data-lucide="shield-alert" class="w-3.5 h-3.5 flex-shrink-0"></i>
                            <span>Sita Jaminan (KTP/Deposit)</span>
                        </label>
                        @endif
                    </div>
                    <p class="text-[11px] mt-2" style="color: #B7791F" x-show="penaltyResolutions[{{ $item->id }}] === 'claim_guarantee'" x-cloak>
                        <i data-lucide="alert-triangle" class="w-3 h-3 inline mr-0.5"></i>
                        Jaminan akan berstatus "Disita" dan tidak dikembalikan ke customer.
                    </p>
                </div>

                {{-- Catatan --}}
                <input type="text" name="items[{{ $index }}][notes]"
                       class="form-input text-xs w-full"
                       placeholder="Catatan kondisi barang (opsional)...">
            </div>
            @endforeach

            {{-- Ringkasan denda — lebih menonjol --}}
            @if($feeData['late_days'] > 0)
            <div class="p-4 rounded-xl mb-5 flex items-center justify-between" style="background: linear-gradient(135deg,#FFF8E7,#FFF1D6); border: 1px solid #F6E4B0">
                <div class="flex items-center gap-2">
                    <i data-lucide="clock-alert" class="w-5 h-5" style="color:#B7791F"></i>
                    <div>
                        <p class="text-xs font-semibold" style="color:#B7791F">Denda Keterlambatan</p>
                        <p class="text-[11px]" style="color:#B7791F">{{ $feeData['late_days'] }} hari × Rp 10.000</p>
                    </div>
                </div>
                <span class="text-lg font-bold font-playfair" style="color:#B7791F">
                    Rp {{ number_format($feeData['late_fee'], 0, ',', '.') }}
                </span>
            </div>
            @endif

            {{-- Tombol Submit --}}
            <button type="submit"
                    data-no-loading
                    :disabled="isLoading"
                    :class="isLoading ? 'btn-loading opacity-75' : 'hover:scale-105'"
                    x-on:click.prevent="if (!isLoading && confirm('Konfirmasi pengembalian barang dari {{ $rental->customer->name }}?')) { isLoading = true; $el.closest('form').submit(); }"
                    class="w-full py-4 rounded-xl font-bold text-white text-base transition-all flex items-center justify-center gap-3"
                    style="background: linear-gradient(135deg, #10B981, #059669);
                           box-shadow: 0 4px 20px rgba(16,185,129,0.4)">
                <template x-if="isLoading">
                    <span class="btn-spinner" style="border-color: rgba(255,255,255,0.5); border-top-color: transparent"></span>
                </template>
                <template x-if="!isLoading">
                    <i data-lucide="package-check" class="w-6 h-6"></i>
                </template>
                <span x-text="isLoading ? 'Memproses Pengembalian...' : 'Konfirmasi Barang Sudah Dikembalikan'"></span>
            </button>
        </form>
    </div>
    </div>

    {{-- Belum lunas --}}
    @elseif(in_array($rental->rental_status, ['active', 'overdue']) && $rental->payment_status !== 'paid')
    <div class="p-4 rounded-xl text-center" style="background: #FFF8E7; border: 1px solid #F6E4B0">
        <p class="font-semibold text-sm" style="color: #B7791F">
            ⚠️ Belum Lunas — Selesaikan Pembayaran Dulu
        </p>
    </div>
    @endif

    {{-- ─── SUDAH DIKEMBALIKAN ──────────────────────────────────────── --}}
    @if($rental->rental_status === 'returned')
    <div class="card p-6">
        <div class="flex items-center gap-2 mb-5">
            <i data-lucide="check-circle-2" class="w-5 h-5" style="color: #15803D"></i>
            <h3 class="font-playfair font-semibold text-base" style="color: #15803D">
                Penyewaan Selesai & Dikembalikan
            </h3>
        </div>

        @if($rental->returnRecord)
        @php $ret = $rental->returnRecord; @endphp
        <div class="space-y-3 text-sm">
            <div class="flex justify-between items-center p-3 rounded-xl"
                 style="background: var(--bg-main)">
                <span style="color: var(--text-soft)">Tanggal Kembali</span>
                <span class="font-semibold" style="color: var(--text-dark)">
                    {{ $ret->returned_at->format('d M Y') }}
                </span>
            </div>
            <div class="flex justify-between items-center p-3 rounded-xl"
                 style="background: var(--bg-main)">
                <span style="color: var(--text-soft)">Kondisi Barang</span>
                <span class="badge badge-{{ $ret->condition_badge_color }}">
                    {{ $ret->condition_label }}
                </span>
            </div>
            @if($ret->late_days > 0)
            <div class="flex justify-between items-center p-3 rounded-xl"
                 style="background: #FFF1F0">
                <span style="color: var(--text-soft)">Denda Keterlambatan</span>
                <span class="font-semibold" style="color: #C0392B">
                    Rp {{ number_format($ret->late_fee, 0, ',', '.') }}
                    ({{ $ret->late_days }} hari)
                </span>
            </div>
            @else
            <div class="flex justify-between items-center p-3 rounded-xl"
                 style="background: #F0FDF4">
                <span style="color: var(--text-soft)">Keterlambatan</span>
                <span class="font-semibold" style="color: #15803D">Tepat Waktu ✓</span>
            </div>
            @endif
            @if($ret->return_notes)
            <div class="p-3 rounded-xl" style="background: var(--bg-main)">
                <p class="text-xs mb-1" style="color: var(--text-soft)">Catatan Pengembalian</p>
                <p style="color: var(--text-dark)">{{ $ret->return_notes }}</p>
            </div>
            @endif
        </div>
        @endif
    </div>
    @endif

    {{-- Flash success --}}
    @if(session('success'))
    <div class="p-4 rounded-xl flex items-center gap-3"
         style="background: #F0FDF4; border: 1px solid #BBF7D0">
        <i data-lucide="check-circle-2" class="w-5 h-5" style="color: #15803D"></i>
        <p class="font-semibold text-sm" style="color: #15803D">{{ session('success') }}</p>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
function returnForm(itemIds, subtotal, lateFee, itemSubtotals) {
    return {
        isLoading: false,
        conditions: Object.fromEntries(itemIds.map(id => [id, 'good'])),
        get counts() {
            const vals = Object.values(this.conditions);
            return {
                good:    vals.filter(v => v === 'good').length,
                damaged: vals.filter(v => v === 'damaged').length,
                lost:    vals.filter(v => v === 'lost').length,
            };
        },
        markAllGood() {
            for (const id in this.conditions) this.conditions[id] = 'good';
            this.penaltyResolutions = {};
        },

        // ── Denda Rusak/Hilang (BARU) ───────────────────────────────────────
        itemSubtotals: itemSubtotals,
        penaltyResolutions: {},
        get totalDamageFee() {
            let sum = 0;
            for (const id in this.penaltyResolutions) {
                if (this.penaltyResolutions[id] === 'charge_double') {
                    sum += (Number(this.itemSubtotals[id]) || 0) * 2;
                }
            }
            return sum;
        },

        // ── Diskon Manual (BARU) ────────────────────────────────────────────
        subtotal: subtotal,
        lateFee: lateFee,
        discountName: '',
        discountType: 'nominal',
        discountValue: null,
        discountDescription: '',
        get discountAmount() {
            const val = Number(this.discountValue) || 0;
            if (val <= 0) return 0;
            const base = this.subtotal + this.lateFee;
            const amount = this.discountType === 'percent' ? (base * val / 100) : val;
            return Math.max(0, Math.min(amount, base));
        },
        get grandTotal() {
            return Math.max(0, this.subtotal + this.lateFee + this.totalDamageFee - this.discountAmount);
        },
        fmt(n) {
            return 'Rp ' + Math.round(n).toLocaleString('id-ID');
        },
    }
}
</script>
@endpush