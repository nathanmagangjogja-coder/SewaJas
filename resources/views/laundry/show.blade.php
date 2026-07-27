@extends('layouts.app')

@section('title', 'Detail Laundry #' . $laundry->id)

@section('content')
<div class="space-y-5">

    {{-- Header + Breadcrumb --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center gap-2 text-xs mb-1" style="color:var(--text-soft)">
                <a href="{{ route('laundry.index') }}" class="hover:underline" style="color:var(--primary)">
                    Laundry
                </a>
                <i data-lucide="chevron-right" class="w-3 h-3"></i>
                <span>Detail #{{ $laundry->id }}</span>
            </div>
            <h1 class="font-playfair text-2xl font-bold" style="color:var(--text-dark)">
                Detail Laundry
            </h1>
        </div>
        <a href="{{ route('laundry.index') }}"
           class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold border transition-colors self-start"
           style="color:var(--text-soft); border-color:var(--border)">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali
        </a>
    </div>

    {{-- Status Flow --}}
    <div class="card p-4">
        @php
            $statusFlow = [
                'menunggu_laundry' => ['label' => 'Menunggu Laundry', 'color' => '#F59E0B', 'icon' => 'clock'],
                'dalam_laundry'    => ['label' => 'Dalam Laundry',    'color' => '#3B82F6', 'icon' => 'loader-2'],
                'siap_disewakan'   => ['label' => 'Siap Disewakan',   'color' => '#10B981', 'icon' => 'check-circle-2'],
            ];
            $reached = false;
        @endphp
        <div class="flex items-center gap-1 flex-wrap">
            @foreach($statusFlow as $key => $s)
            @php
                $isActive = $laundry->status === $key;
                $isPassed = !$reached && !$isActive;
                if ($isActive) $reached = true;
            @endphp
            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold transition-all"
                 style="background: {{ $isActive ? $s['color'].'20' : ($isPassed ? '#F3F4F6' : '#F9FAFB') }};
                        color: {{ $isActive ? $s['color'] : ($isPassed ? '#9CA3AF' : '#D1D5DB') }};
                        border: 1.5px solid {{ $isActive ? $s['color'] : 'transparent' }};
                        opacity: {{ $isPassed ? '0.5' : '1' }}">
                <i data-lucide="{{ $s['icon'] }}" class="w-3.5 h-3.5"></i>
                {{ $s['label'] }}
            </div>
            @if(!$loop->last)
            <i data-lucide="chevron-right" class="w-4 h-4" style="color:#D1D5DB"></i>
            @endif
            @endforeach
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid lg:grid-cols-5 gap-5">

        {{-- Info + Aksi (col 3) --}}
        <div class="lg:col-span-3 space-y-5">

            {{-- Info Card --}}
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b flex items-center gap-2" style="border-color:var(--border)">
                    <i data-lucide="info" class="w-4 h-4" style="color:var(--primary)"></i>
                    <h3 class="font-playfair font-semibold" style="color:var(--text-dark)">Informasi Laundry</h3>
                </div>
                <div class="p-5">
                    <div class="grid sm:grid-cols-2 gap-x-8 gap-y-3">

                        {{-- Kolom Kiri --}}
                        <div class="space-y-3">
                            @foreach([
                                ['label' => 'Kode Transaksi', 'value' => $laundry->transaksi->invoice_number ?? '-', 'mono' => true],
                                ['label' => 'Customer',       'value' => $laundry->transaksi->customer->name ?? '-'],
                                ['label' => 'No. HP',         'value' => $laundry->transaksi->customer->phone ?? '-'],
                                ['label' => 'Jas',            'value' => $laundry->produk->name ?? '-', 'bold' => true],
                            ] as $row)
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider mb-0.5"
                                   style="color:var(--text-soft)">{{ $row['label'] }}</p>
                                <p class="text-sm {{ ($row['bold'] ?? false) ? 'font-semibold' : '' }}
                                              {{ ($row['mono'] ?? false) ? 'font-mono' : '' }}"
                                   style="color:{{ ($row['mono'] ?? false) ? 'var(--primary)' : 'var(--text-dark)' }}">
                                    {{ $row['value'] }}
                                </p>
                            </div>
                            @endforeach
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider mb-0.5"
                                   style="color:var(--text-soft)">Status</p>
                                <span class="badge text-[10px]"
                                      style="background:{{ ['menunggu_laundry'=>'#FEF3C7','dalam_laundry'=>'#DBEAFE','siap_disewakan'=>'#D1FAE5'][$laundry->status] ?? '#F3F4F6' }};
                                             color:{{ ['menunggu_laundry'=>'#92400E','dalam_laundry'=>'#1D4ED8','siap_disewakan'=>'#065F46'][$laundry->status] ?? '#6B7280' }}">
                                    {{ $laundry->status_label }}
                                </span>
                            </div>
                        </div>

                        {{-- Kolom Kanan --}}
                        <div class="space-y-3">
                            @foreach([
                                ['label' => 'Dikembalikan',   'value' => $laundry->dikembalikan_at?->format('d/m/Y H:i') ?? '-'],
                                ['label' => 'Mulai Laundry',  'value' => $laundry->mulai_laundry_at?->format('d/m/Y H:i') ?? '-'],
                                ['label' => 'Selesai Laundry','value' => $laundry->selesai_laundry_at?->format('d/m/Y H:i') ?? '-'],
                                ['label' => 'Diproses Oleh',  'value' => $laundry->diprosesByUser->name ?? '-'],
                                ['label' => 'Catatan',        'value' => $laundry->catatan ?: '-'],
                            ] as $row)
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider mb-0.5"
                                   style="color:var(--text-soft)">{{ $row['label'] }}</p>
                                <p class="text-sm" style="color:var(--text-dark)">{{ $row['value'] }}</p>
                            </div>
                            @endforeach
                        </div>

                    </div>
                </div>
            </div>

            {{-- Action Card --}}
            @if($laundry->status === 'menunggu_laundry')
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b flex items-center gap-2" style="border-color:var(--border)">
                    <i data-lucide="play-circle" class="w-4 h-4" style="color:#3B82F6"></i>
                    <h3 class="font-playfair font-semibold" style="color:var(--text-dark)">Mulai Proses Laundry</h3>
                </div>
                <form method="POST" action="{{ route('laundry.mulai', $laundry) }}" class="p-5 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold mb-1.5" style="color:var(--text-dark)">
                            Catatan Laundry <span style="color:var(--text-soft)">(opsional)</span>
                        </label>
                        <textarea name="catatan" rows="3"
                                  placeholder="Kondisi jas, noda, dll..."
                                  class="w-full px-3 py-2 rounded-xl text-sm resize-none outline-none"
                                  style="border:1.5px solid var(--border); color:var(--text-dark);
                                         font-family:inherit; background:var(--bg-page)"></textarea>
                    </div>
                    <button type="submit"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white transition-all hover:opacity-90"
                            style="background:linear-gradient(135deg,#3B82F6,#1D4ED8)">
                        <i data-lucide="play" class="w-4 h-4"></i> Mulai Proses Laundry
                    </button>
                </form>
            </div>

            @elseif($laundry->status === 'dalam_laundry')
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b flex items-center gap-2" style="border-color:var(--border)">
                    <i data-lucide="check-circle-2" class="w-4 h-4" style="color:#10B981"></i>
                    <h3 class="font-playfair font-semibold" style="color:var(--text-dark)">Tandai Selesai</h3>
                </div>
                <form method="POST" action="{{ route('laundry.selesai', $laundry) }}" class="p-5 space-y-4">
                    @csrf
                    <div class="flex items-start gap-3 px-3 py-3 rounded-xl"
                         style="background:#F0FDF4; border:1px solid #BBF7D0">
                        <i data-lucide="info" class="w-4 h-4 mt-0.5 flex-shrink-0" style="color:#15803D"></i>
                        <p class="text-xs" style="color:#15803D">
                            Stok jas akan otomatis bertambah setelah selesai laundry.
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1.5" style="color:var(--text-dark)">
                            Catatan Selesai <span style="color:var(--text-soft)">(opsional)</span>
                        </label>
                        <textarea name="catatan" rows="3"
                                  placeholder="Kondisi jas setelah laundry..."
                                  class="w-full px-3 py-2 rounded-xl text-sm resize-none outline-none"
                                  style="border:1.5px solid var(--border); color:var(--text-dark);
                                         font-family:inherit; background:var(--bg-page)"></textarea>
                    </div>
                    <button type="submit"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white transition-all hover:opacity-90"
                            style="background:linear-gradient(135deg,#10B981,#059669)">
                        <i data-lucide="check" class="w-4 h-4"></i> Tandai Selesai Laundry
                    </button>
                </form>
            </div>

            @elseif($laundry->status === 'siap_disewakan')
            <div class="flex items-start gap-3 px-4 py-4 rounded-xl"
                 style="background:#F0FDF4; border:1px solid #BBF7D0">
                <i data-lucide="check-circle-2" class="w-5 h-5 mt-0.5 flex-shrink-0" style="color:#10B981"></i>
                <p class="text-sm" style="color:#15803D">
                    Jas ini telah selesai laundry dan <strong>siap disewakan kembali</strong>. Stok telah diperbarui.
                </p>
            </div>
            @endif

        </div>

        {{-- Riwayat Status (col 2) --}}
        <div class="lg:col-span-2">
            <div class="card overflow-hidden h-full">
                <div class="px-5 py-4 border-b flex items-center gap-2" style="border-color:var(--border)">
                    <i data-lucide="history" class="w-4 h-4" style="color:var(--text-soft)"></i>
                    <h3 class="font-playfair font-semibold" style="color:var(--text-dark)">Riwayat Status</h3>
                </div>
                <div class="p-5 space-y-5">

                    {{-- Riwayat Laundry --}}
                    @if($laundry->statusHistories->isNotEmpty())
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-widest mb-3"
                           style="color:var(--text-soft)">Laundry</p>
                        <div class="relative pl-5">
                            <div class="absolute left-[7px] top-2 bottom-2 w-0.5"
                                 style="background:var(--border)"></div>
                            <div class="space-y-4">
                                @foreach($laundry->statusHistories->sortByDesc('changed_at') as $h)
                                @php
                                    $dot = ['menunggu_laundry'=>'#F59E0B','dalam_laundry'=>'#3B82F6','siap_disewakan'=>'#10B981'][$h->status_baru] ?? '#9CA3AF';
                                @endphp
                                <div class="relative">
                                    <div class="absolute -left-5 top-1 w-3.5 h-3.5 rounded-full border-2 border-white"
                                         style="background:{{ $dot }}; box-shadow: 0 0 0 2px {{ $dot }}40"></div>
                                    <p class="text-sm font-semibold" style="color:var(--text-dark)">
                                        {{ $h->status_baru_label ?? $h->status_baru }}
                                    </p>
                                    <p class="text-[11px] mt-0.5" style="color:var(--text-soft)">
                                        {{ $h->changed_at->format('d/m/Y H:i') }}
                                        · {{ $h->user->name ?? 'System' }}
                                    </p>
                                    @if($h->keterangan)
                                    <p class="text-[11px] mt-1 italic" style="color:var(--text-soft)">
                                        "{{ $h->keterangan }}"
                                    </p>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Riwayat Transaksi --}}
                    @if($transaksiHistories->isNotEmpty())
                    <div class="pt-4" style="border-top:1px solid var(--border)">
                        <p class="text-[10px] font-bold uppercase tracking-widest mb-3"
                           style="color:var(--text-soft)">Transaksi</p>
                        <div class="relative pl-5">
                            <div class="absolute left-[7px] top-2 bottom-2 w-0.5"
                                 style="background:var(--border)"></div>
                            <div class="space-y-4">
                                @foreach($transaksiHistories->take(5) as $h)
                                <div class="relative">
                                    <div class="absolute -left-5 top-1 w-3.5 h-3.5 rounded-full border-2 border-white"
                                         style="background:#D6B98C; box-shadow: 0 0 0 2px #D6B98C40"></div>
                                    <p class="text-sm font-semibold" style="color:var(--text-dark)">
                                        {{ $h->status_baru_label ?? $h->status_baru }}
                                    </p>
                                    <p class="text-[11px] mt-0.5" style="color:var(--text-soft)">
                                        {{ $h->changed_at->format('d/m/Y H:i') }}
                                        · {{ $h->user->name ?? 'System' }}
                                    </p>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($laundry->statusHistories->isEmpty() && $transaksiHistories->isEmpty())
                    <div class="py-8 text-center">
                        <i data-lucide="clock" class="w-8 h-8 mx-auto mb-2" style="color:#D6B98C; opacity:.5"></i>
                        <p class="text-xs" style="color:var(--text-soft)">Belum ada riwayat</p>
                    </div>
                    @endif

                </div>
            </div>
        </div>

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