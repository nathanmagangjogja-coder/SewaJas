@extends('layouts.app')

@section('title', 'Detail Penyewaan ' . $rental->invoice_number)
@section('page-title', 'Detail Penyewaan')
@section('subtitle', $rental->invoice_number)

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-4" x-data="rentalDetail()">

    @php
        $requirePaymentBlock = session('require_payment') && $rental->remaining_amount > 0;
        // FIX: sebelumnya default type/title HANYA mengikuti session flash
        // (hanya benar sesaat setelah redirect dari processReturn). Kalau
        // user refresh / balik lagi ke halaman ini nanti, session flash-nya
        // sudah hilang tapi kekurangan bayarnya masih ada — sekarang
        // fallback ke state asli rental (needs_return_payment) supaya modal
        // "Bayar" selalu menunjukkan konteks yang benar.
        // FIX BUG (penyebab 500 error): payments.type di database adalah ENUM
        // ['rental','deposit','late_fee','damage_fee','refund'] — HARUS
        // 'damage_fee', bukan 'damage', kalau tidak INSERT payment akan gagal
        // (SQLSTATE 1265) begitu form ini disubmit.
        $paymentFormType = session('payment_type', $rental->needs_return_payment ? 'damage_fee' : 'rental');
        $paymentFormTitle = match ($paymentFormType) {
            'damage_fee' => 'Bayar Kekurangan Tagihan',
            default => 'Input Pembayaran',
        };
    @endphp

    {{-- ===== ACTION BAR ===== --}}
        <div class="flex flex-col gap-2 sticky top-0 z-30 -mx-4 lg:-mx-6 px-4 lg:px-6 py-3 border-b"
         style="background: var(--bg-main); border-color: var(--border);">
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('rentals.index') }}" class="btn-secondary text-sm">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span class="hidden sm:inline">Kembali</span>
            </a>

            {{-- FIX: tombol "Bayar" ini KHUSUS untuk tagihan sewa awal (waiting/
                 active). Untuk rental overdue, "Bayar" lama selalu menampilkan
                 "Sisa Tagihan Rp 0" (membingungkan, karena sewa awal memang
                 sudah lunas) tanpa memberi jalan untuk membayar denda sama
                 sekali. Sekarang overdue punya tombol sendiri di bawah. --}}
            @if($rental->rental_status === 'waiting' || ($rental->rental_status === 'active' && $rental->remaining_amount > 0))
            <button @click="openPayment()" class="btn-primary text-sm">
                <i data-lucide="credit-card" class="w-4 h-4"></i>
                Bayar
            </button>
            @endif

            @can('cancel', $rental)
            @if(!in_array($rental->rental_status, ['active', 'overdue', 'returned', 'cancelled', 'menunggu_laundry', 'dalam_laundry', 'siap_disewakan']))
            <button @click="openCancel()" class="btn-secondary text-sm"
                    style="color: #DC2626; border-color: #FCA5A5">
                <i data-lucide="x-circle" class="w-4 h-4"></i>
                Batalkan
            </button>
            @endif
            @endcan

            @if($rental->can_be_returned)
            <button @click="openReturn()" class="btn-primary text-sm"
                    style="background: linear-gradient(135deg, #10B981, #059669); box-shadow: 0 2px 8px rgba(16,185,129,0.3)">
                <i data-lucide="package-check" class="w-4 h-4"></i>
                <span class="hidden xs:inline">Proses Pengembalian</span>
                <span class="xs:hidden">Kembali</span>
            </button>
            @endif

            @if($rental->needs_late_fee_confirmation)
            {{-- FITUR BARU: tombol khusus untuk menentukan nominal denda,
                 langsung di halaman ini (tidak perlu pindah ke halaman scan). --}}
            <button @click="openLateFeeSet()" type="button"
                    class="btn-primary text-sm" style="background: linear-gradient(135deg, #C0392B, #E74C3C)">
                <i data-lucide="banknote" class="w-4 h-4"></i>
                <span class="hidden xs:inline">Tentukan Denda</span>
                <span class="xs:hidden">Denda</span>
            </button>
            @elseif($rental->needs_late_fee_payment)
            {{-- FITUR BARU: tombol khusus untuk membayar denda yang sudah
                 ditentukan — beda dari tombol "Bayar" biasa (sewa awal). --}}
            <button @click="openLateFeePayment()" type="button"
                    class="btn-primary text-sm" style="background: linear-gradient(135deg, #C0392B, #E74C3C)">
                <i data-lucide="alarm-clock" class="w-4 h-4"></i>
                <span class="hidden xs:inline">Bayar Denda</span>
                <span class="xs:hidden">Denda</span>
            </button>
            @elseif($rental->needs_return_payment)
            {{-- FITUR BARU: kondisi barang sudah dicatat (mis. ada yang
                 rusak/hilang dibebankan tunai) tapi belum lunas — barang
                 SENGAJA belum dianggap "dikembalikan". Tombol ini membuka
                 modal pembayaran yang sama, dengan konteks "damage" (lihat
                 $requirePaymentBlock/$paymentFormType di atas). --}}
            <button @click="openPayment()" type="button"
                    class="btn-primary text-sm" style="background: linear-gradient(135deg, #C0392B, #E74C3C)">
                <i data-lucide="banknote" class="w-4 h-4"></i>
                <span class="hidden xs:inline">Bayar Kekurangan (Rp {{ number_format($rental->remaining_amount, 0, ',', '.') }})</span>
                <span class="xs:hidden">Bayar</span>
            </button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-2">
        <a href="{{ route('rentals.invoice', $rental) }}" class="btn-secondary text-sm justify-center sm:justify-start">
            <i data-lucide="printer" class="w-4 h-4"></i>
            <span class="hidden sm:inline">Print Invoice</span>
            <span class="sm:hidden">Invoice</span>
        </a>
        <a href="{{ route('rentals.thermal', $rental) }}" target="_blank" class="btn-secondary text-sm justify-center sm:justify-start">
            <i data-lucide="receipt" class="w-4 h-4"></i>
            <span class="hidden sm:inline">Nota Thermal</span>
            <span class="sm:hidden">Nota</span>
        </a>
        <a href="{{ route('rentals.pdf', $rental) }}" class="btn-secondary text-sm justify-center sm:justify-start">
            <i data-lucide="file-down" class="w-4 h-4"></i>
            PDF
        </a>
        <button type="button" @click="openWaPreview()"
           class="btn-secondary text-sm justify-center sm:justify-start" style="color: #25D366; border-color: #25D366">
            <i data-lucide="message-circle" class="w-4 h-4"></i>
            WA
        </button>
        @if(in_array($rental->rental_status, ['active', 'overdue']))
        <a href="{{ route('rentals.reminder', $rental) }}" target="_blank"
           class="btn-secondary text-sm justify-center sm:justify-start col-span-2 sm:col-span-1" style="color: #F59E0B; border-color: #F59E0B">
            <i data-lucide="bell" class="w-4 h-4"></i>
            Reminder
        </a>
        @endif
    </div>

    {{-- ===== OVERDUE BANNER ===== --}}
    @if($rental->rental_status === 'overdue')
    <div class="flex items-center gap-3 p-4 rounded-xl border" style="background: #FFF1F0; border-color: #FECACA;">
        <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0" style="color: #C0392B"></i>
        <div>
            <p class="font-semibold text-sm" style="color: #C0392B">Penyewaan Melewati Jatuh Tempo!</p>
            <p class="text-sm" style="color: #E74C3C">Sudah <strong>{{ $rental->overdue_days }} hari</strong> terlambat dari {{ $rental->return_due_date->format('d M Y') }}</p>
        </div>
    </div>
    @endif

    {{-- ===== MAIN GRID ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5">

        {{-- Left / Main --}}
        <div class="lg:col-span-2 space-y-4 sm:space-y-5">

            {{-- Transaction Info --}}
            <div class="card p-4 sm:p-6">
                                <div class="flex flex-col gap-3 mb-5 pb-4 border-b" style="border-color: var(--border)">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                        <div>
                            <h3 class="font-playfair font-semibold text-base" style="color: var(--text-dark)">Informasi Transaksi</h3>
                            <p class="font-mono text-sm mt-0.5" style="color: var(--primary)">{{ $rental->invoice_number }}</p>
                        </div>

                    </div>

                    <div class="flex gap-2 flex-wrap">
                        <span class="badge badge-{{ $rental->status_badge_color }} text-sm px-3 py-1.5 font-semibold">
                            {{ $rental->status_label }}
                        </span>
                        <span class="badge {{ match($rental->payment_status) { 'paid' => 'badge-green', 'partial' => 'badge-yellow', default => 'badge-red' } }} text-sm px-3 py-1.5 font-semibold">
                            {{ $rental->payment_status_label }}
                        </span>
                        @if($rental->discount > 0)
                        <span class="badge badge-purple text-sm px-3 py-1.5 font-semibold">
                            <i data-lucide="badge-percent" class="w-3.5 h-3.5"></i>
                            Diskon Rp {{ number_format($rental->discount, 0, ',', '.') }}
                        </span>
                        @endif
                        @if($rental->needs_return_payment)
                        {{-- FITUR BARU: kondisi barang sudah dicatat tapi tagihan
                             (biasanya denda rusak/hilang) belum lunas -> barang
                             SENGAJA belum dianggap selesai dikembalikan. --}}
                        <span class="badge text-sm px-3 py-1.5 font-semibold" style="background:#C0392B;color:#fff">
                            <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                            Belum Bisa Dikembalikan — Bayar Dulu
                        </span>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
                    <div>
                        <p class="text-xs" style="color: var(--text-soft)">Tanggal Sewa</p>
                        <p class="font-semibold text-sm mt-0.5" style="color: var(--text-dark)">{{ $rental->rental_date->format('d M Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs" style="color: var(--text-soft)">Jatuh Tempo</p>
                        <p class="font-semibold text-sm mt-0.5 {{ $rental->rental_status === 'overdue' ? 'text-red-500' : '' }}"
                           style="{{ $rental->rental_status !== 'overdue' ? 'color: var(--text-dark)' : '' }}">
                            {{ $rental->return_due_date->format('d M Y') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs" style="color: var(--text-soft)">Durasi</p>
                        <p class="font-semibold text-sm mt-0.5" style="color: var(--text-dark)">{{ $rental->duration_days }} Hari</p>
                    </div>

                                        @if($rental->package)
                    <div>
                        <p class="text-xs" style="color: var(--text-soft)">Paket Sewa</p>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <span class="text-sm font-semibold" style="color: var(--primary)">
                                {{ $rental->package->name }}
                            </span>
                        </div>
                    </div>
                    @endif

                    @if(in_array($rental->rental_status, ['active','overdue']) && $rental->live_late_days > 0)
                    <div>
                        <p class="text-xs" style="color: var(--text-soft)">Status Keterlambatan</p>
                        <p class="font-bold text-sm mt-0.5 text-red-500">
                            {{ $rental->live_late_days }} hari terlambat
                        </p>
                    </div>
                    @endif
                    @if($rental->actual_return_date)
                    <div>
                        <p class="text-xs" style="color: var(--text-soft)">Tgl. Kembali</p>
                        <p class="font-semibold text-sm mt-0.5" style="color: #10B981">{{ $rental->actual_return_date->format('d M Y') }}</p>
                    </div>
                    @endif
                    <div>
                        <p class="text-xs" style="color: var(--text-soft)">Dibuat Oleh</p>
                        <p class="font-semibold text-sm mt-0.5" style="color: var(--text-dark)">{{ $rental->createdBy->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs" style="color: var(--text-soft)">Cabang</p>
                        <p class="font-semibold text-sm mt-0.5" style="color: var(--text-dark)">{{ $rental->branch->name }}</p>
                    </div>
                </div>

                @if($rental->notes)
                <div class="mt-4 p-3 rounded-lg" style="background: var(--bg-main)">
                    <p class="text-xs" style="color: var(--text-soft)">Catatan</p>
                    <p class="text-sm mt-0.5" style="color: var(--text-dark)">{{ $rental->notes }}</p>
                </div>
                @endif
            </div>

            {{-- Rental Items Table --}}
            @if($requirePaymentBlock)
            <div class="card p-4">
                <p class="text-sm" style="color: var(--text-dark)">Pengembalian dicatat, namun ada sisa tagihan sebesar <strong>Rp {{ number_format($rental->remaining_amount, 0, ',', '.') }}</strong>.</p>
                <p class="text-xs mt-2" style="color: var(--text-soft)">Transaksi akan dikunci sampai pelunasan dilakukan. Silakan klik tombol Bayar untuk menyelesaikan tagihan.</p>
                <div class="mt-4">
                    <button type="button" @click="openPayment()" class="btn-primary">Bayar Sekarang</button>
                </div>
            </div>
            @else
            <div class="card overflow-hidden">
                <div class="p-4 sm:p-5 border-b" style="border-color: var(--border)">
                    <h3 class="font-playfair font-semibold text-base" style="color: var(--text-dark)">Barang Disewa</h3>
                </div>

                {{-- Desktop table --}}
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full elegant-table">
                        <thead>
                            <tr>
                                <th class="text-left">#</th>
                                <th class="text-left">Barang</th>
                                <th class="text-center">Qty</th>
                                <th class="text-right">Harga/Hari</th>
                                <th class="text-center">Hari</th>
                                <th class="text-right">Subtotal</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rental->items as $i => $item)
                            <tr>
                                <td class="text-xs" style="color: var(--text-soft)">{{ $i + 1 }}</td>
                                <td>
                                    <p class="font-medium" style="color: var(--text-dark)">{{ $item->product_name }}</p>
                                    <p class="text-xs mt-0.5" style="color: var(--text-soft)">
                                        {{ $item->product_size ? 'Uk: ' . $item->product_size : '' }}
                                        {{ $item->product_color ? '· ' . $item->product_color : '' }}
                                    </p>
                                </td>
                                <td class="text-center font-semibold" style="color: var(--text-dark)">{{ $item->quantity }}</td>
                                <td class="text-right whitespace-nowrap" style="color: var(--text-dark)">Rp {{ number_format($item->price_per_day, 0, ',', '.') }}</td>
                                <td class="text-center" style="color: var(--text-dark)">{{ $item->duration_days }}</td>
                                <td class="text-right font-bold whitespace-nowrap" style="color: var(--text-dark)">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    @if($item->is_returned)
                                    <span class="badge badge-green">Kembali</span>
                                    @if($item->return_condition === 'damaged')
                                    <span class="badge badge-yellow text-[10px] mt-1 block">Rusak</span>
                                    @elseif($item->return_condition === 'lost')
                                    <span class="badge badge-red text-[10px] mt-1 block">Hilang</span>
                                    @endif
                                    @if($item->damage_fee > 0)
                                    <p class="text-[10px] font-semibold mt-0.5" style="color:#C0392B">+Rp {{ number_format($item->damage_fee, 0, ',', '.') }}</p>
                                    @endif
                                    @elseif($item->return_condition && $rental->needs_return_payment)
                                    {{-- FITUR BARU: kondisi sudah dicatat (misal rusak/hilang) TAPI
                                         belum lunas -> barang SENGAJA belum ditandai "Kembali".
                                         Beda dari "Disewa" biasa supaya staf paham kenapa. --}}
                                    <span class="badge" style="background:#FFF1F0;color:#C0392B;border:1px solid #FECACA">
                                        <i data-lucide="lock" class="w-3 h-3 inline-block -mt-0.5 mr-0.5"></i>Menunggu Bayar
                                    </span>
                                    @if($item->return_condition === 'damaged')
                                    <span class="badge badge-yellow text-[10px] mt-1 block">Rusak</span>
                                    @elseif($item->return_condition === 'lost')
                                    <span class="badge badge-red text-[10px] mt-1 block">Hilang</span>
                                    @endif
                                    @if($item->damage_fee > 0)
                                    <p class="text-[10px] font-semibold mt-0.5" style="color:#C0392B">+Rp {{ number_format($item->damage_fee, 0, ',', '.') }}</p>
                                    @endif
                                    @else
                                    <span class="badge badge-blue">Disewa</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="background: var(--bg-main)">
                                <td colspan="5" class="text-right font-semibold py-3 px-4" style="color: var(--text-soft)">Subtotal</td>
                                <td class="text-right font-bold py-3 px-4" style="color: var(--text-dark)">Rp {{ number_format($rental->subtotal, 0, ',', '.') }}</td>
                                <td></td>
                            </tr>
                            @if($rental->discount > 0)
                            <tr style="background: var(--bg-main)">
                                <td colspan="5" class="text-right text-sm py-1 px-4" style="color: var(--text-soft)">
                                    Diskon{{ $rental->discount_name ? " ({$rental->discount_name})" : '' }}
                                </td>
                                <td class="text-right text-sm py-1 px-4" style="color: #E74C3C">-Rp {{ number_format($rental->discount, 0, ',', '.') }}</td>
                                <td></td>
                            </tr>
                            @endif
                            @if($rental->late_fee > 0)
                            <tr style="background: #FFF1F0">
                                <td colspan="5" class="text-right text-sm py-1 px-4" style="color: #C0392B">Denda Keterlambatan</td>
                                <td class="text-right text-sm py-1 px-4 font-semibold" style="color: #C0392B">+Rp {{ number_format($rental->late_fee, 0, ',', '.') }}</td>
                                <td></td>
                            </tr>
                            @endif
                            @if($rental->total_damage_fee > 0)
                            <tr style="background: #FFF1F0">
                                <td colspan="5" class="text-right text-sm py-1 px-4" style="color: #C0392B">Denda Rusak/Hilang</td>
                                <td class="text-right text-sm py-1 px-4 font-semibold" style="color: #C0392B">+Rp {{ number_format($rental->total_damage_fee, 0, ',', '.') }}</td>
                                <td></td>
                            </tr>
                            @endif
                            <tr style="background: var(--bg-main); border-top: 2px solid var(--primary)">
                                <td colspan="5" class="text-right font-bold py-3 px-4" style="color: var(--text-dark)">TOTAL</td>
                                <td class="text-right py-3 px-4">
                                    <span class="text-lg font-bold font-playfair" style="color: var(--primary)">Rp {{ number_format($rental->total_amount, 0, ',', '.') }}</span>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Mobile items list --}}
                <div class="sm:hidden divide-y" style="border-color: var(--border)">
                    @foreach($rental->items as $item)
                    <div class="p-4 flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                @if($item->is_returned)
                                <span class="badge badge-green text-[10px]">Kembali</span>
                                @if($item->return_condition === 'damaged')
                                <span class="badge badge-yellow text-[10px]">Rusak</span>
                                @elseif($item->return_condition === 'lost')
                                <span class="badge badge-red text-[10px]">Hilang</span>
                                @endif
                                @elseif($item->return_condition && $rental->needs_return_payment)
                                <span class="badge text-[10px]" style="background:#FFF1F0;color:#C0392B;border:1px solid #FECACA">Menunggu Bayar</span>
                                @if($item->return_condition === 'damaged')
                                <span class="badge badge-yellow text-[10px]">Rusak</span>
                                @elseif($item->return_condition === 'lost')
                                <span class="badge badge-red text-[10px]">Hilang</span>
                                @endif
                                @else
                                <span class="badge badge-blue text-[10px]">Disewa</span>
                                @endif
                            </div>
                            @if($item->damage_fee > 0)
                            <p class="text-[11px] font-semibold" style="color:#C0392B">Denda: +Rp {{ number_format($item->damage_fee, 0, ',', '.') }}</p>
                            @endif
                            <p class="font-medium text-sm" style="color: var(--text-dark)">{{ $item->product_name }}</p>
                            <p class="text-xs mt-0.5" style="color: var(--text-soft)">
                                {{ $item->product_size ? 'Uk: ' . $item->product_size : '' }}
                                {{ $item->product_color ? '· ' . $item->product_color : '' }}
                            </p>
                            <p class="text-xs mt-1" style="color: var(--text-soft)">
                                {{ $item->quantity }} × Rp {{ number_format($item->price_per_day, 0, ',', '.') }} × {{ $item->duration_days }} hari
                            </p>
                        </div>
                        <p class="font-bold text-sm flex-shrink-0" style="color: var(--text-dark)">
                            Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                        </p>
                    </div>
                    @endforeach

                    {{-- Mobile totals --}}
                    <div class="p-4 space-y-1.5" style="background: var(--bg-main)">
                        <div class="flex justify-between text-sm">
                            <span style="color: var(--text-soft)">Subtotal</span>
                            <span style="color: var(--text-dark)">Rp {{ number_format($rental->subtotal, 0, ',', '.') }}</span>
                        </div>
                        @if($rental->discount > 0)
                        <div class="flex justify-between text-sm">
                            <span style="color: var(--text-soft)">Diskon{{ $rental->discount_name ? " ({$rental->discount_name})" : '' }}</span>
                            <span style="color: #E74C3C">-Rp {{ number_format($rental->discount, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        @if($rental->late_fee > 0)
                        <div class="flex justify-between text-sm">
                            <span style="color: #C0392B">Denda Telat</span>
                            <span style="color: #C0392B">+Rp {{ number_format($rental->late_fee, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        @if($rental->total_damage_fee > 0)
                        <div class="flex justify-between text-sm">
                            <span style="color: #C0392B">Denda Rusak/Hilang</span>
                            <span style="color: #C0392B">+Rp {{ number_format($rental->total_damage_fee, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between pt-2 border-t" style="border-color: var(--border)">
                            <span class="font-bold" style="color: var(--text-dark)">TOTAL</span>
                            <span class="font-bold font-playfair" style="color: var(--primary)">
                                Rp {{ number_format($rental->total_amount, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Diskon Manual (BARU) — hanya tampil kalau ada diskon manual dari proses retur --}}
            @if($rental->has_manual_discount)
            <div class="card p-4 sm:p-6" style="border-top: 3px solid #0EA5E9">
                <h3 class="font-playfair font-semibold text-base mb-4 flex items-center gap-2" style="color: var(--text-dark)">
                    <i data-lucide="badge-percent" class="w-4 h-4" style="color: #0EA5E9"></i>
                    Diskon Manual
                </h3>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs" style="color: var(--text-soft)">Nama Diskon</p>
                        <p class="font-semibold text-sm mt-0.5" style="color: var(--text-dark)">{{ $rental->discount_name ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs" style="color: var(--text-soft)">Nilai Diskon</p>
                        <p class="font-semibold text-sm mt-0.5" style="color: var(--text-dark)">
                            @if($rental->discount_type === 'percent')
                                {{ rtrim(rtrim(number_format((float) $rental->discount_value, 2, ',', '.'), '0'), ',') }}%
                                <span class="text-xs" style="color: var(--text-soft)">(= Rp {{ number_format($rental->discount, 0, ',', '.') }})</span>
                            @else
                                Rp {{ number_format($rental->discount, 0, ',', '.') }}
                            @endif
                        </p>
                    </div>
                    @if($rental->discount_description)
                    <div class="sm:col-span-2">
                        <p class="text-xs" style="color: var(--text-soft)">Deskripsi</p>
                        <p class="text-sm mt-0.5" style="color: var(--text-dark)">{{ $rental->discount_description }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

                @endif
                {{-- Payment History --}}
            @unless($requirePaymentBlock)
            @if($rental->payments->count() > 0)
            <div class="card overflow-hidden">
                <div class="p-4 sm:p-5 border-b" style="border-color: var(--border)">
                    <h3 class="font-playfair font-semibold text-base" style="color: var(--text-dark)">Riwayat Pembayaran</h3>
                </div>

                {{-- Desktop --}}
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full elegant-table">
                        <thead>
                            <tr>
                                <th class="text-left">No. Pembayaran</th>
                                <th class="text-left">Metode</th>
                                <th class="text-left">Jenis</th>
                                <th class="text-left">Waktu</th>
                                <th class="text-right">Jumlah</th>
                                <th class="text-left">Diterima</th>
                                <th class="text-left">Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rental->payments as $payment)
                            <tr>
                                <td class="font-mono text-xs font-semibold" style="color: var(--primary)">{{ $payment->payment_number }}</td>
                                <td>
                                    <span class="badge badge-blue">{{ $payment->method_label }}</span>
                                    @if($payment->channel_label)
                                        <div class="text-[10px] mt-0.5" style="color: var(--text-soft)">{{ $payment->channel_label }}</div>
                                    @endif
                                </td>
                                <td style="color: var(--text-soft)">{{ ucfirst(str_replace('_', ' ', $payment->type)) }}</td>
                                <td class="text-xs whitespace-nowrap" style="color: var(--text-soft)">{{ $payment->paid_at->format('d M Y H:i') }}</td>
                                <td class="text-right font-bold whitespace-nowrap" style="color: #15803D">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                <td class="text-xs" style="color: var(--text-soft)">{{ $payment->receivedBy->name }}</td>
                                <td class="text-xs" style="color: var(--text-soft)">
                                    @if($payment->notes)
                                        {{ $payment->notes }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="background: var(--bg-main)">
                                <td colspan="5" class="text-right font-bold py-3 px-4" style="color: var(--text-soft)">Total Dibayar</td>
                                <td class="text-right py-3 px-4 font-bold" style="color: #15803D; font-size: 1rem">Rp {{ number_format($rental->paid_amount, 0, ',', '.') }}</td>
                                <td></td>
                            </tr>
                            @if($rental->remaining_amount > 0)
                            <tr style="background: #FFF1F0">
                                <td colspan="5" class="text-right font-bold py-2 px-4" style="color: #C0392B">Sisa Tagihan</td>
                                <td class="text-right py-2 px-4 font-bold" style="color: #C0392B">Rp {{ number_format($rental->remaining_amount, 0, ',', '.') }}</td>
                                <td></td>
                            </tr>
                            @endif
                        </tfoot>
                    </table>
                </div>

                {{-- Mobile payments --}}
                <div class="sm:hidden divide-y" style="border-color: var(--border)">
                    @foreach($rental->payments as $payment)
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-2 mb-1">
                            <span class="font-mono text-xs font-semibold" style="color: var(--primary)">{{ $payment->payment_number }}</span>
                            <span class="font-bold text-sm" style="color: #15803D">Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="badge badge-blue text-[10px]">{{ $payment->method_label }}</span>
                            @if($payment->channel_label)
                                <span class="text-[10px] font-semibold" style="color: var(--primary)">{{ $payment->channel_label }}</span>
                            @endif
                            <span class="text-xs" style="color: var(--text-soft)">{{ $payment->paid_at->format('d M Y H:i') }}</span>
                        </div>
                        <div class="text-xs mt-0.5" style="color: var(--text-soft)">· {{ $payment->receivedBy->name }}</div>
                        @if($payment->notes)
                        <div class="text-xs mt-1" style="color: var(--text-soft)">Catatan: {{ $payment->notes }}</div>
                        @endif
                    </div>
                    @endforeach
                    <div class="p-4" style="background: var(--bg-main)">
                        <div class="flex justify-between text-sm font-bold">
                            <span style="color: var(--text-soft)">Total Dibayar</span>
                            <span style="color: #15803D">Rp {{ number_format($rental->paid_amount, 0, ',', '.') }}</span>
                        </div>
                        @if($rental->remaining_amount > 0)
                        <div class="flex justify-between text-sm font-bold mt-1">
                            <span style="color: #C0392B">Sisa Tagihan</span>
                            <span style="color: #C0392B">Rp {{ number_format($rental->remaining_amount, 0, ',', '.') }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
            @endunless
        </div>

        {{-- ===== RIGHT COLUMN ===== --}}
        <div class="space-y-4 sm:space-y-5">

            {{-- Customer Card --}}
            <div class="card p-4 sm:p-5">
                <h3 class="font-playfair font-semibold text-sm mb-4" style="color: var(--text-dark)">Data Customer</h3>
                <div class="flex items-center gap-3 mb-4">
                    <img src="{{ $rental->customer->photo_url }}" alt="{{ $rental->customer->name }}"
                         class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl object-cover flex-shrink-0">
                    <div class="min-w-0">
                        <p class="font-semibold truncate" style="color: var(--text-dark)">{{ $rental->customer->name }}</p>
                        <p class="text-sm" style="color: var(--text-soft)">{{ $rental->customer->phone }}</p>
                        @if($rental->customer->is_blacklisted)
                        <span class="badge badge-red mt-1">Blacklist</span>
                        @endif
                    </div>
                </div>
                @if($rental->customer->address)
                <div class="mb-3">
                    <p class="text-xs" style="color: var(--text-soft)">Alamat</p>
                    <p class="text-sm mt-0.5" style="color: var(--text-dark)">{{ $rental->customer->address }}</p>
                </div>
                @endif
                <a href="{{ route('customers.show', $rental->customer) }}" class="btn-secondary w-full justify-center text-sm">
                    <i data-lucide="user" class="w-3.5 h-3.5"></i>
                    Lihat Profil Customer
                </a>
            </div>

                        @if($rental->customer->id_photo)
            <div class="card p-4 sm:p-5">
                <h3 class="font-playfair font-semibold text-sm mb-3" style="color: var(--text-dark)">Foto jaminan</h3>
                <div class="relative">
                    <img src="{{ asset('storage/' . $rental->customer->id_photo) }}" alt="Foto Jaminan"
                         class="w-full rounded-xl object-cover cursor-zoom-in" style="max-height: 220px;"
                         @click="openKtpViewer('{{ asset('storage/' . $rental->customer->id_photo) }}')">
                    <button type="button"
                            class="absolute bottom-2 right-2 px-2 py-1 rounded-lg text-[10px] font-semibold flex items-center gap-1"
                            style="background: rgba(0,0,0,0.55); color: #fff"
                            @click="openKtpViewer('{{ asset('storage/' . $rental->customer->id_photo) }}')">
                        <i data-lucide="zoom-in" class="w-3 h-3"></i> Lihat Detail
                    </button>
                </div>
                @if($rental->customer->id_number)
                <p class="text-xs font-mono mt-2 text-center" style="color: var(--text-soft)">{{ $rental->customer->id_number }}</p>
                @endif
            </div>
            @endif

            {{-- Guarantee --}}
            @unless($requirePaymentBlock)
            @if($rental->guarantees->count() > 0)
            <div class="card p-4 sm:p-5">
                <h3 class="font-playfair font-semibold text-sm mb-4" style="color: var(--text-dark)">Jaminan</h3>
                @foreach($rental->guarantees as $g)
                <div class="p-3 rounded-xl mb-3 last:mb-0" style="background: var(--bg-main)">
                    <div class="flex justify-between items-start mb-2">
                        <span class="badge badge-gold">{{ $g->type_label }}</span>
                        <span class="badge {{ match($g->status) { 'held' => 'badge-yellow', 'returned' => 'badge-green', 'forfeited' => 'badge-red', default => 'badge-gray' } }}">
                            {{ match($g->status) { 'held' => 'Ditahan', 'returned' => 'Dikembalikan', 'forfeited' => 'Disita', default => $g->status } }}
                        </span>
                    </div>
                    @if($g->id_number)
                    <p class="text-xs" style="color: var(--text-soft)">Nomor: <span class="font-semibold" style="color: var(--text-dark)">{{ $g->id_number }}</span></p>
                    @endif
                    @if($g->id_name)
                    <p class="text-xs mt-1" style="color: var(--text-soft)">Nama: <span class="font-semibold" style="color: var(--text-dark)">{{ $g->id_name }}</span></p>
                    @endif
                    @if($g->deposit_amount > 0)
                    <p class="text-xs mt-1" style="color: var(--text-soft)">Deposit: <span class="font-semibold" style="color: var(--text-dark)">Rp {{ number_format($g->deposit_amount, 0, ',', '.') }}</span></p>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
            @endunless

            {{-- QR Code --}}
            @if($rental->qr_code)
            <div class="card p-4 sm:p-5 text-center">
                <h3 class="font-playfair font-semibold text-sm mb-3" style="color: var(--text-dark)">QR Code Transaksi</h3>
                <img src="{{ asset('storage/' . $rental->qr_code) }}" alt="QR"
                     class="w-32 h-32 sm:w-36 sm:h-36 mx-auto rounded-xl" style="border: 4px solid var(--secondary)">
                <p class="text-xs mt-2" style="color: var(--text-soft)">Scan untuk verifikasi atau pengembalian</p>
                <p class="font-mono text-xs font-bold mt-1" style="color: var(--primary)">{{ $rental->invoice_number }}</p>
                <a href="{{ route('rentals.qr.download', $rental) }}" class="btn-secondary w-full justify-center text-sm mt-3">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                    Download QR Code
                </a>
            </div>
            @endif
        </div>
    </div>

 {{-- ===== PAYMENT MODAL ===== --}}
<div x-show="showPaymentModal"
     x-cloak
     @click.self="closePayment()"
     @keydown.escape.window="closePayment()"
     class="fixed inset-0 z-50 flex items-start justify-center px-4 pt-16"
     style="background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="modal-box w-full max-w-sm rounded-2xl"
         @click.stop
         style="max-height: 90vh; overflow-y: auto;"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

        {{-- Header --}}
        <div class="flex items-center justify-between p-4 border-b" style="border-color: var(--border)">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background: #FEF3C7">
                    <i data-lucide="credit-card" class="w-3.5 h-3.5" style="color: var(--primary)"></i>
                </div>
                <h3 class="font-playfair font-bold text-sm" style="color: var(--text-dark)">{{ $paymentFormTitle }}</h3>
            </div>
            <button @click="closePayment()"
                    class="w-7 h-7 rounded-lg flex items-center justify-center hover:bg-gray-100 transition-colors"
                    style="color: var(--text-soft)">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>
        </div>

        <div class="p-4 space-y-3">

            {{-- Tagihan Card --}}
            <div class="rounded-xl p-3 relative overflow-hidden"
                 style="background: linear-gradient(135deg, var(--primary), #B8860B);">
                <div class="absolute top-0 right-0 w-16 h-16 rounded-full opacity-10"
                     style="background: white; transform: translate(30%,-30%)"></div>
                <p class="text-[10px] font-medium" style="color: rgba(255,255,255,0.75)">Sisa Tagihan</p>
                <p class="text-xl font-bold font-playfair" style="color: white">
                    Rp {{ number_format($rental->remaining_amount, 0, ',', '.') }}
                </p>
                <p class="text-[10px] mt-0.5" style="color: rgba(255,255,255,0.65)">
                    Total: Rp {{ number_format($rental->total_amount, 0, ',', '.') }}
                </p>
            </div>

            <form method="POST" action="{{ route('rentals.payment', $rental) }}" class="space-y-3"
                  @submit="paymentLoading = true">
                @csrf
                <input type="hidden" name="type" value="{{ $paymentFormType }}">

                {{-- Jumlah Bayar --}}
                <div>
                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-dark)">
                        Jumlah Bayar <span class="text-red-400">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold" style="color: var(--text-soft)">Rp</span>
                        {{-- FIX: numerals only. type="number" ternyata masih bisa
                             kemasukan huruf lewat paste/beberapa browser. Dipakai
                             type="text" + inputmode="numeric" + strip karakter
                             non-digit on-input, supaya benar-benar tidak bisa
                             diisi huruf sama sekali. --}}
                        <input type="text" inputmode="numeric" pattern="[0-9]*" name="amount" required
                               value="{{ (int) $rental->remaining_amount }}"
                               @input="$el.value = $el.value.replace(/[^0-9]/g, '')"
                               class="form-input w-full pl-9 font-bold text-sm"
                               style="color: var(--primary); padding-top: 0.5rem; padding-bottom: 0.5rem;">
                    </div>
                </div>

                                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-dark)">Metode Pembayaran</label>
                    <div class="grid grid-cols-3 gap-1.5">
                        @php $paymentSettings = \App\Models\Setting::payment(); @endphp
                        @foreach([
                            'cash'     => ['Tunai',    'banknote'],
                            'transfer' => ['Transfer', 'building-2'],
                            'qris'     => ['QRIS',     'qr-code'],
                            'other' => ['Lainnya',  'credit-card']
                        ] as $val => [$label, $icon])
                        @continue(!in_array($val, $paymentSettings['payment_methods_enabled']))
                        <label class="flex flex-col items-center gap-1 p-2.5 rounded-xl border-2 cursor-pointer transition-all has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50"
                               style="border-color: var(--border)">
                            <input type="radio" name="method" value="{{ $val }}" x-model="paymentMethod"
                                   @change="resetPaymentMethod()"
                                   {{ $val === 'cash' ? 'checked' : '' }} class="sr-only">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: var(--bg-main)">
                                <i data-lucide="{{ $icon }}" class="w-4 h-4" style="color: var(--primary)"></i>
                            </div>
                            <span class="text-[10px] font-semibold" style="color: var(--text-dark)">{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                                <div x-show="paymentMethod === 'transfer'" x-cloak x-transition>
                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-dark)">
                        Bank Tujuan <span class="text-red-400">*</span>
                    </label>
                    <select name="payment_channel" x-model="paymentChannel"
                            @change="autofillBankAccount()"
                            :required="paymentMethod === 'transfer'"
                            class="form-input w-full text-sm" style="padding-top: 0.5rem; padding-bottom: 0.5rem;">
                        <option value="">Pilih bank...</option>
                        <template x-for="bank in bankOptions" :key="bank">
                            <option :value="bank" x-text="bank"></option>
                        </template>
                    </select>
                </div>

                                <div x-show="paymentMethod === 'transfer' && paymentChannel" x-cloak x-transition>
                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-dark)">
                        Nomor Rekening Tujuan <span class="text-red-400">*</span>
                    </label>
                    <input type="text" name="account_number" x-model="paymentAccountNumber"
                           :required="paymentMethod === 'transfer' && paymentChannel"
                           inputmode="numeric" placeholder="Contoh: 1234567890"
                           class="form-input w-full text-sm" style="padding-top: 0.5rem; padding-bottom: 0.5rem;">
                    <p class="text-[10px] mt-1" style="color: var(--text-soft)">
                        Nomor ini akan tersensor otomatis di nota (hanya 4 digit terakhir tampil).
                    </p>
                </div>

                                <div x-show="paymentMethod === 'qris'" x-cloak x-transition>
                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-dark)">
                        QRIS via <span class="text-red-400">*</span>
                    </label>
                    <select name="payment_channel" x-model="paymentChannel"
                            :required="paymentMethod === 'qris'"
                            class="form-input w-full text-sm" style="padding-top: 0.5rem; padding-bottom: 0.5rem;">
                        <option value="">Pilih bank / e-wallet...</option>
                        <template x-for="opt in qrisOptions" :key="opt">
                            <option :value="opt" x-text="opt"></option>
                        </template>
                    </select>
                </div>

                                <div x-show="paymentMethod === 'qris' && paymentChannel" x-cloak x-transition
                     class="flex flex-col items-center gap-2 p-3 rounded-xl" style="background: var(--bg-main)">
                    @if($paymentSettings['payment_qris_image_url'])
                        <img src="{{ $paymentSettings['payment_qris_image_url'] }}" alt="QRIS"
                             class="w-40 h-40 rounded-lg object-contain" style="background: white; padding: 8px; border: 1px solid var(--border)">
                        <p class="text-[11px] text-center" style="color: var(--text-soft)">
                            Minta customer scan QR ini dengan kamera HP / aplikasi <span x-text="paymentChannel"></span>
                        </p>
                    @else
                        <p class="text-[11px] text-center" style="color: var(--text-soft)">
                            Foto QRIS belum diatur. Superadmin bisa mengunggahnya di menu
                            <strong>Metode Pembayaran</strong>.
                        </p>
                    @endif
                </div>

                {{-- Catatan umum (sebelumnya: No. Referensi) --}}
                <div>
                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-dark)">
                        Catatan pelanggan
                    </label>
                    <input type="text" name="reference_number"
                           class="form-input w-full text-sm"
                           style="padding-top: 0.5rem; padding-bottom: 0.5rem;"
                           placeholder="Contoh: minta diantar via ojek, ambil jam 18:00...">
                </div>

                <div x-show="paymentMethod === 'other'" x-cloak x-transition>
                    <label class="block text-xs font-semibold mb-1" style="color:var(--text-dark)">Jenis Pembayaran <span class="text-red-400">*</span></label>
                    <select name="otherOptions" x-model="otherOptions" :required="paymentMethod==='other'" class="form-input w-full text-sm" style="padding-top:.5rem;padding-bottom:.5rem;">
                        <option value="">Pilih jenis pembayaran...</option>
                        <option value="card">Kartu Kredit / Debit</option>
                        <option value="guarantee">Jaminan Barang</option>
                    </select>
                </div>

                <div x-show="paymentMethod==='other' && otherOptions==='card'" x-cloak x-transition class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color:var(--text-dark)">Jenis Kartu <span class="text-red-400">*</span></label>
                            <select name="card_type" class="form-input w-full text-sm" style="padding-top:.5rem;padding-bottom:.5rem;">
                                <option value="">Pilih...</option>
                                <option value="credit">Kartu Kredit</option>
                                <option value="debit">Kartu Debit</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color:var(--text-dark)">Bank <span class="text-red-400">*</span></label>
                            <select name="card_bank" class="form-input w-full text-sm" style="padding-top:.5rem;padding-bottom:.5rem;">
                                <option value="">Pilih Bank...</option>
                                <template x-for="bank in bankOptions" :key="bank"><option :value="bank" x-text="bank"></option></template>
                            </select>
                        </div>
                    </div>
                </div>

                <div x-show="paymentMethod==='other' && otherOptions==='guarantee'" x-cloak x-transition class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1" style="color:var(--text-dark)">Nama Barang <span class="text-red-400">*</span></label>
                        <input type="text" name="guarantee_name" class="form-input w-full text-sm" placeholder="Nama barang" style="padding-top:.5rem;padding-bottom:.5rem;">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color:var(--text-dark)">Merk</label>
                            <input type="text" name="guarantee_brand" class="form-input w-full text-sm" placeholder="Merk barang" style="padding-top:.5rem;padding-bottom:.5rem;">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color:var(--text-dark)">Kondisi</label>
                            <select name="guarantee_condition" class="form-input w-full text-sm" style="padding-top:.5rem;padding-bottom:.5rem;">
                                <option value="">Pilih...</option>
                                <option>Baru</option>
                                <option>Sangat Baik</option>
                                <option>Baik</option>
                                <option>Cukup</option>
                                <option>Rusak Ringan</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color:var(--text-dark)">Estimasi Harga</label>
                            <input type="number" name="guarantee_value" class="form-input w-full text-sm" placeholder="0" style="padding-top:.5rem;padding-bottom:.5rem;">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1" style="color:var(--text-dark)">Nomor Seri / IMEI</label>
                        <input type="text" name="guarantee_serial" class="form-input w-full text-sm" placeholder="Opsional jika barang elektronik" style="padding-top:.5rem;padding-bottom:.5rem;">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1" style="color:var(--text-dark)">Upload Foto Barang</label>
                        <input type="file" name="guarantee_photos[]" multiple accept="image/*" class="form-input w-full text-sm" style="padding-top:.45rem;padding-bottom:.45rem;">
                        <p class="text-[10px] mt-1" style="color:var(--text-soft)">Dapat memilih beberapa foto sekaligus.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1" style="color:var(--text-dark)">Catatan Admin</label>
                        <textarea name="guarantee_note" rows="3" class="form-input w-full text-sm" placeholder="Catatan hasil pengecekan barang..."></textarea>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex gap-2 pt-1">
                    <button type="button" @click="closePayment()"
                            class="btn-secondary flex-1 justify-center text-sm py-2.5">
                        Batal
                    </button>
                    <button type="submit"
                            data-no-loading
                            :disabled="paymentLoading"
                            :class="paymentLoading ? 'btn-loading' : ''"
                            class="btn-primary flex-1 justify-center text-sm py-2.5 font-semibold">
                        <template x-if="paymentLoading">
                            <span class="btn-spinner"></span>
                        </template>
                        <template x-if="!paymentLoading">
                            <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                        </template>
                        <span x-text="paymentLoading ? '\u00A0Memproses...' : 'Konfirmasi'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

    {{-- ===== MODAL: TENTUKAN DENDA (langkah 1 dari 2, dipindah dari halaman
         scan agar bisa langsung dari sini juga) ===== --}}
    <div x-show="showLateFeeSetModal"
         x-cloak
         @click.self="closeLateFeeSet()"
         @keydown.escape.window="closeLateFeeSet()"
         class="fixed inset-0 z-50 flex items-start justify-center px-4 pt-16"
         style="background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="modal-box w-full max-w-sm rounded-2xl" @click.stop
             style="max-height: 90vh; overflow-y: auto;"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between p-4 border-b" style="border-color: var(--border)">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background: #FEE2E2">
                        <i data-lucide="banknote" class="w-3.5 h-3.5" style="color: #C0392B"></i>
                    </div>
                    <h3 class="font-playfair font-bold text-sm" style="color: var(--text-dark)">Tentukan Denda Keterlambatan</h3>
                </div>
                <button @click="closeLateFeeSet()"
                        class="w-7 h-7 rounded-lg flex items-center justify-center hover:bg-gray-100 transition-colors"
                        style="color: var(--text-soft)">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
            </div>
            <div class="p-4 space-y-3">
                <p class="text-xs" style="color: var(--text-soft)">
                    Rental ini terlambat <strong>{{ $rental->live_late_days }} hari</strong>. Tentukan nominal denda
                    sesuai kebijakan toko — barang <strong>baru bisa diproses pengembalian</strong> setelah denda
                    ini ditentukan &amp; dibayar lunas. Isi <strong>0</strong> kalau memang tidak ada denda.
                </p>
                <form method="POST" action="{{ route('rentals.late-fee.set', $rental) }}" class="space-y-3"
                      @submit="lateFeeSetLoading = true">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold mb-1" style="color: var(--text-dark)">
                            Nominal Denda <span class="text-red-400">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold" style="color: var(--text-soft)">Rp</span>
                            <input type="text" inputmode="numeric" pattern="[0-9]*" name="late_fee" required
                                   @input="$el.value = $el.value.replace(/[^0-9]/g, '')"
                                   class="form-input w-full pl-9 font-bold text-sm" placeholder="0"
                                   style="color: #C0392B; padding-top: 0.5rem; padding-bottom: 0.5rem;">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1" style="color: var(--text-dark)">
                            Catatan / Alasan <span class="font-normal" style="color: var(--text-soft)">(opsional)</span>
                        </label>
                        <input type="text" name="late_fee_note" maxlength="255"
                               class="form-input w-full text-sm" style="padding-top: 0.5rem; padding-bottom: 0.5rem;"
                               placeholder="Cth: barang cacat produksi, keringanan diberikan">
                    </div>
                    <div class="flex gap-2 pt-1">
                        <button type="button" @click="closeLateFeeSet()" class="btn-secondary flex-1 justify-center text-sm py-2.5">Batal</button>
                        <button type="submit" :disabled="lateFeeSetLoading" :class="lateFeeSetLoading ? 'btn-loading' : ''"
                                class="btn-primary flex-1 justify-center text-sm py-2.5 font-semibold"
                                style="background: linear-gradient(135deg, #C0392B, #E74C3C)">
                            <template x-if="lateFeeSetLoading"><span class="btn-spinner"></span></template>
                            <span x-text="lateFeeSetLoading ? '\u00A0Menyimpan...' : 'Simpan Nominal Denda'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ===== MODAL: BAYAR DENDA (langkah 2 dari 2) ===== --}}
    <div x-show="showLateFeePaymentModal"
         x-cloak
         @click.self="closeLateFeePayment()"
         @keydown.escape.window="closeLateFeePayment()"
         class="fixed inset-0 z-50 flex items-start justify-center px-4 pt-16"
         style="background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="modal-box w-full max-w-sm rounded-2xl" @click.stop
             style="max-height: 90vh; overflow-y: auto;"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between p-4 border-b" style="border-color: var(--border)">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background: #FEE2E2">
                        <i data-lucide="alarm-clock" class="w-3.5 h-3.5" style="color: #C0392B"></i>
                    </div>
                    <h3 class="font-playfair font-bold text-sm" style="color: var(--text-dark)">Bayar Denda Keterlambatan</h3>
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
                    <p class="text-[10px] font-medium" style="color: rgba(255,255,255,0.75)">Sisa Denda Keterlambatan</p>
                    <p class="text-xl font-bold font-playfair" style="color: white">
                        Rp {{ number_format($rental->remaining_amount, 0, ',', '.') }}
                    </p>
                    <p class="text-[10px] mt-0.5" style="color: rgba(255,255,255,0.65)">
                        Ini adalah pembayaran terpisah dari pembayaran sewa sebelumnya.
                    </p>
                </div>

                <form method="POST" action="{{ route('rentals.payment', $rental) }}" class="space-y-3"
                      @submit="lateFeePaymentLoading = true">
                    @csrf
                    <input type="hidden" name="type" value="late_fee">

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
                            @continue(!in_array($val, $paymentSettings['payment_methods_enabled']))
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
                                @change="autofillLateFeeBankAccount()"
                                :required="lateFeeMethod === 'transfer'"
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
                               inputmode="numeric" placeholder="Contoh: 1234567890"
                               class="form-input w-full text-sm" style="padding-top: 0.5rem; padding-bottom: 0.5rem;">
                    </div>

                    <div x-show="lateFeeMethod === 'qris'" x-cloak x-transition>
                        <label class="block text-xs font-semibold mb-1" style="color: var(--text-dark)">
                            QRIS via <span class="text-red-400">*</span>
                        </label>
                        <select name="payment_channel" x-model="lateFeeChannel"
                                :required="lateFeeMethod === 'qris'"
                                class="form-input w-full text-sm" style="padding-top: 0.5rem; padding-bottom: 0.5rem;">
                            <option value="">Pilih bank / e-wallet...</option>
                            <template x-for="opt in qrisOptions" :key="opt">
                                <option :value="opt" x-text="opt"></option>
                            </template>
                        </select>
                    </div>

                    <div x-show="lateFeeMethod === 'qris' && lateFeeChannel" x-cloak x-transition
                         class="flex flex-col items-center gap-2 p-3 rounded-xl" style="background: var(--bg-main)">
                        @if($paymentSettings['payment_qris_image_url'])
                            <img src="{{ $paymentSettings['payment_qris_image_url'] }}" alt="QRIS"
                                 class="w-40 h-40 rounded-lg object-contain" style="background: white; padding: 8px; border: 1px solid var(--border)">
                            <p class="text-[11px] text-center" style="color: var(--text-soft)">
                                Minta customer scan QR ini dengan kamera HP / aplikasi <span x-text="lateFeeChannel"></span>
                            </p>
                        @else
                            <p class="text-[11px] text-center" style="color: var(--text-soft)">
                                Foto QRIS belum diatur. Superadmin bisa mengunggahnya di menu
                                <strong>Metode Pembayaran</strong>.
                            </p>
                        @endif
                    </div>

                    <div>
                        <label class="block text-xs font-semibold mb-1" style="color: var(--text-dark)">
                            Catatan <span class="font-normal" style="color: var(--text-soft)">(opsional)</span>
                        </label>
                        <input type="text" name="reference_number"
                               class="form-input w-full text-sm" style="padding-top: 0.5rem; padding-bottom: 0.5rem;"
                               placeholder="Contoh: minta diantar via ojek, ambil jam 18:00...">
                    </div>

                    <div class="flex gap-2 pt-1">
                        <button type="button" @click="closeLateFeePayment()" class="btn-secondary flex-1 justify-center text-sm py-2.5">
                            Nanti Saja
                        </button>
                        <button type="submit" :disabled="lateFeePaymentLoading" :class="lateFeePaymentLoading ? 'btn-loading' : ''"
                                class="btn-primary flex-1 justify-center text-sm py-2.5 font-semibold"
                                style="background: linear-gradient(135deg, #C0392B, #E74C3C)">
                            <template x-if="lateFeePaymentLoading"><span class="btn-spinner"></span></template>
                            <span x-text="lateFeePaymentLoading ? '\u00A0Memproses...' : 'Konfirmasi Bayar Denda'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div x-show="showReturnModal"
         x-cloak
         @click.self="closeReturn()"
         @keydown.escape.window="closeReturn()"
         class="fixed inset-0 z-50 flex items-center justify-center"
         style="background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(2px);"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="modal-box w-full sm:max-w-lg mx-0 sm:mx-4 p-5 sm:p-6 rounded-t-2xl sm:rounded-2xl max-h-[90vh] overflow-y-auto"
             @click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-full sm:translate-y-4 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-full sm:translate-y-4 sm:scale-95">

            <div class="w-10 h-1 rounded-full mx-auto mb-4 sm:hidden" style="background: var(--border)"></div>

            <div class="flex items-center justify-between mb-5">
                <h3 class="font-playfair font-semibold text-base" style="color: var(--text-dark)">Proses Pengembalian</h3>
                <button @click="closeReturn()" class="p-2 rounded-lg hover:bg-gray-100 transition-colors" style="color: var(--text-soft)">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="p-3 rounded-xl mb-4 flex items-center gap-2" style="background: #F0FDF4; border: 1px solid #BBF7D0">
                <i data-lucide="info" class="w-4 h-4 flex-shrink-0" style="color: #15803D"></i>
                <p class="text-xs" style="color: #15803D">Pastikan semua barang sudah benar-benar diterima kembali sebelum konfirmasi.</p>
            </div>

            <form method="POST" action="{{ route('rentals.return', $rental) }}"
                @submit.prevent="handleReturnSubmit($event)">
                @csrf
                <div class="space-y-3 mb-4">
                    @php
                        $hasHeld = $rental->guarantees->where('status', 'held')->isNotEmpty();
                        $firstHeldGuarantee = $rental->guarantees->where('status', 'held')->first();
                    @endphp
                    @foreach($rental->items as $item)
                    @if(!$item->is_returned)
                    <div class="p-3 sm:p-4 rounded-xl" style="background: var(--bg-main); border: 1px solid var(--border)" data-subtotal="{{ (float) $item->subtotal }}">
                        <p class="font-semibold text-sm mb-3" style="color: var(--text-dark)">{{ $item->product_name }}</p>
                        <input type="hidden" name="items[{{ $loop->index }}][rental_item_id]" value="{{ $item->id }}">
                        <div class="grid grid-cols-3 gap-2 mb-2">
                            @foreach(['good' => ['Baik', 'badge-green'], 'damaged' => ['Rusak', 'badge-yellow'], 'lost' => ['Hilang', 'badge-red']] as $cond => [$label, $badge])
                            <label class="flex items-center justify-center gap-1.5 p-2 rounded-lg border cursor-pointer transition-all has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50/50"
                                   style="border-color: var(--border)">
                                <input type="radio" name="items[{{ $loop->parent->index }}][condition]"
                                       value="{{ $cond }}" {{ $cond === 'good' ? 'checked' : '' }} class="sr-only"
                                       @change="setReturnCondition({{ $loop->parent->index }}, '{{ $cond }}')">
                                <span class="badge {{ $badge }} text-[9px]">{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                        <input type="text" name="items[{{ $loop->index }}][notes]"
                               class="form-input text-xs w-full" placeholder="Catatan kondisi barang...">

                        <div x-show="returnConditions[{{ $loop->index }}] && returnConditions[{{ $loop->index }}] !== 'good'" x-cloak class="mt-3 space-y-2">
                            <p class="text-xs" style="color: var(--text-soft)">Opsi penyelesaian kerugian</p>
                            <div class="flex items-center gap-3">
                                <label class="flex items-center gap-2">
                                    <input type="radio" x-model="returnPenalties[{{ $loop->index }}]" name="items[{{ $loop->index }}][penalty_resolution]" value="charge_double" class="form-radio">
                                    <span class="text-sm">Tagih ke customer</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" x-model="returnPenalties[{{ $loop->index }}]" name="items[{{ $loop->index }}][penalty_resolution]" value="claim_guarantee" class="form-radio" @if(!$hasHeld) disabled title="Tidak ada jaminan tersimpan" @endif>
                                    <span class="text-sm">Gunakan jaminan</span>
                                </label>
                            </div>

                            <div class="mt-2" x-show="returnPenalties[{{ $loop->index }}] === 'charge_double'" x-cloak>
                                {{-- FIX: sama seperti input "Jumlah Bayar" — numerals only.
                                     x-model.number diganti update manual di @input supaya
                                     tidak dobel-listener dengan proses strip karakter
                                     non-digit (keduanya sama-sama pakai event 'input'). --}}
                                <input type="text" inputmode="numeric" pattern="[0-9]*"
                                       name="items[{{ $loop->index }}][penalty_amount]"
                                       :value="returnPenaltyAmounts[{{ $loop->index }}]"
                                       @input="$el.value = $el.value.replace(/[^0-9]/g, ''); returnPenaltyAmounts[{{ $loop->index }}] = $el.value === '' ? null : Number($el.value)"
                                       class="form-input text-xs w-full" placeholder="Jumlah kerugian (contoh: 50000)">
                            </div>

                            <div class="mt-2" x-show="returnPenalties[{{ $loop->index }}] === 'claim_guarantee'" x-cloak>
                                @if($hasHeld)
                                    <input type="hidden" name="items[{{ $loop->index }}][guarantee_id]" value="{{ $rental->guarantees->where('status', 'held')->first()->id }}">
                                    <div class="p-3 rounded-xl bg-yellow-50 border border-yellow-200 text-sm" style="color: #b45309;">
                                        Jaminan otomatis diklaim: <strong>{{ $rental->guarantees->where('status', 'held')->first()->type_label }}</strong>
                                        @if($rental->guarantees->where('status', 'held')->first()->id_name) milik {{ $rental->guarantees->where('status', 'held')->first()->id_name }}@endif
                                        @if($rental->guarantees->where('status', 'held')->first()->deposit_amount > 0) - Rp {{ number_format($rental->guarantees->where('status', 'held')->first()->deposit_amount, 0, ',', '.') }}@endif
                                    </div>
                                @else
                                    <div class="p-3 rounded-xl bg-gray-100 border border-gray-200 text-sm" style="color: var(--text-soft);">
                                        Tidak ada jaminan tersimpan untuk diklaim.
                                    </div>
                                @endif
                            </div>

                            <div class="mt-2">
                                <p class="text-sm" style="color: var(--text-soft)">Preview denda: <span x-text="(returnPenalties[{{ $loop->index }}] === 'charge_double') ? formatRupiah(returnPenaltyAmounts[{{ $loop->index }}] ?? ({{ (float)$item->subtotal }} * 2)) : (returnPenalties[{{ $loop->index }}] === 'claim_guarantee' ? 'Menggunakan jaminan' : '-')"></span></p>
                            </div>
                        </div>
                    </div>
                    @endif
                    @endforeach
                </div>

                <div class="flex gap-3">
                    <button type="button" @click="closeReturn()" class="btn-secondary flex-1 justify-center">Batal</button>
                    <button type="submit"
                            data-no-loading
                            :disabled="returnLoading"
                            :class="returnLoading ? 'btn-loading' : ''"
                            class="btn-primary flex-1 justify-center"
                            style="background: linear-gradient(135deg, #10B981, #059669); box-shadow: 0 2px 8px rgba(16,185,129,0.3)">
                        <template x-if="returnLoading">
                            <span class="btn-spinner"></span>
                        </template>
                        <template x-if="!returnLoading">
                            <i data-lucide="package-check" class="w-4 h-4"></i>
                        </template>
                        <span x-text="returnLoading ? '\u00A0Memproses...' : 'Konfirmasi Pengembalian'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== KTP VIEWER MODAL (zoom in/out + geser/scroll) ===== --}}
    <div x-show="ktpViewer.open" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center"
         style="background: rgba(0,0,0,0.85)"
         @keydown.escape.window="closeKtpViewer()">
        <button type="button" @click="closeKtpViewer()"
                class="absolute top-4 right-4 w-10 h-10 rounded-full flex items-center justify-center z-10"
                style="background: rgba(255,255,255,0.15)">
            <i data-lucide="x" class="w-5 h-5 text-white"></i>
        </button>

        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex items-center gap-2 z-10 px-3 py-2 rounded-full"
             style="background: rgba(255,255,255,0.15)">
            <button type="button" @click="zoomKtp(-0.25)" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-white/10">
                <i data-lucide="zoom-out" class="w-4 h-4 text-white"></i>
            </button>
            <span class="text-xs text-white w-12 text-center" x-text="Math.round(ktpViewer.scale * 100) + '%'"></span>
            <button type="button" @click="zoomKtp(0.25)" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-white/10">
                <i data-lucide="zoom-in" class="w-4 h-4 text-white"></i>
            </button>
            <button type="button" @click="resetKtpView()" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-white/10" title="Reset">
                <i data-lucide="maximize" class="w-4 h-4 text-white"></i>
            </button>
            <div class="w-px h-5" style="background: rgba(255,255,255,0.25)"></div>
            <button type="button" @click="ktpViewer.rotate = (ktpViewer.rotate + 90) % 360" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-white/10" title="Putar">
                <i data-lucide="rotate-cw" class="w-4 h-4 text-white"></i>
            </button>
        </div>

        <div class="w-full h-full overflow-auto flex items-center justify-center p-6 select-none"
             @wheel.prevent="zoomKtp($event.deltaY < 0 ? 0.15 : -0.15)"
             @mousedown="ktpViewer.dragging = true; ktpViewer.startX = $event.clientX; ktpViewer.startY = $event.clientY"
             @mousemove="if (ktpViewer.dragging) { ktpViewer.panX += ($event.clientX - ktpViewer.startX); ktpViewer.panY += ($event.clientY - ktpViewer.startY); ktpViewer.startX = $event.clientX; ktpViewer.startY = $event.clientY; }"
             @mouseup="ktpViewer.dragging = false"
             @mouseleave="ktpViewer.dragging = false"
             @touchstart="ktpViewer.dragging = true; ktpViewer.startX = $event.touches[0].clientX; ktpViewer.startY = $event.touches[0].clientY"
             @touchmove="if (ktpViewer.dragging) { ktpViewer.panX += ($event.touches[0].clientX - ktpViewer.startX); ktpViewer.panY += ($event.touches[0].clientY - ktpViewer.startY); ktpViewer.startX = $event.touches[0].clientX; ktpViewer.startY = $event.touches[0].clientY; }"
             @touchend="ktpViewer.dragging = false">
            <img :src="ktpViewer.src" alt="Foto KTP"
                 class="max-w-none rounded-lg shadow-2xl transition-transform"
                 :class="ktpViewer.dragging ? 'cursor-grabbing' : 'cursor-grab'"
                 :style="'transform: translate(' + ktpViewer.panX + 'px,' + ktpViewer.panY + 'px) scale(' + ktpViewer.scale + ') rotate(' + ktpViewer.rotate + 'deg); transform-origin: center;'"
                 draggable="false">
        </div>
    </div>

    {{-- ===== MODAL PREVIEW NOTA SEBELUM KIRIM WA ===== --}}
    <div x-show="showWaPreviewModal"
         x-cloak
         @click.self="closeWaPreview()"
         @keydown.escape.window="closeWaPreview()"
         class="fixed inset-0 z-50 flex items-center justify-center p-3"
         style="background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="modal-box w-full max-w-lg rounded-2xl flex flex-col"
             @click.stop
             style="max-height: 92vh;"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            <div class="flex items-center justify-between p-4 border-b flex-shrink-0" style="border-color: var(--border)">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background: #DCFCE7">
                        <i data-lucide="message-circle" class="w-3.5 h-3.5" style="color: #16A34A"></i>
                    </div>
                    <h3 class="font-playfair font-bold text-sm" style="color: var(--text-dark)">Pratinjau Nota Sebelum Kirim</h3>
                </div>
                <button @click="closeWaPreview()"
                        class="w-7 h-7 rounded-lg flex items-center justify-center hover:bg-gray-100 transition-colors"
                        style="color: var(--text-soft)">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
            </div>

            <div class="p-3 flex-1" style="min-height: 0; overflow: hidden;">
                <p class="text-xs mb-2" style="color: var(--text-soft)">
                    Periksa dulu isi nota di bawah — kalau sudah benar, klik konfirmasi untuk membuka WhatsApp.
                </p>
                <template x-if="showWaPreviewModal">
                    <iframe src="{{ route('rentals.invoice.pdf.public', $rental->public_token) }}"
                            class="w-full rounded-lg border"
                            style="height: 55vh; border-color: var(--border);"></iframe>
                </template>
            </div>

            <div class="flex gap-2 p-4 border-t flex-shrink-0" style="border-color: var(--border)">
                <button type="button" @click="closeWaPreview()" class="btn-secondary flex-1 justify-center text-sm">
                    Batal
                </button>
                <button type="button" @click="confirmSendWa()"
                        class="flex-1 justify-center text-sm rounded-xl font-semibold flex items-center gap-2"
                        style="background: #25D366; color: white; padding: 0.6rem 1.4rem;">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                    Konfirmasi &amp; Kirim WA
                </button>
            </div>
        </div>
    </div>

    {{-- ===== MODAL BATALKAN PENYEWAAN ===== --}}
    <div x-show="showCancelModal"
         x-cloak
         @click.self="closeCancel()"
         @keydown.escape.window="closeCancel()"
         class="fixed inset-0 z-50 flex items-start justify-center px-4 pt-16"
         style="background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="modal-box w-full max-w-sm rounded-2xl"
             @click.stop
             style="max-height: 90vh; overflow-y: auto;"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            <div class="flex items-center justify-between p-4 border-b" style="border-color: var(--border)">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background: #FEE2E2">
                        <i data-lucide="x-circle" class="w-3.5 h-3.5" style="color: #DC2626"></i>
                    </div>
                    <h3 class="font-playfair font-bold text-sm" style="color: var(--text-dark)">Batalkan Penyewaan</h3>
                </div>
                <button @click="closeCancel()"
                        class="w-7 h-7 rounded-lg flex items-center justify-center hover:bg-gray-100 transition-colors"
                        style="color: var(--text-soft)">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
            </div>

            <div class="p-4 space-y-3">
                <div class="rounded-xl p-3" style="background: #FEF2F2; border: 1px solid #FECACA">
                    <p class="text-xs" style="color: #991B1B">
                        Penyewaan <strong>{{ $rental->invoice_number }}</strong> akan dibatalkan. Barang belum pernah dipakai — stok langsung dikembalikan dan total tagihan dinolkan. Tindakan ini tidak bisa dibatalkan (undo).
                    </p>
                </div>

                <form method="POST" action="{{ route('rentals.cancel', $rental) }}" class="space-y-3"
                      @submit="cancelLoading = true">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="block text-xs font-semibold mb-1" style="color: var(--text-dark)">
                            Alasan Pembatalan
                            <span class="font-normal" style="color: var(--text-soft)">(opsional)</span>
                        </label>
                        <textarea name="reason" x-model="cancelReason" rows="3" maxlength="255"
                                  class="form-input w-full text-sm"
                                  placeholder="Contoh: customer batal, salah input, dll."></textarea>
                    </div>

                    <div class="flex gap-2 pt-1">
                        <button type="button" @click="closeCancel()" class="btn-secondary flex-1 justify-center text-sm">
                            Batal
                        </button>
                        <button type="submit" :disabled="cancelLoading"
                                class="flex-1 justify-center text-sm rounded-xl font-semibold flex items-center gap-2"
                                style="background: #DC2626; color: white; padding: 0.6rem 1.4rem;"
                                :class="cancelLoading ? 'opacity-70' : ''">
                            <template x-if="cancelLoading">
                                <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                            </template>
                            <span x-text="cancelLoading ? 'Memproses...' : 'Ya, Batalkan'"></span>
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
function rentalDetail() {
    return {
        showPaymentModal: false,
        showReturnModal: false,
        showCancelModal: false,
        showWaPreviewModal: false,
        paymentLoading: false,
        returnLoading: false,
        discountLoading: false,
        cancelLoading: false,
        cancelReason: '',

        paymentMethod: 'cash',
        paymentChannel: '',      // nama bank (transfer) ATAU bank/e-wallet (qris)
        paymentAccountNumber: '',
        // Sumber: pengaturan Superadmin (menu Metode Pembayaran) — lihat
        // window.APP_PAYMENT_SETTINGS yang di-inject di layouts/app.blade.php.
        banks: window.APP_PAYMENT_SETTINGS?.payment_banks || [],
        bankOptions: (window.APP_PAYMENT_SETTINGS?.payment_banks || []).map(b => b.name).filter(Boolean),
        qrisOptions: [
            ...(window.APP_PAYMENT_SETTINGS?.payment_banks || []).map(b => b.name).filter(Boolean),
            ...(window.APP_PAYMENT_SETTINGS?.payment_qris_channels || []),
        ],
        otherOptions: ['Lain-lain'],
        // Isi otomatis nomor rekening tujuan begitu kasir memilih bank
        // (nomor rekening diatur Superadmin di menu Metode Pembayaran).
        // Kasir tetap bisa mengubahnya manual jika perlu.
        autofillBankAccount() {
            const bank = this.banks.find(b => b.name === this.paymentChannel);
            this.paymentAccountNumber = bank?.account_number || '';
        },
        autofillLateFeeBankAccount() {
            const bank = this.banks.find(b => b.name === this.lateFeeChannel);
            this.lateFeeAccountNumber = bank?.account_number || '';
        },
        resetPaymentMethod() {
            this.paymentChannel = '';
            this.paymentAccountNumber = '';
        },

        // ── Foto KTP viewer (zoom in/out, geser/scroll, putar) ──
        ktpViewer: { open: false, src: '', scale: 1, panX: 0, panY: 0, rotate: 0, dragging: false, startX: 0, startY: 0 },

        openKtpViewer(src) {
            if (!src) return;
            this.ktpViewer.open   = true;
            this.ktpViewer.src    = src;
            this.ktpViewer.scale  = 1;
            this.ktpViewer.panX   = 0;
            this.ktpViewer.panY   = 0;
            this.ktpViewer.rotate = 0;
            document.body.style.overflow = 'hidden';
        },
        closeKtpViewer() {
            this.ktpViewer.open = false;
            document.body.style.overflow = '';
        },
        zoomKtp(delta) {
            this.ktpViewer.scale = Math.min(4, Math.max(0.5, this.ktpViewer.scale + delta));
        },
        resetKtpView() {
            this.ktpViewer.scale  = 1;
            this.ktpViewer.panX   = 0;
            this.ktpViewer.panY   = 0;
            this.ktpViewer.rotate = 0;
        },

        // ── FIX: open/close dengan lock scroll body ──
        openPayment() {
            this.showPaymentModal = true;
            document.body.style.overflow = 'hidden';
        },
        closePayment() {
            this.showPaymentModal = false;
            this.paymentLoading = false;
            document.body.style.overflow = '';
        },

        // ── FITUR BARU: modal Tentukan Denda & Bayar Denda (dipindah dari
        // halaman scan supaya bisa langsung diakses dari halaman detail juga) ──
        showLateFeeSetModal: false,
        lateFeeSetLoading: false,
        openLateFeeSet() {
            this.showLateFeeSetModal = true;
            document.body.style.overflow = 'hidden';
        },
        closeLateFeeSet() {
            this.showLateFeeSetModal = false;
            this.lateFeeSetLoading = false;
            document.body.style.overflow = '';
        },

        showLateFeePaymentModal: false,
        lateFeePaymentLoading: false,
        lateFeeMethod: 'cash',
        lateFeeChannel: '',
        lateFeeAccountNumber: '',
        openLateFeePayment() {
            this.showLateFeePaymentModal = true;
            document.body.style.overflow = 'hidden';
        },
        closeLateFeePayment() {
            this.showLateFeePaymentModal = false;
            this.lateFeePaymentLoading = false;
            document.body.style.overflow = '';
        },
        openReturn() {
            this.showReturnModal = true;
            document.body.style.overflow = 'hidden';
        },
        closeReturn() {
            this.showReturnModal = false;
            this.returnLoading = false;
            document.body.style.overflow = '';
        },
        // track per-item selected condition to reveal penalty options
        returnConditions: {},
        // track per-item selected penalty resolution to show amount/guarantee fields
        returnPenalties: {},
        // track per-item penalty amount for live preview
        returnPenaltyAmounts: {},
        // format number to Indonesian Rupiah-like string
        formatRupiah(v) {
            const n = Number(v) || 0;
            return 'Rp ' + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        },
        setReturnCondition(index, value) {
            this.returnConditions[index] = value;
        },
        handleReturnSubmit(e) {
            // Validate that any item with claim_guarantee has a selected guarantee.
            const form = e.target;
            const penaltyInputs = form.querySelectorAll('input[name$="[penalty_resolution]"]');

            for (const inp of penaltyInputs) {
                if (!inp.checked) continue;
                if (inp.value !== 'claim_guarantee') continue;

                // Determine the items index from the name attribute: items[<i>][penalty_resolution]
                const m = inp.name.match(/^items\[(\d+)\]\[penalty_resolution\]$/);
                if (!m) continue;
                const idx = m[1];
                const select = form.querySelector('select[name="items[' + idx + '][guarantee_id]"]');

                // If select missing or has no value, ask the user what to do.
                const guaranteeInput = form.querySelector('input[name="items[' + idx + '][guarantee_id]"], select[name="items[' + idx + '][guarantee_id]"]');
                if (!guaranteeInput || !guaranteeInput.value) {
                    const useMoney = confirm('Anda memilih "Gunakan jaminan" untuk item ini tetapi belum ada jaminan yang dapat diklaim.\n\nPilih OK untuk mengenakan denda uang (sistem denda), atau Cancel untuk batalkan pilihan jaminan.');
                    if (useMoney) {
                        const chargeRadio = form.querySelector('input[name="items[' + idx + '][penalty_resolution]"][value="charge_double"]');
                        if (chargeRadio) {
                            chargeRadio.checked = true;
                            chargeRadio.dispatchEvent(new Event('change'));
                        }
                        const amtInput = form.querySelector('input[name="items[' + idx + '][penalty_amount]"]');
                        if (amtInput && (!amtInput.value || Number(amtInput.value) === 0)) {
                            const container = inp.closest('[data-subtotal]');
                            const sub = container ? Number(container.getAttribute('data-subtotal')) : 0;
                            amtInput.value = Math.max(0, Math.round(sub * 2));
                            amtInput.dispatchEvent(new Event('input'));
                        }
                        continue;
                    } else {
                        if (guaranteeInput) {
                            guaranteeInput.focus();
                        } else {
                            alert('Tidak ada jaminan yang dapat diklaim untuk item ini. Silakan pilih opsi denda uang.');
                        }
                        return;
                    }
                }
            }

            // All validations passed — proceed with submit
            this.returnLoading = true;
            form.submit();
        },
        openCancel() {
            this.showCancelModal = true;
            document.body.style.overflow = 'hidden';
        },
        closeCancel() {
            this.showCancelModal = false;
            this.cancelLoading = false;
            this.cancelReason = '';
            document.body.style.overflow = '';
        },
        openWaPreview() {
            this.showWaPreviewModal = true;
            document.body.style.overflow = 'hidden';
        },
        closeWaPreview() {
            this.showWaPreviewModal = false;
            document.body.style.overflow = '';
        },
        confirmSendWa() {
            window.open('{{ route('rentals.whatsapp', $rental) }}', '_blank');
            this.closeWaPreview();
        },
    }
}
</script>
@endpush