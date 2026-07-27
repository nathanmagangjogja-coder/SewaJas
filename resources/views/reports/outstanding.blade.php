@extends('Layouts.app')
@section('title', 'Laporan Piutang')

@section('content')
<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="font-playfair text-2xl font-bold" style="color:var(--text-dark)">
                Piutang Outstanding
            </h1>
            <p class="text-sm mt-0.5" style="color:var(--text-soft)">
                Transaksi yang belum lunas dan perlu ditagih
            </p>
        </div>
        <a href="{{ route('reports.outstanding', array_merge(request()->all(), ['export' => 'pdf'])) }}"
           class="btn-primary flex items-center gap-2 text-sm">
            <i data-lucide="download" class="w-4 h-4"></i>
            Export PDF
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-4" style="border-left:3px solid #EF4444">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center"
                     style="background:#FEF2F2">
                    <i data-lucide="alert-triangle" class="w-4 h-4" style="color:#EF4444"></i>
                </div>
            </div>
            <p class="text-xs" style="color:var(--text-soft)">Total Piutang</p>
            <p class="text-2xl font-bold font-playfair" style="color:var(--text-dark)">
                {{ $stats['total_count'] }}
            </p>
            <p class="text-xs" style="color:var(--text-soft)">transaksi belum lunas</p>
        </div>

        <div class="card p-4" style="border-left:3px solid #D97706">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center"
                     style="background:#FFFBEB">
                    <i data-lucide="banknote" class="w-4 h-4" style="color:#D97706"></i>
                </div>
            </div>
            <p class="text-xs" style="color:var(--text-soft)">Nilai Piutang</p>
            <p class="text-lg font-bold font-playfair" style="color:#D97706">
                Rp {{ number_format($stats['total_nilai'], 0, ',', '.') }}
            </p>
        </div>

        <div class="card p-4">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center"
                     style="background:#FEF2F2">
                    <i data-lucide="x-circle" class="w-4 h-4" style="color:#EF4444"></i>
                </div>
            </div>
            <p class="text-xs" style="color:var(--text-soft)">Belum Bayar Sama Sekali</p>
            <p class="text-2xl font-bold font-playfair text-red-500">
                {{ $stats['unpaid_count'] }}
            </p>
        </div>

        <div class="card p-4">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center"
                     style="background:#FFFBEB">
                    <i data-lucide="minus-circle" class="w-4 h-4" style="color:#D97706"></i>
                </div>
            </div>
            <p class="text-xs" style="color:var(--text-soft)">Bayar Sebagian</p>
            <p class="text-2xl font-bold font-playfair" style="color:#D97706">
                {{ $stats['partial_count'] }}
            </p>
        </div>
    </div>

    {{-- Filter Cabang (super_admin only) --}}
    @if($isSuperAdmin)
    <div class="card p-4">
        <form method="GET" action="{{ route('reports.outstanding') }}"
              class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-semibold mb-1.5" style="color:var(--text-soft)">Cabang</label>
                <select name="branch_id" class="form-input text-sm">
                    <option value="">Semua Cabang</option>
                    @foreach($branches as $b)
                    <option value="{{ $b->id }}"
                        {{ $selectedBranchId == $b->id ? 'selected' : '' }}>
                        {{ $b->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-primary px-4 py-2 text-sm">Filter</button>
            <a href="{{ route('reports.outstanding') }}" class="btn-secondary px-4 py-2 text-sm">Reset</a>
        </form>
    </div>
    @endif

    {{-- Tabel Piutang --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b flex items-center gap-2" style="border-color:var(--border)">
            <i data-lucide="alert-circle" class="w-4 h-4 text-red-500"></i>
            <h3 class="font-semibold text-sm" style="color:var(--text-dark)">
                Daftar Piutang — Wajib Ditagih
            </h3>
            <span class="ml-auto text-xs" style="color:var(--text-soft)">
                {{ $rentals->total() }} transaksi
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr style="background:var(--bg-soft)">
                        <th class="text-left px-4 py-3 text-xs font-semibold" style="color:var(--text-soft)">Invoice</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold" style="color:var(--text-soft)">Customer</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold" style="color:var(--text-soft)">Paket</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold" style="color:var(--text-soft)">Tgl Sewa</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold" style="color:var(--text-soft)">Total</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold" style="color:var(--text-soft)">Sudah Bayar</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold" style="color:var(--text-soft)">Sisa</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold" style="color:var(--text-soft)">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y" style="border-color:var(--border)">
                    @forelse($rentals as $rental)
                    @php $sisa = $rental->total_amount - $rental->paid_amount; @endphp
                    <tr class="hover:bg-[var(--bg-soft)] transition-colors">
                        <td class="px-4 py-3">
                            <span class="font-mono font-semibold text-xs" style="color:var(--primary)">
                                {{ $rental->invoice_number }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-sm" style="color:var(--text-dark)">
                                {{ $rental->customer?->name ?? '-' }}
                            </p>
                            @if($rental->customer?->phone)
                            <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/\D/', '', $rental->customer->phone)) }}?text={{ urlencode('Halo ' . $rental->customer->name . ', mengingatkan pembayaran sewa jas ' . $rental->invoice_number . ' sebesar Rp ' . number_format($sisa, 0, ',', '.') . ' belum dilunasi. Terima kasih.') }}"
                               target="_blank"
                               class="text-xs flex items-center gap-1 mt-0.5" style="color:#25D366">
                                <i data-lucide="message-circle" class="w-3 h-3"></i>
                                {{ $rental->customer->phone }}
                            </a>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs" style="color:var(--text-soft)">
                            {{ $rental->package?->name ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-xs" style="color:var(--text-soft)">
                            {{ $rental->rental_date?->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3 text-right font-medium" style="color:var(--text-dark)">
                            Rp {{ number_format($rental->total_amount, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right" style="color:var(--text-soft)">
                            Rp {{ number_format($rental->paid_amount, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-red-500">
                            Rp {{ number_format($sisa, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($rental->payment_status === 'unpaid')
                                <span class="px-2 py-1 rounded-lg text-xs font-semibold"
                                      style="background:#FEF2F2; color:#EF4444">
                                    Belum Bayar
                                </span>
                            @else
                                <span class="px-2 py-1 rounded-lg text-xs font-semibold"
                                      style="background:#FFFBEB; color:#D97706">
                                    Bayar Sebagian
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('rentals.show', $rental) }}"
                               class="text-xs px-2 py-1 rounded-lg border transition-colors hover:bg-[var(--bg-soft)]"
                               style="color:var(--primary); border-color:var(--primary)">
                                Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-12 text-center text-sm" style="color:var(--text-soft)">
                            <i data-lucide="check-circle-2" class="w-10 h-10 mx-auto mb-3 opacity-20"></i>
                            <p class="font-medium">Tidak ada piutang.</p>
                            <p class="text-xs mt-1">Semua transaksi sudah lunas! 🎉</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>

                {{-- Total row --}}
                @if($rentals->count() > 0)
                <tfoot>
                    <tr style="background:var(--bg-soft)">
                        <td colspan="6" class="px-4 py-3 text-sm font-semibold" style="color:var(--text-dark)">
                            Total Piutang ({{ $stats['total_count'] }} transaksi)
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-red-500">
                            Rp {{ number_format($stats['total_nilai'], 0, ',', '.') }}
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        @if($rentals->hasPages())
        <div class="px-4 py-3 border-t" style="border-color:var(--border)">
            {{ $rentals->links() }}
        </div>
        @endif
    </div>

</div>
@endsection