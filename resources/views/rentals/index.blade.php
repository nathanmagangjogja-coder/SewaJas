@extends('layouts.app')

@section('title', 'Penyewaan')
@section('page-title', 'Penyewaan')
@section('subtitle', 'Kelola semua transaksi penyewaan jas')

@section('content')
<div class="space-y-4">

    {{-- ===== TOP BAR: TABS + ACTIONS ===== --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

        {{-- Status Tabs — scroll horizontal di mobile --}}
        <div class="overflow-x-auto -mx-4 px-4 sm:mx-0 sm:px-0">
            <div class="flex items-center gap-1 p-1 rounded-xl w-max sm:w-auto" style="background: var(--secondary)">
                @foreach([
                    'all'       => ['label' => 'Semua',    'count' => $statusCounts['all']],
                    'waiting'   => ['label' => 'Menunggu', 'count' => $statusCounts['waiting']],
                    'active'    => ['label' => 'Sedang Disewa', 'count' => $statusCounts['active']],
                    'overdue'   => ['label' => 'Telat',    'count' => $statusCounts['overdue']],
                    'returned'  => ['label' => 'Kembali',  'count' => $statusCounts['returned']],
                    'cancelled' => ['label' => 'Batal',    'count' => $statusCounts['cancelled']],
                ] as $status => $info)
                <a href="{{ route('rentals.index', array_merge(request()->except('status', 'page'), $status !== 'all' ? ['status' => $status] : [])) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all flex items-center gap-1.5 whitespace-nowrap {{ (request('status', 'all') === $status) ? 'shadow-sm' : '' }}"
                   style="{{ (request('status', 'all') === $status) ? 'background: #FFFFFF; color: var(--text-dark);' : 'color: var(--text-soft);' }}">
                    {{ $info['label'] }}
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold
                        {{ $status === 'overdue' && $info['count'] > 0 ? 'bg-red-100 text-red-600' : 'bg-white/60 text-gray-500' }}">
                        {{ $info['count'] }}
                    </span>
                </a>
                @endforeach
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('rentals.create') }}" class="btn-primary text-sm">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span class="hidden xs:inline">Sewa Baru</span>
                <span class="xs:hidden">Baru</span>
            </a>
            <a href="{{ route('rentals.scan') }}" class="btn-secondary text-sm">
                <i data-lucide="scan-qr-code" class="w-4 h-4"></i>
                <span class="hidden sm:inline">Scan QR</span>
            </a>
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="btn-secondary text-sm">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    <span class="hidden sm:inline">Export</span>
                    <i data-lucide="chevron-down" class="w-3 h-3"></i>
                </button>
                <div x-show="open" @click.outside="open = false"
                     class="absolute right-0 mt-1 w-40 rounded-xl shadow-lg border overflow-hidden z-10"
                     style="background: #FFFFFF; border-color: var(--border);">
                    <a href="{{ route('reports.export.pdf', request()->all()) }}"
                       class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-gray-50 transition-colors text-dark">
                        <i data-lucide="file-text" class="w-4 h-4 text-red-400"></i> Export PDF
                    </a>
                    <a href="{{ route('reports.export.excel', request()->all()) }}"
                       class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-gray-50 transition-colors text-dark">
                        <i data-lucide="table-2" class="w-4 h-4 text-green-500"></i> Export Excel
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== FILTER CARD ===== --}}
    <div class="card p-4" x-data="{ expanded: {{ request()->hasAny(['search','payment_status','date_from','date_to']) ? 'true' : 'false' }} }">
        {{-- Filter toggle header (mobile) --}}
        <button type="button" @click="expanded = !expanded"
                class="flex items-center justify-between w-full sm:hidden mb-0"
                :class="expanded ? 'mb-3' : ''">
            <span class="text-sm font-medium flex items-center gap-2 text-dark">
                <i data-lucide="filter" class="w-4 h-4" style="color: var(--primary)"></i>
                Filter & Pencarian
                @if(request()->hasAny(['search','payment_status','date_from','date_to']))
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                @endif
            </span>
            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform text-soft"
               :class="{ 'rotate-180': expanded }"></i>
        </button>

        <form method="GET" action="{{ route('rentals.index') }}"
              x-show="expanded || window.innerWidth >= 640"
              class="flex flex-col sm:flex-row flex-wrap gap-3 sm:items-end">
            <input type="hidden" name="status" value="{{ request('status') }}">

            {{-- Search --}}
            <div class="flex-1 min-w-0 sm:min-w-48">
                <label class="block text-xs font-medium mb-1.5 text-soft">Cari</label>
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-soft"></i>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Invoice, nama customer..."
                           class="form-input pl-9 w-full">
                </div>
            </div>

            {{-- Pembayaran --}}
            <div class="min-w-0">
                <label class="block text-xs font-medium mb-1.5 text-soft">Pembayaran</label>
                <select name="payment_status" class="form-input w-full">
                    <option value="">Semua</option>
                    <option value="unpaid"  {{ request('payment_status') === 'unpaid'  ? 'selected' : '' }}>Belum Bayar</option>
                    <option value="partial" {{ request('payment_status') === 'partial' ? 'selected' : '' }}>Sebagian</option>
                    <option value="paid"    {{ request('payment_status') === 'paid'    ? 'selected' : '' }}>Lunas</option>
                </select>
            </div>

            {{-- Tanggal --}}
            <div class="grid grid-cols-2 gap-3 sm:flex sm:gap-3">
                <div>
                    <label class="block text-xs font-medium mb-1.5 text-soft">Dari</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input w-full">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1.5 text-soft">Sampai</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input w-full">
                </div>
            </div>

            {{-- Buttons --}}
            <div class="flex gap-2">
                <button type="submit" class="btn-primary flex-1 sm:flex-none">
                    <i data-lucide="filter" class="w-4 h-4"></i>
                    Filter
                </button>
                @if(request()->hasAny(['search', 'payment_status', 'date_from', 'date_to']))
                <a href="{{ route('rentals.index', ['status' => request('status')]) }}" class="btn-secondary">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- ===== TABLE (desktop) / CARD LIST (mobile) ===== --}}

    {{-- Desktop Table --}}
    <div class="card overflow-hidden hidden md:block">
        <div class="overflow-x-auto">
            <table class="w-full elegant-table">
                <thead>
                    <tr>
                        <th class="text-left w-10">#</th>
                        <th class="text-left">Invoice</th>
                        <th class="text-left">Customer</th>
                        <th class="text-left">Barang</th>
                        <th class="text-left">Paket</th>
                        <th class="text-left">Sewa</th>
                        <th class="text-left">Jatuh Tempo</th>
                        <th class="text-right">Total</th>
                        <th class="text-center">Bayar</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rentals as $rental)
                    <tr class="{{ $rental->rental_status === 'overdue' ? 'bg-red-50/30' : (in_array($rental->due_alert['level'] ?? null, ['today', 'tomorrow']) ? 'bg-amber-50/40' : '') }}">
                        <td class="text-xs text-soft">{{ $rentals->firstItem() + $loop->index }}</td>
                        <td>
                            <a href="{{ route('rentals.show', $rental) }}"
                               class="font-mono text-sm font-semibold hover:underline" style="color: #D6B98C">
                                {{ $rental->invoice_number }}
                            </a>
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                <img src="{{ $rental->customer->photo_url }}" alt="" loading="lazy"
                                     class="w-7 h-7 rounded-full object-cover flex-shrink-0">
                                <div>
                                    <p class="text-sm font-medium text-dark">{{ $rental->customer->name }}</p>
                                    <p class="text-xs text-soft">{{ $rental->customer->phone }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                @foreach($rental->items->take(2) as $item)
                                <span class="badge badge-gray">{{ Str::limit($item->product_name, 18) }}</span>
                                @endforeach
                                @if($rental->items->count() > 2)
                                <span class="badge badge-gold">+{{ $rental->items->count() - 2 }}</span>
                                @endif
                            </div>
                        </td>
                        {{-- Paket --}}
                        <td class="text-sm whitespace-nowrap">
                            @if($rental->package)
                            <span class="px-1.5 py-0.5 rounded text-xs font-medium"
                                  style="background:var(--bg-soft); color:var(--primary)">
                                {{ $rental->package->name }}
                            </span>
                            <p class="text-xs mt-0.5 text-soft">
                                {{ $rental->duration_days }} hari
                            </p>
                            @else
                            <span class="text-xs text-soft">{{ $rental->duration_days }} hari</span>
                            @endif
                        </td>
                        <td class="text-sm whitespace-nowrap text-dark">{{ $rental->rental_date->format('d M Y') }}</td>
                        <td>
                            <span class="text-sm whitespace-nowrap {{ $rental->rental_status === 'overdue' ? 'text-red-500 font-semibold' : '' }}"
                                  style="{{ $rental->rental_status !== 'overdue' ? 'color: var(--text-dark)' : '' }}">
                                {{ $rental->return_due_date->format('d M Y') }}
                            </span>
                            @if($rental->due_alert)
                            <p class="text-xs mt-0.5 font-semibold flex items-center gap-1"
                               style="color: {{ match($rental->due_alert['level']) { 'overdue' => '#DC2626', 'today' => '#DC2626', 'tomorrow' => '#D97706', default => '#B45309' } }}">
                                <i data-lucide="{{ $rental->due_alert['level'] === 'overdue' ? 'alert-triangle' : 'clock-alert' }}" class="w-3 h-3"></i>
                                {{ $rental->due_alert['label'] }}
                            </p>
                            @endif
                        </td>
                        <td class="text-right font-semibold text-sm whitespace-nowrap text-dark">
                            Rp {{ number_format($rental->total_amount, 0, ',', '.') }}
                        </td>
                        <td class="text-center">
                            <span class="badge {{ match($rental->payment_status) { 'paid' => 'badge-green', 'partial' => 'badge-yellow', default => 'badge-red' } }}">
                                {{ $rental->payment_status_label }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-{{ $rental->status_badge_color }}">{{ $rental->status_label }}</span>
                        </td>
                        <td>
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('rentals.show', $rental) }}"
                                   class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors text-soft" title="Detail">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                </a>
                                <a href="{{ route('rentals.invoice', $rental) }}"
                                   class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors text-soft" title="Invoice">
                                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                </a>
                                <a href="{{ route('rentals.whatsapp', $rental) }}" target="_blank"
                                   class="p-1.5 rounded-lg hover:bg-green-50 transition-colors" title="WhatsApp"
                                   style="color: #25D366">
                                    <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="py-16 text-center">
                            @include('rentals.partials.empty-state')
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rentals->hasPages())
        <div class="px-6 py-4 border-t" style="border-color: var(--border)">
            {{ $rentals->links('components.pagination') }}
        </div>
        @endif
    </div>

    {{-- Mobile Card List --}}
    <div class="md:hidden space-y-3">
        @forelse($rentals as $rental)
        <div class="card p-4 {{ $rental->rental_status === 'overdue' ? 'border-l-4 border-red-400' : (in_array($rental->due_alert['level'] ?? null, ['today', 'tomorrow']) ? 'border-l-4 border-amber-400' : '') }}">
            {{-- Header row --}}
            <div class="flex items-start justify-between gap-2 mb-3">
                <div>
                    <a href="{{ route('rentals.show', $rental) }}"
                       class="font-mono text-sm font-bold hover:underline" style="color: #D6B98C">
                        {{ $rental->invoice_number }}
                    </a>
                    <p class="text-xs mt-0.5 text-soft">
                        {{ $rental->rental_date->format('d M Y') }} →
                        <span class="{{ $rental->rental_status === 'overdue' ? 'text-red-500 font-semibold' : '' }}">
                            {{ $rental->return_due_date->format('d M Y') }}
                        </span>
                    </p>
                    @if($rental->due_alert)
                    <p class="text-xs mt-0.5 font-semibold flex items-center gap-1"
                       style="color: {{ match($rental->due_alert['level']) { 'overdue' => '#DC2626', 'today' => '#DC2626', 'tomorrow' => '#D97706', default => '#B45309' } }}">
                        <i data-lucide="{{ $rental->due_alert['level'] === 'overdue' ? 'alert-triangle' : 'clock-alert' }}" class="w-3 h-3"></i>
                        {{ $rental->due_alert['label'] }}
                    </p>
                    @endif
                </div>
                <div class="flex flex-col items-end gap-1 flex-shrink-0">
                    <span class="badge badge-{{ $rental->status_badge_color }}">{{ $rental->status_label }}</span>
                    <span class="badge {{ match($rental->payment_status) { 'paid' => 'badge-green', 'partial' => 'badge-yellow', default => 'badge-red' } }}">
                        {{ $rental->payment_status_label }}
                    </span>
                </div>
            </div>

            {{-- Customer row --}}
            <div class="flex items-center gap-2 mb-3 pb-3" style="border-bottom: 1px solid var(--border)">
                <img src="{{ $rental->customer->photo_url }}" alt="" loading="lazy"
                     class="w-8 h-8 rounded-full object-cover flex-shrink-0">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium truncate text-dark">{{ $rental->customer->name }}</p>
                    <p class="text-xs text-soft">{{ $rental->customer->phone }}</p>
                </div>
                <p class="font-bold text-sm flex-shrink-0 text-dark">
                    Rp {{ number_format($rental->total_amount, 0, ',', '.') }}
                </p>
            </div>

            {{-- Items row --}}
            <div class="flex flex-wrap gap-1 mb-3">
                @foreach($rental->items->take(3) as $item)
                <span class="badge badge-gray text-[10px]">{{ Str::limit($item->product_name, 20) }}</span>
                @endforeach
                @if($rental->items->count() > 3)
                <span class="badge badge-gold text-[10px]">+{{ $rental->items->count() - 3 }}</span>
                @endif
            </div>

            {{-- Action row --}}
            <div class="flex items-center gap-2">
                <a href="{{ route('rentals.show', $rental) }}" class="btn-secondary flex-1 justify-center text-xs py-1.5">
                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                    Detail
                </a>
                <a href="{{ route('rentals.invoice', $rental) }}" class="btn-secondary text-xs py-1.5 px-3">
                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                </a>
                <a href="{{ route('rentals.whatsapp', $rental) }}" target="_blank"
                   class="btn-secondary text-xs py-1.5 px-3" style="color: #25D366; border-color: #25D366">
                    <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </div>
        @empty
        <div class="card p-12 text-center">
            @include('rentals.partials.empty-state')
        </div>
        @endforelse

        {{-- Pagination mobile --}}
        @if($rentals->hasPages())
        <div class="pt-2">
            {{ $rentals->links('components.pagination') }}
        </div>
        @endif
    </div>

</div>
@endsection