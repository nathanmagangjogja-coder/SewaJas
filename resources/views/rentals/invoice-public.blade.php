<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $rental->invoice_number }} — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f5f3ef; }
        .font-playfair { font-family: 'Playfair Display', serif; }
        .elegant-table { width: 100%; border-collapse: collapse; }
        .elegant-table thead tr { border-bottom: 1px solid #E8E0D5; }
        .elegant-table thead th { padding: 10px 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6B6B6B; }
        .elegant-table tbody tr { border-bottom: 1px solid #F0EBE3; }
        .elegant-table tbody td { padding: 12px; font-size: 13px; color: #2B2B2B; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 9999px; font-size: 11px; font-weight: 600; }
        .badge-green { background: #dcfce7; color: #15803d; }
        .badge-yellow { background: #fef9c3; color: #a16207; }
        .badge-red { background: #fee2e2; color: #b91c1c; }
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .card { box-shadow: none !important; }
        }
    </style>
</head>
<body class="min-h-screen py-6 px-4">

    {{-- Action Bar (tidak muncul saat print) --}}
    <div class="no-print max-w-4xl mx-auto mb-4 flex items-center justify-between">
        <div class="flex items-center gap-2">
            {{-- Logo / Brand --}}
            <span class="font-playfair text-lg font-bold" style="color: #2B2520">{{ config('app.name') }}</span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('rentals.pdf', $rental) }}"
               class="flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
               style="background: #f0ebe3; color: #2B2520">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Download PDF
            </a>
            <button onclick="window.print()"
                    class="flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium text-white transition-colors"
                    style="background: #D6B98C">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print
            </button>
        </div>
    </div>

    {{-- Invoice Card --}}
    <div class="max-w-4xl mx-auto bg-white rounded-2xl shadow-lg overflow-hidden card" id="invoice-content">
        <div class="p-8">

            {{-- Header --}}
            <div class="flex justify-between items-start pb-6 border-b-2" style="border-color: #D6B98C">
                <div>
                    <h1 class="font-playfair text-2xl font-bold" style="color: #2B2520">{{ config('app.name') }}</h1>
                    <p class="text-xs font-semibold tracking-widest mt-1" style="color: #D6B98C">PREMIUM SUIT RENTAL</p>
                    <div class="mt-3 text-xs leading-6" style="color: #6B6B6B">
                        <p class="font-semibold" style="color: #2B2B2B">{{ $rental->branch->name }}</p>
                        <p>{{ $rental->branch->address }}</p>
                        @if($rental->branch->phone)<p>Telp: {{ $rental->branch->phone }}</p>@endif
                        @if($rental->branch->email)<p>Email: {{ $rental->branch->email }}</p>@endif
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-3xl font-bold tracking-wider" style="color: #D6B98C">INVOICE</p>
                    <p class="font-mono font-bold text-lg mt-1" style="color: #2B2B2B">{{ $rental->invoice_number }}</p>
                    <p class="text-xs mt-1" style="color: #6B6B6B">{{ $rental->created_at->format('d F Y, H:i') }} WIB</p>
                    <div class="mt-3 inline-block px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider
                        {{ match($rental->payment_status) { 'paid' => 'bg-green-100 text-green-700', 'partial' => 'bg-yellow-100 text-yellow-700', default => 'bg-red-100 text-red-600' } }}">
                        {{ $rental->payment_status_label }}
                    </div>
                </div>
            </div>

            {{-- Info Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 py-6 border-b" style="border-color: #F0EBE3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider mb-3" style="color: #D6B98C">Tagihan Kepada</p>
                    <p class="font-bold" style="color: #2B2B2B">{{ $rental->customer->name }}</p>
                    <p class="text-sm mt-1" style="color: #6B6B6B">{{ $rental->customer->phone }}</p>
                    @if($rental->customer->address)
                    <p class="text-sm mt-1 leading-5" style="color: #6B6B6B">{{ $rental->customer->address }}</p>
                    @endif
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider mb-3" style="color: #D6B98C">Jadwal</p>
                    <div class="space-y-2">
                        <div>
                            <p class="text-xs" style="color: #6B6B6B">Tanggal Mulai</p>
                            <p class="font-semibold text-sm" style="color: #2B2B2B">{{ $rental->rental_date->format('d F Y') }}</p>
                        </div>
                        <div>
                            <p class="text-xs" style="color: #6B6B6B">Jatuh Tempo</p>
                            <p class="font-semibold text-sm" style="color: #2B2B2B">{{ $rental->return_due_date->format('d F Y') }}</p>
                        </div>
                        <div>
                            <p class="text-xs" style="color: #6B6B6B">Durasi</p>
                            <p class="font-semibold text-sm" style="color: #2B2B2B">{{ $rental->duration_days }} Hari</p>
                        </div>
                    </div>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider mb-3" style="color: #D6B98C">Info Transaksi</p>
                    <div class="space-y-2">
                        <div>
                            <p class="text-xs" style="color: #6B6B6B">Diproses Oleh</p>
                            <p class="font-semibold text-sm" style="color: #2B2B2B">{{ $rental->createdBy->name }}</p>
                        </div>
                        <div>
                            <p class="text-xs" style="color: #6B6B6B">Cabang</p>
                            <p class="font-semibold text-sm" style="color: #2B2B2B">{{ $rental->branch->name }}</p>
                        </div>
                        <div>
                            <p class="text-xs" style="color: #6B6B6B">Status</p>
                            <span class="badge badge-{{ $rental->status_badge_color }} mt-0.5">{{ $rental->status_label }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Items Table --}}
            <div class="py-6 overflow-x-auto">
                <table class="elegant-table">
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
                            <td style="color: #6B6B6B">{{ $i + 1 }}</td>
                            <td>
                                <p class="font-semibold" style="color: #2B2B2B">{{ $item->product_name }}</p>
                                @if($item->is_returned)
                                <span class="badge badge-green mt-0.5">Sudah Dikembalikan</span>
                                @endif
                            </td>
                            <td style="color: #6B6B6B">{{ $item->product_size ?? '-' }}</td>
                            <td style="color: #6B6B6B">{{ $item->product_color ?? '-' }}</td>
                            <td class="text-center font-semibold" style="color: #2B2B2B">{{ $item->quantity }}</td>
                            <td class="text-right" style="color: #2B2B2B">Rp {{ number_format($item->price_per_day, 0, ',', '.') }}</td>
                            <td class="text-center" style="color: #2B2B2B">{{ $item->duration_days }}</td>
                            <td class="text-right font-bold" style="color: #2B2B2B">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Totals + QR --}}
            <div class="flex justify-between items-end pt-4 border-t" style="border-color: #F0EBE3">
                @if($rental->qr_code)
                <div class="text-center">
                    <img src="{{ asset('storage/' . $rental->qr_code) }}" alt="QR" class="w-24 h-24 mx-auto">
                    <p class="text-xs mt-1" style="color: #6B6B6B">Scan untuk verifikasi</p>
                </div>
                @else
                <div></div>
                @endif

                <div class="w-64">
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span style="color: #6B6B6B">Subtotal</span>
                            <span style="color: #2B2B2B">Rp {{ number_format($rental->subtotal, 0, ',', '.') }}</span>
                        </div>
                        @if(!$rental->has_manual_discount && $rental->discount > 0)
                        <div class="flex justify-between text-sm">
                            <span style="color: #6B6B6B">Diskon</span>
                            <span style="color: #E74C3C">-Rp {{ number_format($rental->discount, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        @if($rental->late_fee > 0)
                        <div class="flex justify-between text-sm">
                            <span style="color: #6B6B6B">Denda Keterlambatan</span>
                            <span style="color: #E74C3C">+Rp {{ number_format($rental->late_fee, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        {{-- BARU: Denda Barang Rusak/Hilang --}}
                        @if($rental->total_damage_fee > 0)
                        <div class="flex justify-between text-sm">
                            <span style="color: #6B6B6B">Denda Rusak/Hilang</span>
                            <span style="color: #E74C3C">+Rp {{ number_format($rental->total_damage_fee, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        {{-- BARU: Diskon Manual (dari proses retur) --}}
                        @if($rental->has_manual_discount)
                        <div class="flex justify-between text-sm">
                            <span style="color: #6B6B6B">
                                Diskon Manual{{ $rental->discount_name ? " ({$rental->discount_name})" : '' }}
                            </span>
                            <span style="color: #E74C3C">-Rp {{ number_format($rental->discount, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between items-center pt-3 border-t-2" style="border-color: #D6B98C">
                            <span class="font-bold text-base" style="color: #2B2B2B">TOTAL</span>
                            <span class="font-bold text-xl font-playfair" style="color: #D6B98C">Rp {{ number_format($rental->total_amount, 0, ',', '.') }}</span>
                        </div>
                        @if($rental->paid_amount > 0)
                        <div class="flex justify-between text-sm">
                            <span style="color: #6B6B6B">Dibayar</span>
                            <span style="color: #15803D; font-weight: 600">Rp {{ number_format($rental->paid_amount, 0, ',', '.') }}</span>
                        </div>
                        @if($rental->remaining_amount > 0)
                        <div class="flex justify-between text-sm font-bold">
                            <span style="color: #C0392B">Sisa Tagihan</span>
                            <span style="color: #C0392B">Rp {{ number_format($rental->remaining_amount, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        @endif
                    </div>
                </div>
            </div>

            {{-- Terms & Signature --}}
            <div class="flex flex-col sm:flex-row justify-between items-end mt-8 pt-6 border-t gap-6" style="border-color: #F0EBE3">
                <div class="text-xs leading-6" style="color: #6B6B6B">
                    <p class="font-semibold text-sm mb-2" style="color: #2B2B2B">Syarat & Ketentuan:</p>
                    <p>1. Barang wajib dikembalikan sesuai tanggal jatuh tempo.</p>
                    <p>2. Keterlambatan pengembalian dikenakan denda 50% per hari.</p>
                    <p>3. Kerusakan / kehilangan menjadi tanggung jawab penyewa.</p>
                    <p>4. Jaminan dikembalikan setelah barang kembali dalam kondisi baik.</p>
                </div>
                <div class="text-center flex-shrink-0">
                    <div style="height: 60px; border-bottom: 1px solid #2B2B2B; width: 160px;"></div>
                    <p class="text-xs mt-2" style="color: #6B6B6B">{{ $rental->createdBy->name }}</p>
                    <p class="text-xs" style="color: #6B6B6B">Petugas / Admin</p>
                </div>
            </div>

            {{-- Footer --}}
            <div class="mt-8 text-center border-t pt-4" style="border-color: #F0EBE3">
                <p class="text-xs" style="color: #6B6B6B">
                    Dokumen ini dibuat otomatis oleh sistem • {{ config('app.name') }} • {{ now()->format('d M Y H:i') }} WIB
                </p>
            </div>

        </div>
    </div>

    {{-- Powered by footer --}}
    <div class="no-print max-w-4xl mx-auto mt-4 text-center">
        <p class="text-xs" style="color: #9CA3AF">{{ config('app.name') }} &copy; {{ date('Y') }}</p>
    </div>

</body>
</html>