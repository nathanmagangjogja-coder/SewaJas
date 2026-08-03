<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Simulasi Pembayaran QRIS - {{ $rental->invoice_number }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: -apple-system, 'Segoe UI', Roboto, sans-serif;
        background: linear-gradient(135deg, #F8F5F0, #EFE8DC);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .card {
        background: #fff;
        border-radius: 20px;
        max-width: 380px;
        width: 100%;
        padding: 32px 24px;
        text-align: center;
        box-shadow: 0 10px 40px rgba(0,0,0,0.08);
    }
    .badge {
        display: inline-block;
        background: #FEF3C7;
        color: #92400E;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.5px;
        padding: 4px 12px;
        border-radius: 999px;
        margin-bottom: 16px;
        text-transform: uppercase;
    }
    .icon-circle {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: linear-gradient(135deg, #D6B98C, #C4A478);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
        font-size: 30px;
    }
    h1 { font-size: 18px; color: #1E1A16; margin-bottom: 6px; }
    p { font-size: 13px; color: #6B6B6B; line-height: 1.6; margin-bottom: 4px; }
    .amount { font-size: 26px; font-weight: 700; color: #1E1A16; margin: 16px 0 4px; }
    .invoice { font-size: 12px; color: #A78355; font-weight: 600; margin-bottom: 20px; }
    .note {
        background: #F8F5F0;
        border-radius: 10px;
        padding: 12px;
        font-size: 11px;
        color: #6B6B6B;
        line-height: 1.6;
        text-align: left;
    }
    .note strong { color: #1E1A16; }
</style>
</head>
<body>
    <div class="card">
        <span class="badge">Mode Simulasi</span>
        <div class="icon-circle">✓</div>
        <h1>Halaman Simulasi QRIS</h1>
        <p>QR code ini berhasil discan.</p>
        <div class="amount">Rp {{ number_format($rental->total_amount, 0, ',', '.') }}</div>
        <div class="invoice">{{ $rental->invoice_number }}</div>
        <div class="note">
            <strong>Catatan:</strong> Ini halaman simulasi/demo — belum terhubung ke payment gateway QRIS sungguhan. Pembayaran tetap dikonfirmasi manual oleh kasir di sistem.
        </div>
    </div>
</body>
</html>
