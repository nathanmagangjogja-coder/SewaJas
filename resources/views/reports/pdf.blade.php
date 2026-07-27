<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        h2, h4 { text-align: center; margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 5px 7px; text-align: left; vertical-align: top; }
        th { background: #f0f0f0; }
        .text-right { text-align: right; }
        .badge { padding: 2px 6px; border-radius: 3px; font-size: 10px; }
    </style>
</head>
<body>
    <h2>Laporan Penyewaan</h2>
    <h4>{{ now()->format('d/m/Y H:i') }}</h4>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Invoice</th>
                <th>Customer</th>
                <th>Cabang</th>
                <th>Tgl Sewa</th>
                <th>Tgl Kembali</th>
                <th>Barang</th>
                <th>Status</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reports as $i => $r)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $r->invoice_number }}</td>
                <td>{{ $r->customer->name ?? '-' }}</td>
                <td>{{ $r->branch->name ?? '-' }}</td>
                <td>{{ $r->rental_date?->format('d/m/Y') ?? '-' }}</td>
                <td>{{ $r->return_due_date?->format('d/m/Y') ?? '-' }}</td>
                <td>
                    @foreach($r->items as $item)
                        {{ $item->product_name }} x{{ $item->quantity }}<br>
                    @endforeach
                </td>
                <td>{{ $r->rental_status }}</td>
                <td class="text-right">Rp {{ number_format($r->total_amount, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="8"><strong>Total Keseluruhan</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($reports->sum('total_amount'), 0, ',', '.') }}</strong></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>