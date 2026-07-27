<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Nota Thermal - {{ $rental->invoice_number }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    @media print {
        @page {
            size: 80mm auto;
            margin: 0;
        }
        body { width: 80mm; }
        .no-print { display: none !important; }
        .print-only { display: block !important; }
    }

    body {
        font-family: 'Courier New', Courier, monospace;
        font-size: 11px;
        color: #000000;
        background: #ffffff;
        width: 80mm;
        margin: 0 auto;
        padding: 6px 8px;
    }

    .center { text-align: center; }
    .right { text-align: right; }
    .left { text-align: left; }
    .bold { font-weight: bold; }
    .divider { border-top: 1px dashed #000; margin: 5px 0; }
    .divider-solid { border-top: 1px solid #000; margin: 5px 0; }
    .row { display: flex; justify-content: space-between; }
    .mt1 { margin-top: 4px; }
    .mt2 { margin-top: 8px; }

    .header-brand { font-size: 16px; font-weight: bold; text-align: center; letter-spacing: 1px; }
    .header-sub { font-size: 9px; text-align: center; margin-top: 2px; }

    .invoice-no { font-size: 12px; font-weight: bold; text-align: center; margin-top: 4px; }

    table { width: 100%; border-collapse: collapse; }
    table td { font-size: 10px; vertical-align: top; padding: 1px 0; }
    table td:last-child { text-align: right; }

    .items-table tr { border-bottom: 1px dotted #ccc; }
    .item-name { font-weight: bold; }
    .item-detail { font-size: 9px; color: #444; }
    .item-amount { font-weight: bold; }

    .total-section { margin-top: 4px; }
    .total-row { display: flex; justify-content: space-between; font-size: 11px; padding: 1px 0; }
    .grand-total { font-size: 14px; font-weight: bold; border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 4px 0; margin: 4px 0; }

    .qr-area { text-align: center; margin: 8px 0; }
    .qr-area img { width: 80px; height: 80px; }
    .qr-label { font-size: 8px; margin-top: 2px; }

    .footer-msg { font-size: 9px; text-align: center; margin-top: 8px; line-height: 1.5; }
    .print-btn-area { text-align: center; padding: 20px; }

    .status-badge {
        text-align: center;
        padding: 3px;
        font-weight: bold;
        font-size: 11px;
        margin: 4px 0;
        border: 1px solid #000;
    }
</style>
</head>
<body>

    <!-- Preview Actions (hidden on print) -->
    <div class="no-print" style="background: #F8F5F0; padding: 12px; text-align: center; margin-bottom: 16px; border-radius: 8px;">
        <p style="font-size: 11px; color: #6B6B6B; margin-bottom: 8px; font-family: Inter, sans-serif;">Preview Nota Thermal (80mm)</p>
        <button onclick="window.print()" style="background: linear-gradient(135deg, #D6B98C, #C4A478); color: #1E1A16; border: none; padding: 8px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; margin-right: 8px;">🖨️ Cetak</button>
        <button onclick="window.close()" style="background: #E8DED1; color: #2B2B2B; border: none; padding: 8px 20px; border-radius: 6px; cursor: pointer;">✕ Tutup</button>
    </div>

    <!-- ===== THERMAL RECEIPT ===== -->

    <div class="header-brand">{{ config('app.name', 'JASRENTAL') }}</div>
    <div class="header-sub">Premium Suit Rental</div>
    <div class="header-sub">{{ $rental->branch->name }}</div>
    @if($rental->branch->phone)
    <div class="header-sub">{{ $rental->branch->phone }}</div>
    @endif

    <div class="divider-solid mt2"></div>

    <div class="invoice-no">{{ $rental->invoice_number }}</div>
    <div class="center" style="font-size: 9px; margin-top: 2px;">{{ $rental->created_at->format('d/m/Y H:i') }} | {{ $rental->createdBy->name }}</div>

    <div class="divider"></div>

    <!-- Customer Info -->
    <table>
        <tr><td>Customer</td><td style="text-align:right; font-weight:bold">{{ $rental->customer->name }}</td></tr>
        <tr><td>HP</td><td style="text-align:right">{{ $rental->customer->phone }}</td></tr>
        <tr><td>Tgl Sewa</td><td style="text-align:right">{{ $rental->rental_date->format('d/m/Y') }}</td></tr>
        <tr><td>Tgl Kembali</td><td style="text-align:right; font-weight:bold">{{ $rental->return_due_date->format('d/m/Y') }}</td></tr>
        <tr><td>Durasi</td><td style="text-align:right">{{ $rental->duration_days }} hari</td></tr>
        @if($rental->package)
        <tr><td>Paket</td><td style="text-align:right">{{ $rental->package->name }}</td></tr>
        @endif
    </table>

    <div class="divider"></div>

    <!-- Items -->
    <div class="bold" style="margin-bottom: 3px;">BARANG DISEWA:</div>
    <table class="items-table">
        @foreach($rental->items as $item)
        <tr>
            <td style="padding: 3px 0;">
                <div class="item-name">{{ $item->product_name }}</div>
                <div class="item-detail">Uk: {{ $item->product_size ?? '-' }} | Qty: {{ $item->quantity }}</div>
                <div class="item-detail">{{ $item->quantity }} × Rp {{ number_format($item->price_per_day, 0, ',', '.') }} × {{ $item->duration_days }}hr</div>
            </td>
            <td class="item-amount">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </table>

    <div class="divider"></div>

    <!-- Totals -->
    <div class="total-section">
        <div class="total-row">
            <span>Subtotal</span>
            <span>Rp {{ number_format($rental->subtotal, 0, ',', '.') }}</span>
        </div>
        @if(!$rental->has_manual_discount && $rental->discount > 0)
        <div class="total-row">
            <span>Diskon</span>
            <span>-Rp {{ number_format($rental->discount, 0, ',', '.') }}</span>
        </div>
        @endif
        @if($rental->late_fee > 0)
        <div class="total-row">
            <span>Denda</span>
            <span>+Rp {{ number_format($rental->late_fee, 0, ',', '.') }}</span>
        </div>
        @endif
        {{-- BARU: Denda Barang Rusak/Hilang --}}
        @if($rental->total_damage_fee > 0)
        <div class="total-row">
            <span>Denda Rusak/Hilang</span>
            <span>+Rp {{ number_format($rental->total_damage_fee, 0, ',', '.') }}</span>
        </div>
        @endif
        {{-- BARU: Diskon Manual (dari proses retur) --}}
        @if($rental->has_manual_discount)
        <div class="total-row">
            <span>Diskon Manual</span>
            <span>-Rp {{ number_format($rental->discount, 0, ',', '.') }}</span>
        </div>
        @endif
        <div class="total-row grand-total">
            <span>TOTAL</span>
            <span>Rp {{ number_format($rental->total_amount, 0, ',', '.') }}</span>
        </div>
        @if($rental->paid_amount > 0)
        <div class="total-row">
            <span>Dibayar</span>
            <span>Rp {{ number_format($rental->paid_amount, 0, ',', '.') }}</span>
        </div>
        @if($rental->remaining_amount > 0)
        <div class="total-row bold">
            <span>Sisa</span>
            <span>Rp {{ number_format($rental->remaining_amount, 0, ',', '.') }}</span>
        </div>
        @endif
        @endif
    </div>

    <!-- Status -->
    <div class="status-badge">
        [{{ strtoupper($rental->status_label) }}] [{{ strtoupper($rental->payment_status_label) }}]
    </div>

    <!-- Guarantee -->
    @if($rental->guarantees->count() > 0)
    <div class="divider"></div>
    <div class="bold" style="margin-bottom: 2px;">JAMINAN:</div>
    @foreach($rental->guarantees as $g)
    <table>
        <tr><td>Jenis</td><td style="text-align:right">{{ $g->type_label }}</td></tr>
        @if($g->id_number)<tr><td>Nomor</td><td style="text-align:right">{{ $g->id_number }}</td></tr>@endif
        @if($g->id_name)<tr><td>Nama</td><td style="text-align:right">{{ $g->id_name }}</td></tr>@endif
        @if($g->deposit_amount > 0)<tr><td>Deposit</td><td style="text-align:right">Rp {{ number_format($g->deposit_amount, 0, ',', '.') }}</td></tr>@endif
    </table>
    @endforeach
    @endif

    <!-- QR Code -->
    @if($rental->qr_code)
    <div class="divider"></div>
    <div class="qr-area">
        <img src="{{ asset('storage/' . $rental->qr_code) }}" alt="QR Code">
        <div class="qr-label">Scan untuk verifikasi transaksi</div>
        <div class="qr-label bold">{{ $rental->invoice_number }}</div>
    </div>
    @endif

    <div class="divider"></div>

    <div class="footer-msg">
        Harap kembalikan barang<br>
        tepat waktu: <strong>{{ $rental->return_due_date->format('d/m/Y') }}</strong><br>
        Keterlambatan = denda {{ $rental->package ? number_format($rental->package->penalty_percent, 0) : '50' }}%/hari<br>
        <br>
        Terima kasih telah mempercayai<br>
        <strong>{{ config('app.name') }}</strong>
    </div>

    <div style="margin-top: 16px; margin-bottom: 8px; text-align: center; font-size: 8px; color: #999;">
        *** {{ now()->format('d/m/Y H:i') }} ***
    </div>

</body>
</html>