@extends('layouts.app')

@section('title', 'Riwayat Laundry')

@section('content')
<div class="space-y-5">

    {{-- Header --}}
    <div>
        <h1 class="font-playfair text-2xl font-bold" style="color:var(--text-dark)">
            Riwayat Laundry
        </h1>
        <p class="text-sm mt-0.5" style="color:var(--text-soft)">
            Audit trail semua perubahan status laundry
        </p>
    </div>

    {{-- Tabel --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b flex items-center gap-2" style="border-color:var(--border)">
            <i data-lucide="history" class="w-4 h-4" style="color:var(--text-soft)"></i>
            <h3 class="font-playfair font-semibold" style="color:var(--text-dark)">Semua Riwayat</h3>
        </div>

        @if($histories->isEmpty())
        <div class="py-16 text-center">
            <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-3" style="color:#D6B98C; opacity:.5"></i>
            <p class="text-sm" style="color:var(--text-soft)">Belum ada riwayat perubahan status</p>
        </div>

        @else

        {{-- Desktop Table --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full elegant-table">
                <thead>
                    <tr>
                        <th class="text-left">#</th>
                        <th class="text-left">Laundry ID</th>
                        <th class="text-left">Status Sebelum</th>
                        <th class="text-left">Status Sesudah</th>
                        <th class="text-left">Keterangan</th>
                        <th class="text-left">Diubah Oleh</th>
                        <th class="text-left">Waktu</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($histories as $index => $h)
                    @php
                        $badgeMap = [
                            'menunggu_laundry' => ['bg' => '#FEF3C7', 'color' => '#92400E'],
                            'dalam_laundry'    => ['bg' => '#DBEAFE', 'color' => '#1D4ED8'],
                            'siap_disewakan'   => ['bg' => '#D1FAE5', 'color' => '#065F46'],
                        ];
                        $badge = $badgeMap[$h->status_baru] ?? ['bg' => '#F3F4F6', 'color' => '#6B7280'];
                    @endphp
                    <tr>
                        <td class="text-sm" style="color:var(--text-soft)">
                            {{ $histories->firstItem() + $index }}
                        </td>
                        <td>
                            <a href="{{ route('laundry.show', $h->model_id) }}"
                               class="font-mono text-sm font-semibold hover:underline"
                               style="color:var(--primary)">
                                #{{ $h->model_id }}
                            </a>
                        </td>
                        <td>
                            @if($h->status_lama)
                            <span class="badge text-[10px]" style="background:#F3F4F6;color:#6B7280">
                                {{ $h->status_lama_label }}
                            </span>
                            @else
                            <span class="text-xs" style="color:var(--text-soft)">-</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge text-[10px]"
                                  style="background:{{ $badge['bg'] }};color:{{ $badge['color'] }}">
                                {{ $h->status_baru_label }}
                            </span>
                        </td>
                        <td class="text-xs" style="color:var(--text-soft)">
                            {{ $h->keterangan ?: '-' }}
                        </td>
                        <td class="text-sm" style="color:var(--text-dark)">
                            {{ $h->user->name ?? 'System' }}
                        </td>
                        <td>
                            <p class="text-sm" style="color:var(--text-dark)">
                                {{ $h->changed_at->format('d/m/Y') }}
                            </p>
                            <p class="text-[11px]" style="color:var(--text-soft)">
                                {{ $h->changed_at->format('H:i:s') }}
                            </p>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('laundry.show', $h->model_id) }}"
                               class="p-1.5 rounded-lg hover:bg-gray-100 inline-flex transition-colors"
                               style="color:var(--text-soft)">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile Card List --}}
        <div class="md:hidden divide-y" style="border-color:var(--border)">
            @foreach($histories as $h)
            @php
                $badgeMap = [
                    'menunggu_laundry' => ['bg' => '#FEF3C7', 'color' => '#92400E'],
                    'dalam_laundry'    => ['bg' => '#DBEAFE', 'color' => '#1D4ED8'],
                    'siap_disewakan'   => ['bg' => '#D1FAE5', 'color' => '#065F46'],
                ];
                $badge = $badgeMap[$h->status_baru] ?? ['bg' => '#F3F4F6', 'color' => '#6B7280'];
            @endphp
            <div class="p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        {{-- ID + Status --}}
                        <div class="flex items-center gap-2 flex-wrap">
                            <a href="{{ route('laundry.show', $h->model_id) }}"
                               class="font-mono text-sm font-semibold" style="color:var(--primary)">
                                #{{ $h->model_id }}
                            </a>
                            @if($h->status_lama)
                            <span class="badge text-[10px]" style="background:#F3F4F6;color:#6B7280">
                                {{ $h->status_lama_label }}
                            </span>
                            <i data-lucide="arrow-right" class="w-3 h-3" style="color:var(--text-soft)"></i>
                            @endif
                            <span class="badge text-[10px]"
                                  style="background:{{ $badge['bg'] }};color:{{ $badge['color'] }}">
                                {{ $h->status_baru_label }}
                            </span>
                        </div>
                        {{-- Meta --}}
                        <p class="text-xs mt-1.5" style="color:var(--text-soft)">
                            {{ $h->user->name ?? 'System' }}
                            · {{ $h->changed_at->format('d/m/Y H:i') }}
                        </p>
                        @if($h->keterangan)
                        <p class="text-xs mt-1 italic" style="color:var(--text-soft)">
                            "{{ $h->keterangan }}"
                        </p>
                        @endif
                    </div>
                    <a href="{{ route('laundry.show', $h->model_id) }}"
                       class="p-1.5 rounded-lg hover:bg-gray-100 flex-shrink-0"
                       style="color:var(--text-soft)">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="px-5 py-4 border-t flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
             style="border-color:var(--border)">
            <p class="text-xs" style="color:var(--text-soft)">
                Menampilkan {{ $histories->firstItem() }}–{{ $histories->lastItem() }}
                dari {{ $histories->total() }} data
            </p>
            {{ $histories->links() }}
        </div>

        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    lucide.createIcons();
});
</script>
@endpush