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

    .footer-table { width: 100%; border-collapse: collapse; border-top: 1px solid #E5DDD2; padding-top: 16px; margin-top: 16px; }
    .footer-table td { vertical-align: bottom; padding-top: 16px; }
    .notes-title { font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #6B6B6B; margin-bottom: 4px; }
    .notes-text { font-size: 9px; color: #6B6B6B; line-height: 1.6; }
    .signature-box { border-bottom: 1px solid #2B2B2B; height: 50px; margin-bottom: 6px; }
    .signature-label { font-size: 9px; color: #6B6B6B; text-align: center; }
    .qr-label { font-size: 8px; color: #6B6B6B; margin-top: 4px; text-align: center; }

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
                <div class="info-label">Diproses Oleh</div>
                <div class="info-value">{{ $rental->createdBy->name }}</div>
                <div class="info-label">Cabang</div>
                <div class="info-value">{{ $rental->branch->name }}</div>
                <div class="info-label">Tanggal Dibuat</div>
                <div class="info-value">{{ $rental->created_at->format('d M Y H:i') }}</div>
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
                    {{-- BARU: Denda Barang Rusak/Hilang --}}
                    @if($rental->total_damage_fee > 0)
                    <tr class="total-row">
                        <td class="label">Denda Rusak/Hilang</td>
                        <td class="value" style="color: #E74C3C; text-align: right;">+Rp {{ number_format($rental->total_damage_fee, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    {{-- BARU: Diskon Manual (dari proses retur) --}}
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

    <!-- Guarantee -->
    @if($rental->guarantees->count() > 0)
    <div class="guarantee-box">
        <div class="guarantee-title">Informasi Jaminan</div>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                @foreach($rental->guarantees as $g)
                <td style="padding-right: 24px; vertical-align: top;">
                    <div class="g-label">Jenis Jaminan</div>
                    <div class="g-value">{{ $g->type_label }}</div>
                </td>
                @if($g->id_number)
                <td style="padding-right: 24px; vertical-align: top;">
                    <div class="g-label">Nomor</div>
                    <div class="g-value">{{ $g->id_number }}</div>
                </td>
                @endif
                @if($g->id_name)
                <td style="padding-right: 24px; vertical-align: top;">
                    <div class="g-label">Atas Nama</div>
                    <div class="g-value">{{ $g->id_name }}</div>
                </td>
                @endif
                @if($g->deposit_amount > 0)
                <td style="padding-right: 24px; vertical-align: top;">
                    <div class="g-label">Nominal Deposit</div>
                    <div class="g-value">Rp {{ number_format($g->deposit_amount, 0, ',', '.') }}</div>
                </td>
                @endif
                <td style="padding-right: 24px; vertical-align: top;">
                    <div class="g-label">Status</div>
                    <div class="g-value">{{ match($g->status) { 'held' => 'Ditahan', 'returned' => 'Dikembalikan', 'forfeited' => 'Disita', default => $g->status } }}</div>
                </td>
                @endforeach
            </tr>
        </table>
    </div>
    @endif

    <!-- Notes -->
    @if($rental->notes)
    <div style="background: #FFFCF5; border: 1px solid #F6E4B0; border-radius: 6px; padding: 10px; margin-bottom: 16px;">
        <div style="font-size: 9px; font-weight: bold; color: #B7791F; margin-bottom: 3px; text-transform: uppercase; letter-spacing: 1px;">Catatan</div>
        <div style="font-size: 10px; color: #2B2B2B;">{{ $rental->notes }}</div>
    </div>
    @endif

    <!-- Footer -->
    <table class="footer-table">
        <tr>
            <td style="vertical-align: bottom;">
                <div class="notes-title">Syarat &amp; Ketentuan</div>
                <div class="notes-text">
                    1. Barang wajib dikembalikan sesuai tanggal jatuh tempo.<br>
                    2. Keterlambatan pengembalian dikenakan denda 50% per hari.<br>
                    3. Kerusakan / kehilangan menjadi tanggung jawab penyewa.<br>
                    4. Jaminan dikembalikan setelah barang kembali dalam kondisi baik.
                </div>
            </td>
            @if($rental->qr_code)
            <td style="width: 90px; text-align: center; vertical-align: bottom;">
                <img src="{{ storage_path('app/public/' . $rental->qr_code) }}" width="70" height="70">
                <div class="qr-label">Scan untuk verifikasi</div>
            </td>
            @endif
            <td style="width: 150px; text-align: center; vertical-align: bottom;">
                <div class="signature-box"></div>
                <div class="signature-label">
                    ( {{ $rental->createdBy->name }} )<br>
                    Petugas / Admin
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