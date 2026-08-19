@extends('layouts.app')

@section('title', 'Invoice ' . $rental->invoice_number)
@section('page-title', 'Invoice Penyewaan')
@section('subtitle', $rental->invoice_number)

@section('content')
@php
    $invoiceSettings = $invoiceSettings ?? \App\Models\Setting::invoice();
    $invoiceCompanyName = $invoiceSettings['invoice_company_name'] ?: config('app.name');
    $invoiceTagline = $invoiceSettings['invoice_tagline'] ?: 'Premium Suit Rental';
    $invoiceLogoPath = null;

    if ($invoiceSettings['invoice_show_logo']) {
        if (!empty($invoiceSettings['invoice_logo_path'])) {
            $invoiceLogoPath = asset('storage/' . $invoiceSettings['invoice_logo_path']);
        } elseif ($invoiceSettings['invoice_use_branch_logo'] && $rental->branch?->logo) {
            $invoiceLogoPath = asset('storage/' . $rental->branch->logo);
        }
    }

    $invoiceTerms = trim($invoiceSettings['invoice_terms'] ?? '');
    $invoiceFooter = $invoiceSettings['invoice_footer_text'] ?: ('Dokumen dicetak otomatis - ' . $invoiceCompanyName . ' - ' . now()->format('d M Y H:i') . ' WIB');
@endphp
<div class="space-y-4">

    <!-- Action Bar -->
    <div class="flex items-center justify-between">
        <a href="{{ route('rentals.show', $rental) }}" class="btn-secondary">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Kembali
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('rentals.thermal', $rental) }}" target="_blank" class="btn-secondary">
                <i data-lucide="receipt" class="w-4 h-4"></i>
                Nota Thermal
            </a>
            <a href="{{ route('rentals.pdf', $rental) }}" class="btn-secondary">
                <i data-lucide="file-down" class="w-4 h-4"></i>
                Download PDF
            </a>
            <a href="{{ route('rentals.whatsapp', $rental) }}" target="_blank" class="btn-secondary" style="color: #25D366">
                <i data-lucide="message-circle" class="w-4 h-4"></i>
                Kirim WhatsApp
            </a>
            <button onclick="window.print()" class="btn-primary">
                <i data-lucide="printer" class="w-4 h-4"></i>
                Print
            </button>
        </div>
    </div>

    <!-- Invoice Card -->
    <div class="card p-8 max-w-4xl mx-auto print:shadow-none print:border-none" id="invoice-content"
         style="--inv-heading: {{ $invoiceSettings['invoice_heading_color'] }}; --inv-accent: {{ $invoiceSettings['invoice_primary_color'] }}; --inv-text: {{ $invoiceSettings['invoice_text_color'] }}; --inv-text-secondary: {{ $invoiceSettings['invoice_muted_color'] }}; --inv-border-strong: {{ $invoiceSettings['invoice_primary_color'] }};">

        <!-- Header -->
        <div class="flex justify-between items-start pb-6 border-b-2" style="border-color: var(--inv-border-strong)">
            <div>
                @if($invoiceLogoPath)
                    <img src="{{ $invoiceLogoPath }}" alt="Logo {{ $invoiceCompanyName }}" class="h-12 max-w-[180px] object-contain mb-2">
                @endif
                <h1 class="font-playfair text-2xl font-bold" style="color: var(--inv-heading)">{{ $invoiceCompanyName }}</h1>
                <p class="text-xs font-semibold tracking-widest mt-1 uppercase" style="color: var(--inv-accent)">{{ $invoiceTagline }}</p>
                <div class="mt-3 text-xs leading-6" style="color: var(--inv-text-secondary)">
                    <p class="font-semibold" style="color: var(--inv-text)">{{ $rental->branch->name }}</p>
                    <p>{{ $rental->branch->address }}</p>
                    @if($rental->branch->phone)<p>Telp: {{ $rental->branch->phone }}</p>@endif
                    @if($rental->branch->email)<p>Email: {{ $rental->branch->email }}</p>@endif
                </div>
            </div>
            <div class="text-right">
                <p class="text-3xl font-bold tracking-wider" style="color: var(--inv-accent)">INVOICE</p>
                <p class="font-mono font-bold text-lg mt-1" style="color: var(--inv-text)">{{ $rental->invoice_number }}</p>
                <p class="text-xs mt-1" style="color: var(--inv-text-secondary)">{{ $rental->created_at->format('d F Y, H:i') }} WIB</p>

                <!-- Status badge -->
                <div class="mt-3 inline-block px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider
                    {{ match($rental->payment_status) { 'paid' => 'bg-green-100 text-green-700', 'partial' => 'bg-yellow-100 text-yellow-700', default => 'bg-red-100 text-red-600' } }}">
                    {{ $rental->payment_status_label }}
                </div>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="grid grid-cols-3 gap-6 py-6 border-b" style="border-color: var(--inv-border)">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider mb-3" style="color: var(--inv-accent)">Tagihan Kepada</p>
                <p class="font-bold" style="color: var(--inv-text)">{{ $rental->customer->name }}</p>
                <p class="text-sm mt-1" style="color: var(--inv-text-secondary)">{{ $rental->customer->phone }}</p>
                @if($rental->customer->address)
                <p class="text-sm mt-1 leading-5" style="color: var(--inv-text-secondary)">{{ $rental->customer->address }}</p>
                @endif
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wider mb-3" style="color: var(--inv-accent)">Jadwal</p>
                <div class="space-y-2">
                    <div>
                        <p class="text-xs" style="color: var(--inv-text-secondary)">Tanggal Mulai</p>
                        <p class="font-semibold text-sm" style="color: var(--inv-text)">{{ $rental->rental_date->format('d F Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs" style="color: var(--inv-text-secondary)">Jatuh Tempo</p>
                        <p class="font-semibold text-sm" style="color: var(--inv-text)">{{ $rental->return_due_date->format('d F Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs" style="color: var(--inv-text-secondary)">Durasi</p>
                        <p class="font-semibold text-sm" style="color: var(--inv-text)">{{ $rental->duration_days }} Hari
                            @if($rental->package)
                            <span class="text-xs font-normal" style="color: var(--inv-text-secondary)">({{ $rental->package->name }})</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wider mb-3" style="color: var(--inv-accent)">Info Transaksi</p>
                <div class="space-y-2">
                    <div>
                        <p class="text-xs" style="color: var(--inv-text-secondary)">Dibuat Oleh</p>
                        <p class="font-semibold text-sm" style="color: var(--inv-text)">{{ $rental->createdBy->name }}</p>
                    </div>
                    {{-- FITUR BARU: staf yang benar-benar memproses pengembalian
                         seringkali berbeda orang dari yang membuat transaksi
                         awal -> ditampilkan terpisah, hanya muncul kalau
                         barang sudah diproses retur (returnedBy terisi). --}}
                    @if($rental->returnedBy)
                    <div>
                        <p class="text-xs" style="color: var(--inv-text-secondary)">Dikembalikan Oleh</p>
                        <p class="font-semibold text-sm" style="color: var(--inv-text)">{{ $rental->returnedBy->name }}</p>
                    </div>
                    @endif
                    <div>
                        <p class="text-xs" style="color: var(--inv-text-secondary)">Cabang</p>
                        <p class="font-semibold text-sm" style="color: var(--inv-text)">{{ $rental->branch->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs" style="color: var(--inv-text-secondary)">Status Penyewaan</p>
                        <span class="badge badge-{{ $rental->status_badge_color }} mt-0.5">{{ $rental->status_label }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="py-6">
            <table class="w-full elegant-table">
                <thead>
                    <tr>
                        <th class="text-left w-8">#</th>
                        <th class="text-left">Nama Barang</th>
                        <th class="text-left">Ukuran</th>
                        <th class="text-left">Warna</th>
                        <th class="text-center">Qty</th>
                        <th class="text-right">Harga/Hari</th>
                        <th class="text-center">Hari</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rental->items as $i => $item)
                    <tr>
                        <td class="text-xs" style="color: var(--inv-text-secondary)">{{ $i + 1 }}</td>
                        <td>
                            <p class="font-semibold" style="color: var(--inv-text)">{{ $item->product_name }}</p>
                            @if($item->is_returned)
                            <span class="badge badge-green mt-0.5">Sudah Dikembalikan</span>
                            @endif
                        </td>
                        <td style="color: var(--inv-text-secondary)">{{ $item->product_size ?? '-' }}</td>
                        <td style="color: var(--inv-text-secondary)">{{ $item->product_color ?? '-' }}</td>
                        <td class="text-center font-semibold" style="color: var(--inv-text)">{{ $item->quantity }}</td>
                        <td class="text-right" style="color: var(--inv-text)">Rp {{ number_format($item->price_per_day, 0, ',', '.') }}</td>
                        <td class="text-center" style="color: var(--inv-text)">{{ $item->duration_days }}</td>
                        <td class="text-right font-bold" style="color: var(--inv-text)">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals ─── FIX: QR dipindah keluar dari sini (lihat "Tiket
             Verifikasi" di bawah dekat tanda tangan) supaya jadi elemen
             besar & benar-benar dominan/center di halaman, gaya e-tiket
             KAI Access — bukan lagi ikon kecil di samping rincian total. -->
        <div class="pt-4 border-t" style="border-color: var(--inv-border)">
            <div class="w-full sm:w-64 sm:ml-auto">
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span style="color: var(--inv-text-secondary)">Subtotal</span>
                        <span style="color: var(--inv-text)">Rp {{ number_format($rental->subtotal, 0, ',', '.') }}</span>
                    </div>
                    @if(!$rental->has_manual_discount && $rental->discount > 0)
                    <div class="flex justify-between text-sm">
                        <span style="color: var(--inv-text-secondary)">Diskon</span>
                        <span style="color: #E74C3C">-Rp {{ number_format($rental->discount, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    @if($rental->late_fee > 0)
                    <div class="flex justify-between text-sm">
                        <span style="color: var(--inv-text-secondary)">Denda Keterlambatan</span>
                        <span style="color: #E74C3C">+Rp {{ number_format($rental->late_fee, 0, ',', '.') }}</span>
                    </div>
                    @endif
                                        @if($rental->total_damage_fee > 0)
                    <div class="flex justify-between text-sm">
                        <span style="color: var(--inv-text-secondary)">Denda Rusak/Hilang</span>
                        <span style="color: #E74C3C">+Rp {{ number_format($rental->total_damage_fee, 0, ',', '.') }}</span>
                    </div>
                    @endif
                                        @if($rental->has_manual_discount)
                    <div class="flex justify-between text-sm">
                        <span style="color: var(--inv-text-secondary)">
                            Diskon Manual{{ $rental->discount_name ? " ({$rental->discount_name})" : '' }}
                        </span>
                        <span style="color: #E74C3C">-Rp {{ number_format($rental->discount, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between items-center pt-3 border-t-2" style="border-color: var(--inv-border-strong)">
                        <span class="font-bold text-base" style="color: var(--inv-text)">TOTAL</span>
                        <span class="font-bold text-xl font-playfair" style="color: var(--inv-accent)">Rp {{ number_format($rental->total_amount, 0, ',', '.') }}</span>
                    </div>
                    @if($rental->paid_amount > 0)
                    <div class="flex justify-between text-sm">
                        <span style="color: var(--inv-text-secondary)">Dibayar</span>
                        <span style="color: #22C55E; font-weight: 600">Rp {{ number_format($rental->paid_amount, 0, ',', '.') }}</span>
                    </div>
                    @if($rental->remaining_amount > 0)
                    <div class="flex justify-between text-sm font-bold">
                        <span style="color: #E15B4B">Sisa Tagihan</span>
                        <span style="color: #E15B4B">Rp {{ number_format($rental->remaining_amount, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    @endif
                </div>
            </div>
        </div>

        @php
            $paymentNoteClass = match ($rental->payment_status) {
                'paid' => 'invoice-note-green',
                'partial' => 'invoice-note-amber',
                default => 'invoice-note-red',
            };
        @endphp

        <!-- Colored Notes & Signature -->
        <div class="mt-8 pt-6 border-t" style="border-color: var(--inv-border)">
            <div class="grid grid-cols-2 gap-3">
                <div class="invoice-note invoice-note-blue">
                    <div class="invoice-note-head">
                        <i data-lucide="message-square-text" class="w-4 h-4"></i>
                        <span>Catatan Transaksi</span>
                    </div>
                    <p>
                        {{ $rental->notes ?: 'Tidak ada catatan khusus untuk transaksi ini.' }}
                    </p>
                </div>

                <div class="invoice-note invoice-note-amber">
                    <div class="invoice-note-head">
                        <i data-lucide="clock-alert" class="w-4 h-4"></i>
                        <span>Pengingat Jatuh Tempo</span>
                    </div>
                    <p>
                        Barang wajib kembali pada <strong>{{ $rental->return_due_date->format('d F Y') }}</strong>.
                        Keterlambatan pengembalian akan dikenakan denda sesuai kebijakan toko.
                    </p>
                </div>

                <div class="invoice-note {{ $paymentNoteClass }}">
                    <div class="invoice-note-head">
                        <i data-lucide="wallet-cards" class="w-4 h-4"></i>
                        <span>Status Pembayaran</span>
                    </div>
                    <p>
                        Status saat invoice dibuat: <strong>{{ $rental->payment_status_label }}</strong>.
                        @if($rental->remaining_amount > 0)
                            Sisa tagihan Rp {{ number_format($rental->remaining_amount, 0, ',', '.') }}.
                        @else
                            Pembayaran sudah tercatat lunas.
                        @endif
                    </p>
                </div>

                <div class="invoice-note invoice-note-violet">
                    <div class="invoice-note-head">
                        <i data-lucide="shield-check" class="w-4 h-4"></i>
                        <span>Syarat Penting</span>
                    </div>
                    <p>
                        {!! nl2br(e($invoiceTerms ?: 'Kerusakan atau kehilangan menjadi tanggung jawab penyewa. Jaminan dikembalikan setelah semua barang kembali dalam kondisi baik.')) !!}
                    </p>
                </div>
            </div>

            <div class="flex justify-end mt-8">
                <div class="text-center">
                <div style="height: 60px; border-bottom: 1px solid var(--inv-border-strong); width: 160px;"></div>
                {{-- FIX: tanda tangan sekarang ikut staf yang relevan dengan
                     status transaksi saat ini — kalau barang sudah
                     dikembalikan, yang menandatangani adalah staf yang
                     memproses retur (returnedBy), bukan selalu staf yang
                     membuat transaksi di awal (createdBy). --}}
                <p class="text-xs mt-2" style="color: var(--inv-text-secondary)">{{ ($rental->returnedBy ?? $rental->createdBy)->name }}</p>
                <p class="text-xs" style="color: var(--inv-text-secondary)">{{ $rental->returnedBy ? 'Petugas Penerima Retur' : 'Petugas / Admin' }}</p>
                </div>
            </div>
        </div>

        {{-- FITUR BARU: ticket-stub QR — gaya e-tiket KAI Access. Barcode
             besar, benar-benar di tengah halaman (bukan lagi ikon kecil di
             samping rincian total), dengan garis putus-putus di atas
             seperti sobekan tiket, plus nomor invoice diulang di bawahnya. --}}
        @if($invoiceSettings['invoice_show_qr'] && $rental->qr_code)
        <div class="mt-8 pt-6 text-center" style="border-top: 2px dashed var(--inv-accent)">
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] mb-3" style="color: var(--inv-accent)">Tiket Verifikasi Transaksi</p>
            <img src="{{ asset('storage/' . $rental->qr_code) }}" alt="QR" class="w-36 h-36 sm:w-40 sm:h-40 mx-auto">
            <p class="text-sm font-bold font-mono tracking-widest mt-3" style="color: var(--inv-text)">{{ $rental->invoice_number }}</p>
            <p class="text-xs mt-1" style="color: var(--inv-text-secondary)">Scan kode ini untuk verifikasi atau proses pengembalian barang</p>
        </div>
        @endif

        <!-- Bottom -->
        <div class="mt-8 text-center border-t pt-4" style="border-color: var(--inv-border)">
            <p class="text-xs" style="color: var(--inv-text-secondary)">{{ $invoiceFooter }}</p>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Theme-aware colors for the invoice card. Falls back to the light-mode
   palette by default, then switches to a light-on-dark palette whenever
   dark mode is active — covers the common ways dark mode gets toggled
   (class="dark" on <html>/<body>, or a data-theme="dark" attribute). */
#invoice-content {
    --inv-heading: #2B2520;
    --inv-accent: #D6B98C;
    --inv-text: #2B2B2B;
    --inv-text-secondary: #6B6B6B;
    --inv-border: #F0EBE3;
    --inv-border-strong: #D6B98C;
    --note-blue-bg: #EFF6FF;
    --note-blue-border: #93C5FD;
    --note-blue-text: #1E3A8A;
    --note-amber-bg: #FFFBEB;
    --note-amber-border: #FCD34D;
    --note-amber-text: #92400E;
    --note-green-bg: #ECFDF5;
    --note-green-border: #86EFAC;
    --note-green-text: #166534;
    --note-red-bg: #FEF2F2;
    --note-red-border: #FCA5A5;
    --note-red-text: #991B1B;
    --note-violet-bg: #F5F3FF;
    --note-violet-border: #C4B5FD;
    --note-violet-text: #4C1D95;
}

.invoice-note {
    border: 1px solid;
    border-radius: 8px;
    padding: 12px 14px;
    min-height: 104px;
}

.invoice-note-head {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    margin-bottom: 8px;
}

.invoice-note p {
    font-size: 12px;
    line-height: 1.6;
}

.invoice-note-blue { background: var(--note-blue-bg); border-color: var(--note-blue-border); color: var(--note-blue-text); }
.invoice-note-amber { background: var(--note-amber-bg); border-color: var(--note-amber-border); color: var(--note-amber-text); }
.invoice-note-green { background: var(--note-green-bg); border-color: var(--note-green-border); color: var(--note-green-text); }
.invoice-note-red { background: var(--note-red-bg); border-color: var(--note-red-border); color: var(--note-red-text); }
.invoice-note-violet { background: var(--note-violet-bg); border-color: var(--note-violet-border); color: var(--note-violet-text); }

html.dark #invoice-content,
body.dark #invoice-content,
.dark #invoice-content,
[data-theme="dark"] #invoice-content {
    --inv-heading: #F5EFE6;
    --inv-accent: #E3C79A;
    --inv-text: #ECE5D8;
    --inv-text-secondary: #A79F91;
    --inv-border: rgba(255, 255, 255, 0.12);
    --inv-border-strong: #C9A876;
    --note-blue-bg: rgba(37, 99, 235, 0.13);
    --note-blue-border: rgba(96, 165, 250, 0.42);
    --note-blue-text: #BFDBFE;
    --note-amber-bg: rgba(217, 119, 6, 0.14);
    --note-amber-border: rgba(251, 191, 36, 0.42);
    --note-amber-text: #FDE68A;
    --note-green-bg: rgba(22, 163, 74, 0.14);
    --note-green-border: rgba(74, 222, 128, 0.38);
    --note-green-text: #BBF7D0;
    --note-red-bg: rgba(220, 38, 38, 0.14);
    --note-red-border: rgba(248, 113, 113, 0.42);
    --note-red-text: #FECACA;
    --note-violet-bg: rgba(124, 58, 237, 0.14);
    --note-violet-border: rgba(167, 139, 250, 0.42);
    --note-violet-text: #DDD6FE;
}

@media print {
    .sidebar, header, .btn-primary, .btn-secondary, nav { display: none !important; }
    main { padding: 0 !important; margin: 0 !important; }
    .card { box-shadow: none !important; border: none !important; }
    #invoice-content { max-width: 100% !important; }

    /* Always print on a light background regardless of on-screen theme */
    #invoice-content {
        --inv-heading: #2B2520;
        --inv-accent: #D6B98C;
        --inv-text: #2B2B2B;
        --inv-text-secondary: #6B6B6B;
        --inv-border: #F0EBE3;
        --inv-border-strong: #D6B98C;
        --note-blue-bg: #EFF6FF;
        --note-blue-border: #93C5FD;
        --note-blue-text: #1E3A8A;
        --note-amber-bg: #FFFBEB;
        --note-amber-border: #FCD34D;
        --note-amber-text: #92400E;
        --note-green-bg: #ECFDF5;
        --note-green-border: #86EFAC;
        --note-green-text: #166534;
        --note-red-bg: #FEF2F2;
        --note-red-border: #FCA5A5;
        --note-red-text: #991B1B;
        --note-violet-bg: #F5F3FF;
        --note-violet-border: #C4B5FD;
        --note-violet-text: #4C1D95;
        background: #ffffff !important;
    }
}
</style>
@endpush
