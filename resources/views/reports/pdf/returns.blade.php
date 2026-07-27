<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size:10px; color:#1a1a2e; }
    .header { background:#1a1a2e; color:white; padding:14px 20px; margin-bottom:16px; }
    .header h1 { font-size:16px; font-weight:bold; letter-spacing:1px; }
    .header p  { font-size:9px; opacity:0.7; margin-top:3px; }
    .meta { display:flex; gap:20px; padding:0 20px 12px; font-size:9px; color:#666; }
    .badge-red    { background:#FEF2F2; color:#DC2626; padding:2px 6px; border-radius:4px; font-size:8px; font-weight:bold; }
    .badge-amber  { background:#FFFBEB; color:#D97706; padding:2px 6px; border-radius:4px; font-size:8px; font-weight:bold; }
    .badge-green  { background:#F0FDF4; color:#16A34A; padding:2px 6px; border-radius:4px; font-size:8px; font-weight:bold; }
    .summary { display:flex; gap:12px; padding:0 20px 14px; }
    .stat-box { flex:1; border:1px solid #e5e7eb; border-radius:6px; padding:10px 12px; }
    .stat-box .label { font-size:8px; color:#9ca3af; text-transform:uppercase; letter-spacing:0.5px; }
    .stat-box .value { font-size:16px; font-weight:bold; color:#1a1a2e; margin-top:3px; }
    .stat-box.warning .value { color:#D97706; }
    .stat-box.danger  .value { color:#DC2626; }
    table { width:100%; border-collapse:collapse; font-size:9px; margin:0 20px; width:calc(100% - 40px); }
    thead tr { background:#1a1a2e; color:white; }
    thead th { padding:7px 8px; text-align:left; font-size:8px; text-transform:uppercase; letter-spacing:0.5px; }
    tbody tr { border-bottom:1px solid #f3f4f6; }
    tbody tr:nth-child(even) { background:#fafafa; }
    tbody tr.late-row { background:#fffbeb; }
    tbody td { padding:6px 8px; vertical-align:top; }
    .section-title { padding:8px 20px 4px; font-size:11px; font-weight:bold; color:#1a1a2e;
                     border-top:2px solid #1a1a2e; margin-top:14px; }
    .footer { margin-top:16px; padding:10px 20px 0; border-top:1px solid #e5e7eb;
              font-size:8px; color:#9ca3af; display:flex; justify-content:space-between; }
    .text-right { text-align:right; }
    .font-bold  { font-weight:bold; }
    .text-amber { color:#D97706; }
    .text-red   { color:#DC2626; }
    .text-green { color:#16A34A; }
    .pkg-tag { background:#EDE9FE; color:#7C3AED; padding:1px 4px; border-radius:3px; font-size:7px; }
</style>
</head>
<body>

<div class="header">
    <h1>LAPORAN PENGEMBALIAN JAS</h1>
    <p>
        {{ $branchName }}
        @if($dateFrom || $dateTo)
            &nbsp;·&nbsp; Periode: {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : '—' }}
            s/d {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : '—' }}
        @endif
    </p>
</div>

{{-- Summary --}}
<div class="summary">
    <div class="stat-box">
        <div class="label">Total Dikembalikan</div>
        <div class="value">{{ $rentals->count() }}</div>
    </div>
    <div class="stat-box warning">
        <div class="label">Pernah Terlambat</div>
        <div class="value">{{ $rentals->where('late_fee', '>', 0)->count() }}</div>
    </div>
    <div class="stat-box warning">
        <div class="label">Total Denda Terkumpul</div>
        <div class="value">Rp {{ number_format($totalFee, 0, ',', '.') }}</div>
    </div>
    <div class="stat-box">
        <div class="label">Rata-rata Hari Telat</div>
        @php $avg = $rentals->where('overdue_days', '>', 0)->avg('overdue_days'); @endphp
        <div class="value">{{ $avg ? number_format($avg, 1) : '0' }} hari</div>
    </div>
</div>

{{-- Tabel --}}
<div class="section-title">Riwayat Pengembalian</div>

<table>
    <thead>
        <tr>
            <th>Invoice</th>
            <th>Customer</th>
            <th>Paket</th>
            <th>Tgl Sewa</th>
            <th>Jatuh Tempo</th>
            <th>Tgl Kembali</th>
            <th class="text-right">Subtotal</th>
            <th class="text-right">Denda</th>
            <th class="text-right">Total</th>
            <th>Ket.</th>
        </tr>
    </thead>
    <tbody>
        @php $totalSubtotal = 0; $totalDenda = 0; $totalNilai = 0; @endphp
        @forelse($rentals as $r)
        @php
            $isLate = $r->late_fee > 0;
            $totalSubtotal += $r->subtotal;
            $totalDenda    += $r->late_fee;
            $totalNilai    += $r->total_amount;
        @endphp
        <tr class="{{ $isLate ? 'late-row' : '' }}">
            <td class="font-bold" style="color:#5B3A8A">{{ $r->invoice_number }}</td>
            <td>{{ $r->customer?->name ?? '-' }}</td>
            <td>
                @if($r->package)
                <span class="pkg-tag">{{ $r->package->name }}</span>
                @else
                <span style="color:#9ca3af">—</span>
                @endif
            </td>
            <td>{{ $r->rental_date?->format('d/m/Y') }}</td>
            <td>{{ $r->return_due_date?->format('d/m/Y') }}</td>
            <td class="{{ $isLate ? 'text-red' : '' }}">
                {{ $r->actual_return_date?->format('d/m/Y') ?? '-' }}
                @if($r->overdue_days > 0)
                    <br><span class="badge-red">+{{ $r->overdue_days }} hari</span>
                @endif
            </td>
            <td class="text-right">Rp {{ number_format($r->subtotal, 0, ',', '.') }}</td>
            <td class="text-right {{ $isLate ? 'text-amber font-bold' : '' }}">
                {{ $isLate ? 'Rp ' . number_format($r->late_fee, 0, ',', '.') : '-' }}
                @if($isLate && $r->package)
                    <br><span style="color:#9ca3af; font-size:7px">
                        {{ number_format($r->package->penalty_percent, 0) }}%/hari
                    </span>
                @endif
            </td>
            <td class="text-right font-bold">Rp {{ number_format($r->total_amount, 0, ',', '.') }}</td>
            <td>{{ $r->returnRecord?->condition ? ucfirst($r->returnRecord->condition) : '-' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="10" style="text-align:center; padding:20px; color:#9ca3af">
                Tidak ada data pengembalian
            </td>
        </tr>
        @endforelse

        {{-- Total row --}}
        @if($rentals->count() > 0)
        <tr style="background:#1a1a2e; color:white; font-weight:bold;">
            <td colspan="6" style="padding:7px 8px; font-size:9px;">TOTAL ({{ $rentals->count() }} transaksi)</td>
            <td class="text-right" style="padding:7px 8px;">Rp {{ number_format($totalSubtotal, 0, ',', '.') }}</td>
            <td class="text-right text-amber" style="padding:7px 8px; color:#FCD34D;">
                Rp {{ number_format($totalDenda, 0, ',', '.') }}
            </td>
            <td class="text-right" style="padding:7px 8px;">Rp {{ number_format($totalNilai, 0, ',', '.') }}</td>
            <td></td>
        </tr>
        @endif
    </tbody>
</table>

<div class="footer">
    <span>Dicetak: {{ $generatedAt }}</span>
    <span>Sistem: MonsieurJas · Laporan Pengembalian</span>
</div>

</body>
</html>
