@extends('layouts.app')

@section('title', 'Hasil Scan - ' . $rental->invoice_number)
@section('page-title', 'Hasil Scan QR')
@section('subtitle', $rental->invoice_number)

@section('content')
<div class="max-w-3xl mx-auto space-y-5" id="scan-result-content"
     x-data="latePaymentModal(
        {{ session('open_late_fee_payment') ? 'true' : 'false' }},
        {{-- FIX BUG (penyebab 500 error): payments.type di database adalah
             ENUM ['rental','deposit','late_fee','damage_fee','refund'] —
             HARUS 'damage_fee', bukan 'damage'. --}}
        '{{ $rental->needs_return_payment ? 'damage_fee' : 'late_fee' }}'
     )">

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

        @php
        $stepActive = $rental->can_be_returned;
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

    {{-- ─── DENDA BELUM DIBAYAR / KEKURANGAN TAGIHAN RETUR ── FITUR BARU:
         2 kasus berbeda ditangani di sini —
         1) needs_late_fee_payment : denda TELAT belum lunas (sebelum barang dinilai kondisinya)
         2) needs_return_payment   : kondisi barang SUDAH dinilai (mis. ada yang
            rusak/hilang, dibebankan tunai) tapi belum lunas -> barang belum
            dianggap dikembalikan.
         Kondisi lama (setelah retur selesai, misal baru bayar sebagian) tetap dipertahankan. ── --}}
    @php
        $showLateFeeBanner = $rental->needs_late_fee_payment
            || (in_array($rental->rental_status, ['menunggu_laundry', 'dalam_laundry', 'siap_disewakan', 'returned'])
                && $rental->payment_status !== 'paid'
                && $rental->late_fee > 0
                && $rental->remaining_amount > 0);
        $showReturnPaymentBanner = $rental->needs_return_payment;
    @endphp
    @if($showLateFeeBanner)
    <div class="p-4 rounded-xl flex items-center justify-between gap-3 flex-wrap"
         style="background: #FFF1F0; border: 1px solid #FECACA">
        <div class="flex items-center gap-3">
            <i data-lucide="alarm-clock" class="w-5 h-5 flex-shrink-0" style="color: #C0392B"></i>
            <div>
                <p class="font-semibold text-sm" style="color: #C0392B">Denda Keterlambatan Belum Dibayar</p>
                <p class="text-xs" style="color: #E74C3C">
                    Sisa tagihan denda: Rp {{ number_format($rental->remaining_amount, 0, ',', '.') }}
                    @if($rental->rental_status === 'overdue')
                        — barang <strong>belum bisa dikembalikan</strong> sebelum ini lunas.
                    @endif
                </p>
            </div>
        </div>
        <button @click="openLateFeePayment('late_fee')" type="button"
                class="btn-primary text-sm"
                style="background: linear-gradient(135deg, #C0392B, #E74C3C)">
            <i data-lucide="credit-card" class="w-4 h-4"></i>
            Bayar Denda
        </button>
    </div>
    @endif

    @if($showReturnPaymentBanner)
    <div class="p-4 rounded-xl flex items-center justify-between gap-3 flex-wrap"
         style="background: #FFF1F0; border: 1px solid #FECACA">
        <div class="flex items-center gap-3">
            <i data-lucide="lock" class="w-5 h-5 flex-shrink-0" style="color: #C0392B"></i>
            <div>
                <p class="font-semibold text-sm" style="color: #C0392B">Kondisi Barang Sudah Dicatat, Belum Lunas</p>
                <p class="text-xs" style="color: #E74C3C">
                    Sisa tagihan: Rp {{ number_format($rental->remaining_amount, 0, ',', '.') }}
                    — barang <strong>belum dianggap dikembalikan</strong> sebelum ini lunas.
                </p>
            </div>
        </div>
        <button @click="openLateFeePayment('damage_fee')" type="button"
                class="btn-primary text-sm"
                style="background: linear-gradient(135deg, #C0392B, #E74C3C)">
            <i data-lucide="banknote" class="w-4 h-4"></i>
            Bayar Kekurangan
        </button>
    </div>
    @endif

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

    {{-- ─── FITUR BARU: DENDA HARUS LUNAS DULU SEBELUM BARANG DIKEMBALIKAN ───
         Alur overdue sekarang 2 langkah:
           1) Staf menentukan nominal denda (endpoint terpisah, bisa Rp 0)
           2) Denda WAJIB lunas dibayar (banner "Bayar Denda" di atas)
         Baru setelah itu form pengembalian fisik di bawah ini terbuka. ── --}}
    @if($rental->rental_status === 'overdue' && $feeData['late_days'] > 0)

        @if($rental->needs_late_fee_confirmation)
        {{-- Langkah 1: staf belum menentukan nominal denda sama sekali --}}
        <div class="card p-6" style="border-top: 3px solid #C0392B; background: #FFFBFB">
            <h3 class="font-playfair font-semibold text-base mb-1 flex items-center gap-2" style="color: #C0392B">
                <i data-lucide="banknote" class="w-4 h-4"></i>
                Tentukan Denda Keterlambatan
            </h3>
            <p class="text-xs mb-4" style="color: var(--text-soft)">
                Rental ini terlambat <strong>{{ $feeData['late_days'] }} hari</strong>. Tentukan nominal denda
                sesuai kebijakan toko — barang <strong>baru bisa diproses pengembalian</strong> setelah
                denda ini ditentukan &amp; dibayar lunas. Isi <strong>0</strong> kalau memang tidak ada denda.
            </p>

            <form method="POST" action="{{ route('rentals.late-fee.set', $rental) }}"
                  x-data="{ isLoading: false }"
                  @submit="isLoading = true">
                @csrf
                <div class="grid sm:grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">Nominal Denda</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm" style="color: var(--text-soft)">Rp</span>
                            {{-- FIX: numerals only, sama seperti input Jumlah Bayar --}}
                            <input type="text" inputmode="numeric" pattern="[0-9]*" name="late_fee" required
                                   @input="$el.value = $el.value.replace(/[^0-9]/g, '')"
                                   class="form-input w-full pl-9" placeholder="0">
                        </div>
                        @error('late_fee')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">
                            Catatan / Alasan <span class="font-normal" style="color: var(--text-soft)">(opsional)</span>
                        </label>
                        <input type="text" name="late_fee_note" maxlength="255"
                               class="form-input w-full text-sm"
                               placeholder="Cth: barang cacat produksi, keringanan diberikan">
                    </div>
                </div>
                <button type="submit" :disabled="isLoading" :class="isLoading ? 'opacity-75' : ''"
                        class="btn-primary w-full justify-center py-3">
                    <template x-if="isLoading"><span class="btn-spinner"></span></template>
                    <span x-text="isLoading ? 'Menyimpan...' : 'Simpan Nominal Denda'"></span>
                </button>
            </form>
        </div>
        @elseif($rental->needs_late_fee_payment)
        {{-- Langkah 2: denda sudah ditentukan, tinggal menunggu pembayaran
             (tombol "Bayar Denda" ada di banner merah di atas). --}}
        <div class="card p-6 text-center" style="border-top: 3px solid #C0392B; background: #FFFBFB">
            <i data-lucide="lock" class="w-6 h-6 mx-auto mb-2" style="color: #C0392B"></i>
            <p class="font-semibold text-sm" style="color: #C0392B">
                Denda Rp {{ number_format($rental->late_fee, 0, ',', '.') }} Belum Lunas
            </p>
            <p class="text-xs mt-1" style="color: var(--text-soft)">
                Formulir pengembalian barang akan terbuka otomatis setelah denda ini dibayar lunas.
                Gunakan tombol <strong>"Bayar Denda"</strong> di atas.
            </p>
            @if($rental->late_fee_note)
            <p class="text-xs mt-2 italic" style="color: var(--text-soft)">Catatan: {{ $rental->late_fee_note }}</p>
            @endif
        </div>
        @else
        {{-- Denda sudah ditentukan & lunas — info ringkas saja sebagai konteks --}}
        <div class="card p-4 flex items-center justify-between" style="border-top: 3px solid #16A34A; background: #F0FDF4">
            <div class="flex items-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4" style="color: #16A34A"></i>
                <span class="text-sm font-semibold" style="color: #15803D">
                    {{ $rental->late_fee > 0 ? 'Denda Rp ' . number_format($rental->late_fee, 0, ',', '.') . ' sudah lunas' : 'Tidak ada denda keterlambatan' }}
                </span>
            </div>
            <span class="text-xs" style="color: var(--text-soft)">{{ $feeData['late_days'] }} hari terlambat</span>
        </div>
        @endif
    @endif

    {{-- ─── FORM PENGEMBALIAN ───────────────────────────────────────── --}}
    @if($rental->can_be_returned)
    @php
        $returnItems = $rental->items->where('is_returned', false)->values();
        $guaranteeAvailable = $rental->guarantees->where('status', 'held')->count() > 0;
    @endphp
    <div x-data="returnForm({{ $returnItems->pluck('id') }}, {{ (float) $rental->subtotal }}, {{ (float) $rental->late_fee }}, {{ $returnItems->pluck('subtotal', 'id') }})" class="space-y-5">


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

                                <input type="text" name="items[{{ $index }}][notes]"
                       class="form-input text-xs w-full"
                       placeholder="Catatan kondisi barang (opsional)...">
            </div>
            @endforeach

            {{-- Ringkasan denda — denda ini sudah ditentukan & LUNAS dibayar
                 sebelum form ini bisa diakses (lihat blok "Tentukan/Bayar
                 Denda" di atas), jadi di sini murni informasi. --}}
            @if($rental->late_fee > 0)
            <div class="p-4 rounded-xl mb-5 flex items-center justify-between" style="background: linear-gradient(135deg,#F0FDF4,#DCFCE7); border: 1px solid #BBF7D0">
                <div class="flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-5 h-5" style="color:#15803D"></i>
                    <div>
                        <p class="text-xs font-semibold" style="color:#15803D">Denda Keterlambatan (Lunas)</p>
                        <p class="text-[11px]" style="color:#15803D">{{ $rental->overdue_days }} hari terlambat</p>
                    </div>
                </div>
                <span class="text-lg font-bold font-playfair" style="color:#15803D">
                    Rp {{ number_format($rental->late_fee, 0, ',', '.') }}
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

    {{-- Belum lunas (pembayaran sewa awal, KASUS NON-OVERDUE — untuk rental
         overdue, status "belum lunas" selalu berarti denda belum lunas dan
         sudah ditangani oleh blok "Tentukan/Bayar Denda" di atas). --}}
    @elseif($rental->rental_status === 'active' && $rental->payment_status !== 'paid')
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

    {{-- ===== MODAL PEMBAYARAN DENDA (pembayaran ke-2, khusus denda) ===== --}}
    <div x-show="showLateFeePayment"
         x-cloak
         @click.self="closeLateFeePayment()"
         @keydown.escape.window="closeLateFeePayment()"
         class="fixed inset-0 z-50 flex items-center justify-center"
         style="background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(2px);"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="modal-box w-full sm:max-w-md mx-0 sm:mx-4 p-0 rounded-t-2xl sm:rounded-2xl max-h-[90vh] overflow-y-auto"
             @click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-full sm:translate-y-4 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-full sm:translate-y-4 sm:scale-95">

            <div class="flex items-center justify-between p-4 border-b" style="border-color: var(--border)">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background: #FEE2E2">
                        <i data-lucide="alarm-clock" class="w-3.5 h-3.5" style="color: #C0392B"></i>
                    </div>
                    <h3 class="font-playfair font-bold text-sm" style="color: var(--text-dark)"
                        x-text="paymentContext === 'damage_fee' ? 'Bayar Kekurangan Tagihan' : 'Bayar Denda Keterlambatan'"></h3>
                </div>
                <button @click="closeLateFeePayment()"
                        class="w-7 h-7 rounded-lg flex items-center justify-center hover:bg-gray-100 transition-colors"
                        style="color: var(--text-soft)">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
            </div>

            <div class="p-4 space-y-3">

                <div class="rounded-xl p-3 relative overflow-hidden"
                     style="background: linear-gradient(135deg, #C0392B, #E74C3C);">
                    <p class="text-[10px] font-medium" style="color: rgba(255,255,255,0.75)"
                       x-text="paymentContext === 'damage_fee' ? 'Sisa Kekurangan Tagihan' : 'Sisa Denda Keterlambatan'"></p>
                    <p class="text-xl font-bold font-playfair" style="color: white">
                        Rp {{ number_format($rental->remaining_amount, 0, ',', '.') }}
                    </p>
                    <p class="text-[10px] mt-0.5" style="color: rgba(255,255,255,0.65)"
                       x-text="paymentContext === 'damage_fee' ? 'Barang belum dianggap dikembalikan sampai ini lunas.' : 'Ini adalah pembayaran terpisah dari pembayaran sewa sebelumnya.'"></p>
                </div>

                <form method="POST" action="{{ route('rentals.payment', $rental) }}" class="space-y-3"
                      enctype="multipart/form-data"
                      @submit="lateFeePaymentLoading = true">
                    @csrf
                    <input type="hidden" name="type" :value="paymentContext">

                    <div>
                        <label class="block text-xs font-semibold mb-1" style="color: var(--text-dark)">
                            Jumlah Bayar <span class="text-red-400">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold" style="color: var(--text-soft)">Rp</span>
                            <input type="text" inputmode="numeric" pattern="[0-9]*" name="amount" required
                                   value="{{ (int) $rental->remaining_amount }}"
                                   @input="$el.value = $el.value.replace(/[^0-9]/g, '')"
                                   class="form-input w-full pl-9 font-bold text-sm"
                                   style="color: #C0392B; padding-top: 0.5rem; padding-bottom: 0.5rem;">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-dark)">Metode Pembayaran</label>
                        <div class="grid grid-cols-3 gap-1.5">
                            @foreach([
                                'cash'     => ['Tunai',    'banknote'],
                                'transfer' => ['Transfer', 'building-2'],
                                'qris'     => ['QRIS',     'qr-code'],
                            ] as $val => [$label, $icon])
                            <label class="flex flex-col items-center gap-1 p-2.5 rounded-xl border-2 cursor-pointer transition-all has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50"
                                   style="border-color: var(--border)">
                                <input type="radio" name="method" value="{{ $val }}" x-model="lateFeeMethod"
                                       @change="lateFeeChannel = ''; lateFeeAccountNumber = ''"
                                       {{ $val === 'cash' ? 'checked' : '' }} class="sr-only">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: var(--bg-main)">
                                    <i data-lucide="{{ $icon }}" class="w-4 h-4" style="color: #C0392B"></i>
                                </div>
                                <span class="text-[10px] font-semibold" style="color: var(--text-dark)">{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div x-show="lateFeeMethod === 'transfer'" x-cloak x-transition>
                        <label class="block text-xs font-semibold mb-1" style="color: var(--text-dark)">
                            Bank Tujuan <span class="text-red-400">*</span>
                        </label>
                        <select name="payment_channel" x-model="lateFeeChannel"
                                :required="lateFeeMethod === 'transfer'"
                                :disabled="lateFeeMethod !== 'transfer'"
                                class="form-input w-full text-sm" style="padding-top: 0.5rem; padding-bottom: 0.5rem;">
                            <option value="">Pilih bank...</option>
                            <template x-for="bank in bankOptions" :key="bank">
                                <option :value="bank" x-text="bank"></option>
                            </template>
                        </select>
                    </div>

                    <div x-show="lateFeeMethod === 'transfer' && lateFeeChannel" x-cloak x-transition>
                        <label class="block text-xs font-semibold mb-1" style="color: var(--text-dark)">
                            Nomor Rekening Tujuan <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="account_number" x-model="lateFeeAccountNumber"
                               :required="lateFeeMethod === 'transfer' && lateFeeChannel"
                               :disabled="!(lateFeeMethod === 'transfer' && lateFeeChannel)"
                               inputmode="numeric" placeholder="Contoh: 1234567890"
                               class="form-input w-full text-sm" style="padding-top: 0.5rem; padding-bottom: 0.5rem;">
                    </div>

                    <div x-show="lateFeeMethod === 'qris'" x-cloak x-transition>
                        <label class="block text-xs font-semibold mb-1" style="color: var(--text-dark)">
                            QRIS via <span class="text-red-400">*</span>
                        </label>
                        <select name="payment_channel" x-model="lateFeeChannel"
                                :required="lateFeeMethod === 'qris'"
                                :disabled="lateFeeMethod !== 'qris'"
                                class="form-input w-full text-sm" style="padding-top: 0.5rem; padding-bottom: 0.5rem;">
                            <option value="">Pilih bank / e-wallet...</option>
                            <template x-for="opt in qrisOptions" :key="opt">
                                <option :value="opt" x-text="opt"></option>
                            </template>
                        </select>
                    </div>

                    <div x-show="lateFeeMethod === 'qris' && lateFeeChannel" x-cloak x-transition
                         class="flex flex-col items-center gap-2 p-3 rounded-xl" style="background: var(--bg-main)">
                        <img src="{{ route('rentals.qris-demo.qr', $rental) }}" alt="QR QRIS"
                             class="w-40 h-40 rounded-lg" style="background: white; padding: 8px; border: 1px solid var(--border)">
                        <p class="text-[11px] text-center" style="color: var(--text-soft)">
                            Minta customer scan QR ini dengan kamera HP / aplikasi <span x-text="lateFeeChannel"></span>
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold mb-1" style="color: var(--text-dark)">
                            Catatan
                            <span class="font-normal" style="color: var(--text-soft)">(opsional)</span>
                        </label>
                        <input type="text" name="reference_number"
                               class="form-input w-full text-sm"
                               style="padding-top: 0.5rem; padding-bottom: 0.5rem;"
                               placeholder="Contoh: minta diantar via ojek, ambil jam 18:00...">
                    </div>

                    <div class="flex gap-2 pt-1">
                        <button type="button" @click="closeLateFeePayment()"
                                class="btn-secondary flex-1 justify-center text-sm py-2.5">
                            Nanti Saja
                        </button>
                        <button type="submit"
                                data-no-loading
                                :disabled="lateFeePaymentLoading"
                                :class="lateFeePaymentLoading ? 'btn-loading' : ''"
                                class="btn-primary flex-1 justify-center text-sm py-2.5 font-semibold"
                                style="background: linear-gradient(135deg, #C0392B, #E74C3C)">
                            <template x-if="lateFeePaymentLoading">
                                <span class="btn-spinner"></span>
                            </template>
                            <template x-if="!lateFeePaymentLoading">
                                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                            </template>
                            <span x-text="lateFeePaymentLoading ? '\u00A0Memproses...' : 'Konfirmasi Bayar Denda'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function latePaymentModal(autoOpen, defaultContext) {
    return {
        showLateFeePayment: autoOpen,
        // FITUR BARU: modal ini sekarang dipakai untuk 2 konteks berbeda —
        // 'late_fee' (denda keterlambatan) dan 'damage_fee' (kekurangan tagihan
        // retur, mis. denda rusak/hilang yang dibebankan tunai).
        paymentContext: defaultContext || 'late_fee',
        lateFeePaymentLoading: false,
        lateFeeMethod: 'cash',
        lateFeeChannel: '',
        lateFeeAccountNumber: '',
        bankOptions: ['BCA', 'Mandiri', 'BNI', 'BRI', 'BSI', 'CIMB Niaga'],
        qrisOptions: ['BCA', 'Mandiri', 'BNI', 'BRI', 'BSI', 'SeaBank', 'GoPay', 'OVO', 'Dana'],
        openLateFeePayment(context) {
            this.paymentContext = context || 'late_fee';
            this.showLateFeePayment = true;
        },
        closeLateFeePayment() {
            this.showLateFeePayment = false;
        },
    }
}

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
        lateFeeNote: '',
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