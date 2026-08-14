<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #2B2B2B; background: #FFFFFF; }

    .page { padding: 28px 32px; }

    .header-table { width: 100%; border-collapse: collapse; padding-bottom: 20px; margin-bottom: 20px; border-bottom: 2px solid #D6B98C; }
    .brand-name { font-size: 22px; font-weight: bold; color: #2B2520; letter-spacing: -0.5px; }
    .brand-tagline { font-size: 9px; color: #D6B98C; text-transform: uppercase; letter-spacing: 2px; margin-top: 2px; }
    .brand-address { font-size: 9px; color: #6B6B6B; margin-top: 6px; line-height: 1.5; }

    .invoice-badge { text-align: right; }
    .invoice-title { font-size: 20px; font-weight: bold; color: #D6B98C; letter-spacing: 1px; text-transform: uppercase; }
    .invoice-number { font-size: 12px; font-weight: bold; color: #2B2B2B; margin-top: 4px; font-family: 'Courier New', monospace; }
    .invoice-date { font-size: 9px; color: #6B6B6B; margin-top: 3px; }

    .status-ribbon { text-align: center; padding: 5px; margin-bottom: 16px; border-radius: 6px; font-size: 10px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
    .status-paid { background: #F0FDF4; color: #15803D; border: 1px solid #BBF7D0; }
    .status-unpaid { background: #FFF1F0; color: #C0392B; border: 1px solid #FECACA; }
    .status-partial { background: #FFF8E7; color: #B7791F; border: 1px solid #F6E4B0; }

    .info-grid { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin-bottom: 20px; }
    .info-box { background: #F8F5F0; border: 1px solid #E5DDD2; border-radius: 8px; padding: 12px; vertical-align: top; width: 33%; }
    .info-box-title { font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 1.5px; color: #D6B98C; margin-bottom: 8px; }
    .info-label { font-size: 9px; color: #6B6B6B; margin-bottom: 1px; }
    .info-value { font-size: 10px; font-weight: bold; color: #2B2B2B; margin-bottom: 6px; }

    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    .items-table thead tr { background: #2B2520; }
    .items-table thead th { padding: 8px 10px; font-size: 9px; font-weight: bold; color: #D6B98C; text-align: left; text-transform: uppercase; letter-spacing: 0.5px; }
    .items-table thead th.right { text-align: right; }
    .items-table thead th.center { text-align: center; }
    .items-table tbody tr { border-bottom: 1px solid #F0EBE4; }
    .items-table tbody tr.even { background: #FDFBF8; }
    .items-table tbody td { padding: 8px 10px; font-size: 10px; color: #2B2B2B; }
    .items-table tbody td.right { text-align: right; }
    .items-table tbody td.center { text-align: center; }

    .totals-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .totals-box { width: 240px; }
    .total-row { width: 100%; border-bottom: 1px solid #F0EBE4; }
    .total-row td { padding: 4px 0; font-size: 10px; }
    .total-row .label { color: #6B6B6B; }
    .total-row .value { font-weight: 600; color: #2B2B2B; text-align: right; }
    .total-final td { border-top: 2px solid #D6B98C; border-bottom: none; padding-top: 8px; }
    .total-final .label { font-size: 12px; font-weight: bold; color: #2B2B2B; }
    .total-final .value { font-size: 14px; font-weight: bold; color: #D6B98C; text-align: right; }

    .guarantee-box { background: #F8F5F0; border: 1px solid #E5DDD2; border-radius: 8px; padding: 12px; margin-bottom: 16px; }
    .guarantee-title { font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #D6B98C; margin-bottom: 8px; }
    .g-label { font-size: 9px; color: #6B6B6B; }
    .g-value { font-size: 10px; font-weight: bold; color: #2B2B2B; margin-top: 2px; }

    .footer-table { width: 100%; border-collapse: collapse; margin-top: 16px; }
    .footer-table td { vertical-align: top; }
    .notes-title { font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #6B6B6B; margin-bottom: 4px; }
    .notes-text { font-size: 9px; color: #6B6B6B; line-height: 1.6; }

    /* FITUR BARU: "Riwayat Pelayanan" — menggantikan blok tanda tangan lama.
       Sesuai permintaan: tanpa garis/form ttd, cukup nama staf + waktu utk
       tiap tahap layanan (dibuat & dikembalikan), jadi jelas siapa
       menerima vs siapa mengembalikan walau orangnya beda. */
    .service-history { background: #F8F5F0; border: 1px solid #E5DDD2; border-radius: 8px; padding: 12px; }
    .service-history-title { font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 1.5px; color: #D6B98C; margin-bottom: 8px; }
    .service-step { padding-left: 14px; border-left: 2px solid #E5DDD2; padding-bottom: 10px; position: relative; }
    .service-step:last-child { padding-bottom: 0; }
    .service-step-dot { position: absolute; left: -5px; top: 2px; width: 8px; height: 8px; border-radius: 50%; background: #D6B98C; }
    .service-step-role { font-size: 8px; text-transform: uppercase; letter-spacing: 1px; color: #6B6B6B; }
    .service-step-name { font-size: 11px; font-weight: bold; color: #2B2B2B; margin-top: 1px; }
    .service-step-time { font-size: 8px; color: #6B6B6B; margin-top: 1px; }

    /* FITUR BARU (gaya "e-tiket" KAI Access): barcode/QR sekarang jadi
       elemen tersendiri yang besar & benar-benar di tengah halaman, dengan
       garis putus-putus di atasnya seperti sobekan tiket — bukan lagi
       ikon kecil 90x90 yang terjepit di pojok tabel footer. */
    .ticket-stub {
        margin-top: 22px;
        margin-bottom: 22px;
        padding-top: 20px;
        padding-bottom: 20px;
        border-top: 2px dashed #D6B98C;
        border-bottom: 2px dashed #D6B98C;
    }
    .ticket-stub-label {
        font-size: 8px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: #D6B98C;
        margin-bottom: 10px;
        text-align: center;
    }
    .ticket-stub img { width: 150px; height: 150px; }
    .ticket-stub-code {
        font-size: 12px;
        font-weight: bold;
        font-family: 'Courier New', monospace;
        letter-spacing: 2px;
        color: #2B2520;
        margin-top: 10px;
        text-align: center;
    }
    .ticket-stub-hint { font-size: 8px; color: #6B6B6B; margin-top: 3px; text-align: center; }

    .watermark {
        position: absolute;
        top: 40%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-30deg);
        font-size: 80px;
        font-weight: bold;
        color: rgba(214, 185, 140, 0.08);
        text-transform: uppercase;
        letter-spacing: 10px;
        z-index: -1;
        white-space: nowrap;
    }
</style>
</head>
<body>
<div class="page">

    @if($rental->payment_status === 'paid')
    <div class="watermark">LUNAS</div>
    @endif

    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="vertical-align: top;">
                @if($rental->branch->logo)
                    <img src="{{ public_path('storage/' . $rental->branch->logo) }}" height="50" style="margin-bottom: 6px; display: block;">
                @elseif(file_exists(public_path('images/logo.png')))
                    <img src="{{ public_path('images/logo.png') }}" height="50" style="margin-bottom: 6px; display: block;">
                @endif
                <div class="brand-name">{{ config('app.name', 'JasRental') }}</div>
                <div class="brand-tagline">Premium Suit Rental</div>
                <div class="brand-address">
                    {{ $rental->branch->name }}<br>
                    {{ $rental->branch->address }}<br>
                    @if($rental->branch->phone)Telp: {{ $rental->branch->phone }}@endif
                </div>
            </td>
            <td style="vertical-align: top;" class="invoice-badge">
                <div class="invoice-title">Invoice</div>
                <div class="invoice-number">{{ $rental->invoice_number }}</div>
                <div class="invoice-date">Diterbitkan: {{ $rental->created_at->format('d F Y, H:i') }} WIB</div>
            </td>
        </tr>
    </table>

    <!-- Status Ribbon -->
    <div class="status-ribbon {{ match($rental->payment_status) { 'paid' => 'status-paid', 'partial' => 'status-partial', default => 'status-unpaid' } }}">
        {{ match($rental->payment_status) { 'paid' => '&#10003; PEMBAYARAN LUNAS', 'partial' => '&#9889; PEMBAYARAN SEBAGIAN', default => '&#9888; MENUNGGU PEMBAYARAN' } }}
        &nbsp;|&nbsp; Status: {{ $rental->status_label }}
    </div>

    <!-- Info Grid -->
    <table class="info-grid">
        <tr>
            <td class="info-box">
                <div class="info-box-title">Data Customer</div>
                <div class="info-label">Nama Lengkap</div>
                <div class="info-value">{{ $rental->customer->name }}</div>
                <div class="info-label">No. WhatsApp</div>
                <div class="info-value">{{ $rental->customer->phone }}</div>
                @if($rental->customer->address)
                <div class="info-label">Alamat</div>
                <div class="info-value">{{ $rental->customer->address }}</div>
                @endif
                @if($rental->guarantees->count() > 0)
                <div class="info-label">Jenis Jaminan</div>
                <div class="info-value">
                    @foreach($rental->guarantees as $g)
                        {{ $g->type_label }}@if($g->deposit_amount > 0) (Rp {{ number_format($g->deposit_amount, 0, ',', '.') }})@endif{{ !$loop->last ? ', ' : '' }}
                    @endforeach
                </div>
                @endif
            </td>
            <td class="info-box">
                <div class="info-box-title">Jadwal Penyewaan</div>
                <div class="info-label">Tanggal Mulai</div>
                <div class="info-value">{{ $rental->rental_date->format('d F Y') }}</div>
                <div class="info-label">Jatuh Tempo Kembali</div>
                <div class="info-value">{{ $rental->return_due_date->format('d F Y') }}</div>
                <div class="info-label">Durasi</div>
                <div class="info-value">{{ $rental->duration_days }} Hari</div>
                @if($rental->actual_return_date)
                <div class="info-label">Tanggal Dikembalikan</div>
                <div class="info-value">{{ $rental->actual_return_date->format('d F Y') }}</div>
                @endif
            </td>
            <td class="info-box">
                <div class="info-box-title">Informasi Transaksi</div>
                <div class="info-label">Cabang</div>
                <div class="info-value">{{ $rental->branch->name }}</div>
                <div class="info-label">Tanggal Dibuat</div>
                <div class="info-value">{{ $rental->created_at->format('d M Y H:i') }}</div>
                <div class="info-label">Status</div>
                <div class="info-value">{{ $rental->status_label }}</div>
            </td>
        </tr>
    </table>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:30px">#</th>
                <th>Nama Barang</th>
                <th>Ukuran</th>
                <th>Warna</th>
                <th class="center">Qty</th>
                <th class="right">Harga/Hari</th>
                <th class="center">Hari</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rental->items as $i => $item)
            <tr class="{{ $i % 2 === 1 ? 'even' : '' }}">
                <td>{{ $i + 1 }}</td>
                <td><strong>{{ $item->product_name }}</strong></td>
                <td>{{ $item->product_size ?? '-' }}</td>
                <td>{{ $item->product_color ?? '-' }}</td>
                <td class="center">{{ $item->quantity }}</td>
                <td class="right">Rp {{ number_format($item->price_per_day, 0, ',', '.') }}</td>
                <td class="center">{{ $item->duration_days }}</td>
                <td class="right"><strong>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <table class="totals-table">
        <tr>
            <td></td>
            <td style="width: 240px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr class="total-row">
                        <td class="label">Subtotal</td>
                        <td class="value">Rp {{ number_format($rental->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @if(!$rental->has_manual_discount && $rental->discount > 0)
                    <tr class="total-row">
                        <td class="label">Diskon</td>
                        <td class="value" style="color: #E74C3C; text-align: right;">-Rp {{ number_format($rental->discount, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($rental->late_fee > 0)
                    <tr class="total-row">
                        <td class="label">Denda Keterlambatan</td>
                        <td class="value" style="color: #E74C3C; text-align: right;">+Rp {{ number_format($rental->late_fee, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                                        @if($rental->total_damage_fee > 0)
                    <tr class="total-row">
                        <td class="label">Denda Rusak/Hilang</td>
                        <td class="value" style="color: #E74C3C; text-align: right;">+Rp {{ number_format($rental->total_damage_fee, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                                        @if($rental->has_manual_discount)
                    <tr class="total-row">
                        <td class="label">Diskon Manual{{ $rental->discount_name ? " ({$rental->discount_name})" : '' }}</td>
                        <td class="value" style="color: #E74C3C; text-align: right;">-Rp {{ number_format($rental->discount, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr class="total-row total-final">
                        <td class="label">TOTAL</td>
                        <td class="value">Rp {{ number_format($rental->total_amount, 0, ',', '.') }}</td>
                    </tr>
                    @if($rental->paid_amount > 0)
                    <tr class="total-row">
                        <td class="label">Dibayar</td>
                        <td class="value" style="color: #15803D; text-align: right;">Rp {{ number_format($rental->paid_amount, 0, ',', '.') }}</td>
                    </tr>
                    @if($rental->remaining_amount > 0)
                    <tr class="total-row">
                        <td class="label">Sisa Tagihan</td>
                        <td class="value" style="color: #C0392B; text-align: right;">Rp {{ number_format($rental->remaining_amount, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <!-- Notes -->
    @if($rental->notes)
    <div style="background: #FFFCF5; border: 1px solid #F6E4B0; border-radius: 6px; padding: 10px; margin-bottom: 16px;">
        <div style="font-size: 9px; font-weight: bold; color: #B7791F; margin-bottom: 3px; text-transform: uppercase; letter-spacing: 1px;">Catatan</div>
        <div style="font-size: 10px; color: #2B2B2B;">{{ $rental->notes }}</div>
    </div>
    @endif

    {{-- FITUR BARU (urutan diubah sesuai permintaan): ticket-stub QR
         sekarang berada DI TENGAH dokumen — persis setelah tabel
         barang/total, SEBELUM syarat & ketentuan + riwayat pelayanan
         (yang sekarang jadi footer paling bawah). Gaya e-tiket KAI Access:
         barcode besar, benar-benar di tengah halaman, dengan garis
         putus-putus di atas (seperti sobekan tiket), plus nomor invoice
         diulang di bawahnya (mirip kode booking di bawah barcode boarding
         pass).
         FIX centering: dompdf kadang tidak konsisten menghormati
         text-align:center pada tag gambar langsung di dalam sebuah div —
         dipakai <table> 1 kolom dengan align="center" bawaan HTML, cara
         paling reliable untuk benar-benar center di dompdf. --}}
    @if($rental->qr_code)
    <div class="ticket-stub">
        <div class="ticket-stub-label">Tiket Verifikasi Transaksi</div>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td align="center">
                    <img src="{{ storage_path('app/public/' . $rental->qr_code) }}" alt="QR Code">
                </td>
            </tr>
        </table>
        <div class="ticket-stub-code">{{ $rental->invoice_number }}</div>
        <div class="ticket-stub-hint">Scan kode ini untuk verifikasi atau proses pengembalian barang</div>
    </div>
    @endif

    <!-- Footer: syarat & ketentuan + riwayat pelayanan (paling bawah) -->
    <table class="footer-table">
        <tr>
            <td style="width: 52%;">
                <div class="notes-title">Syarat &amp; Ketentuan</div>
                <div class="notes-text">
                    1. Barang wajib dikembalikan sesuai tanggal jatuh tempo.<br>
                    2. Keterlambatan pengembalian dikenakan denda 50% per hari.<br>
                    3. Kerusakan / kehilangan menjadi tanggung jawab penyewa.<br>
                    4. Jaminan dikembalikan setelah barang kembali dalam kondisi baik.
                </div>
            </td>
            <td style="width: 4%;"></td>
            <td style="width: 44%;">
                {{-- "Riwayat Pelayanan" — tanpa form/garis tanda tangan sama
                     sekali (sesuai permintaan), cukup nama + waktu untuk
                     tiap tahap. Kalau barang belum dikembalikan, cuma tahap
                     "Dibuat" yang tampil. Kalau staf pembuat & staf
                     pengembali sama, tetap ditampilkan sebagai 2 baris
                     terpisah supaya riwayatnya konsisten & jelas urutannya. --}}
                <div class="service-history">
                    <div class="service-history-title">Riwayat Pelayanan</div>

                    <div class="service-step">
                        <div class="service-step-dot"></div>
                        <div class="service-step-role">Dibuat oleh</div>
                        <div class="service-step-name">{{ $rental->createdBy->name }}</div>
                        <div class="service-step-time">{{ $rental->created_at->format('d M Y, H:i') }} WIB</div>
                    </div>

                    @if($rental->returnedBy)
                    <div class="service-step" style="border-left-color: #D6B98C;">
                        <div class="service-step-dot" style="background: #15803D;"></div>
                        <div class="service-step-role">Dikembalikan oleh</div>
                        <div class="service-step-name">{{ $rental->returnedBy->name }}</div>
                        <div class="service-step-time">{{ $rental->returned_at?->format('d M Y, H:i') }} WIB</div>
                    </div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- Bottom line -->
    <div style="margin-top: 20px; border-top: 1px dashed #D6B98C; padding-top: 10px; text-align: center;">
        <div style="font-size: 9px; color: #6B6B6B;">
            Dokumen ini dicetak secara otomatis oleh sistem {{ config('app.name') }}
            &nbsp;|&nbsp; {{ now()->format('d M Y H:i') }} WIB
        </div>
    </div>

</div>
</body>
</html>