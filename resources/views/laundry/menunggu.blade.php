@extends('layouts.app')

@section('title', 'Menunggu Laundry')

@section('content')
<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="font-playfair text-2xl font-bold" style="color:var(--text-dark)">
                Menunggu Laundry
            </h1>
            <p class="text-sm mt-0.5" style="color:var(--text-soft)">
                Jas yang baru dikembalikan dan menunggu proses laundry
            </p>
        </div>
        <div class="flex items-center gap-2">
        <a href="{{ route('laundry.index') }}"
           class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold border transition-colors"
           style="color:var(--text-soft); border-color:var(--border)">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Kembali</span>
        </a>
        <button id="btnBatchMulai" disabled
                class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white
                       transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                style="background: linear-gradient(135deg, #3B82F6, #1D4ED8)">
            <i data-lucide="play" class="w-4 h-4"></i>
            <span>Mulai Laundry Terpilih</span>
        </button>
    </div>
</div>

    {{-- Alert --}}
    @if(session('success'))
    <div class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium"
         style="background:#F0FDF4; color:#15803D; border:1px solid #BBF7D0">
        <i data-lucide="check-circle-2" class="w-4 h-4 flex-shrink-0"></i>
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium"
         style="background:#FFF1F0; color:#C0392B; border:1px solid #FECACA">
        <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
        {{ session('error') }}
    </div>
    @endif

    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        <a href="{{ route('laundry.menunggu') }}" class="stat-card block hover:scale-105 transition-transform"
           style="border-left: 4px solid #F59E0B;">
            <div class="flex items-start justify-between mb-3">
                <div class="p-2.5 rounded-xl" style="background:#FEF3C720">
                    <i data-lucide="clock" class="w-5 h-5" style="color:#F59E0B"></i>
                </div>
                <span class="badge badge-yellow text-[10px]">Antrian</span>
            </div>
            <p class="text-3xl font-bold font-playfair" style="color:var(--text-dark)">
                {{ $stats['menunggu_laundry'] }}
            </p>
            <p class="text-xs mt-1" style="color:var(--text-soft)">Menunggu Laundry</p>
        </a>

        <a href="{{ route('laundry.dalam') }}" class="stat-card block hover:scale-105 transition-transform"
           style="border-left: 4px solid #3B82F6;">
            <div class="flex items-start justify-between mb-3">
                <div class="p-2.5 rounded-xl" style="background:#EFF6FF">
                    <i data-lucide="loader-2" class="w-5 h-5" style="color:#3B82F6"></i>
                </div>
                <span class="badge text-[10px]" style="background:#DBEAFE;color:#1D4ED8">Proses</span>
            </div>
            <p class="text-3xl font-bold font-playfair" style="color:var(--text-dark)">
                {{ $stats['dalam_laundry'] }}
            </p>
            <p class="text-xs mt-1" style="color:var(--text-soft)">Dalam Laundry</p>
        </a>

        <a href="{{ route('laundry.siap') }}" class="stat-card block hover:scale-105 transition-transform"
           style="border-left: 4px solid #10B981;">
            <div class="flex items-start justify-between mb-3">
                <div class="p-2.5 rounded-xl" style="background:#F0FDF4">
                    <i data-lucide="check-circle-2" class="w-5 h-5" style="color:#10B981"></i>
                </div>
                <span class="badge badge-green text-[10px]">Siap</span>
            </div>
            <p class="text-3xl font-bold font-playfair" style="color:var(--text-dark)">
                {{ $stats['siap_disewakan'] }}
            </p>
            <p class="text-xs mt-1" style="color:var(--text-soft)">Siap Disewakan</p>
        </a>

    </div>

    {{-- Tabel / Card List --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b flex items-center justify-between" style="border-color:var(--border)">
            <div class="flex items-center gap-2">
                <i data-lucide="list" class="w-4 h-4" style="color:#F59E0B"></i>
                <h3 class="font-playfair font-semibold" style="color:var(--text-dark)">Antrian Laundry</h3>
                <span class="badge badge-yellow text-[10px]">{{ $laundries->total() }}</span>
            </div>
        </div>

        @if($laundries->isEmpty())
        <div class="py-16 text-center">
            <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-3" style="color:#D6B98C; opacity:.5"></i>
            <p class="text-sm" style="color:var(--text-soft)">Tidak ada jas yang menunggu laundry</p>
        </div>

        @else

        {{-- Desktop Table --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full elegant-table">
                <thead>
                    <tr>
                        <th class="w-10">
                            <input type="checkbox" id="checkAll"
                                   class="w-4 h-4 rounded cursor-pointer accent-amber-500">
                        </th>
                        <th class="text-left">#</th>
                        <th class="text-left">Kode Transaksi</th>
                        <th class="text-left">Jas / Produk</th>
                        <th class="text-left">Customer</th>
                        <th class="text-left">Dikembalikan</th>
                        <th class="text-left">Lama Tunggu</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($laundries as $index => $laundry)
                    @php $hours = (int) ($laundry->dikembalikan_at?->diffInHours(now()) ?? 0); @endphp
                    <tr>
                        <td>
                            <input type="checkbox" class="check-item w-4 h-4 rounded cursor-pointer accent-amber-500"
                                   value="{{ $laundry->id }}">
                        </td>
                        <td class="text-sm" style="color:var(--text-soft)">
                            {{ $laundries->firstItem() + $index }}
                        </td>
                        <td>
                            <span class="font-mono text-sm font-semibold" style="color:var(--primary)">
                                {{ $laundry->transaksi->invoice_number ?? '-' }}
                            </span>
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                @if($laundry->produk->photo ?? false)
                                <img src="{{ asset('storage/' . $laundry->produk->photo) }}"
                                     class="w-9 h-9 rounded-lg object-cover flex-shrink-0">
                                @else
                                <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0"
                                     style="background:#F5F0EA">
                                    <i data-lucide="shirt" class="w-4 h-4" style="color:#D6B98C"></i>
                                </div>
                                @endif
                                <div>
                                    <p class="text-sm font-semibold" style="color:var(--text-dark)">
                                        {{ $laundry->produk->name ?? '-' }}
                                    </p>
                                    <p class="text-[11px]" style="color:var(--text-soft)">
                                        {{ $laundry->produk->size ?? '' }}
                                        {{ $laundry->produk->color ?? '' }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="text-sm" style="color:var(--text-dark)">
                            {{ $laundry->transaksi->customer->name ?? '-' }}
                        </td>
                        <td>
                            <p class="text-sm" style="color:var(--text-dark)">
                                {{ $laundry->dikembalikan_at?->format('d/m/Y') ?? '-' }}
                            </p>
                            <p class="text-[11px]" style="color:var(--text-soft)">
                                {{ $laundry->dikembalikan_at?->format('H:i') ?? '' }}
                            </p>
                        </td>
                        <td>
                            @if($laundry->dikembalikan_at)
                            <span class="badge text-[10px] {{ $hours > 24 ? 'badge-red' : ($hours > 12 ? 'badge-yellow' : 'badge-green') }}">
                                {{ $hours < 1 ? 'Baru saja' : $hours . ' jam' }}
                            </span>
                            @else
                            <span class="text-xs" style="color:var(--text-soft)">-</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center justify-center gap-1.5">
                                <button type="button"
                                        class="btn-mulai-laundry flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white transition-all hover:opacity-90"
                                        style="background:#3B82F6"
                                        data-id="{{ $laundry->id }}"
                                        data-nama="{{ $laundry->produk->name ?? '' }}">
                                    <i data-lucide="play" class="w-3 h-3"></i> Mulai
                                </button>
                                <a href="{{ route('laundry.show', $laundry) }}"
                                   class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors"
                                   style="color:var(--text-soft)">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile Card List --}}
        <div class="md:hidden divide-y" style="border-color:var(--border)">
            @foreach($laundries as $index => $laundry)
            @php $hours = (int) ($laundry->dikembalikan_at?->diffInHours(now()) ?? 0); @endphp
            <div class="p-4">
                <div class="flex items-start gap-3">
                    {{-- Checkbox + Foto --}}
                    <input type="checkbox" class="check-item mt-1 w-4 h-4 rounded accent-amber-500"
                           value="{{ $laundry->id }}">
                    @if($laundry->produk->photo ?? false)
                    <img src="{{ asset('storage/' . $laundry->produk->photo) }}"
                         class="w-12 h-12 rounded-xl object-cover flex-shrink-0">
                    @else
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                         style="background:#F5F0EA">
                        <i data-lucide="shirt" class="w-5 h-5" style="color:#D6B98C"></i>
                    </div>
                    @endif

                    <div class="flex-1 min-w-0">
                        {{-- Produk & Kode --}}
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-semibold text-sm truncate" style="color:var(--text-dark)">
                                {{ $laundry->produk->name ?? '-' }}
                            </p>
                            @if($laundry->dikembalikan_at)
                            <span class="badge text-[10px] flex-shrink-0 {{ $hours > 24 ? 'badge-red' : ($hours > 12 ? 'badge-yellow' : 'badge-green') }}">
                                {{ $hours < 1 ? 'Baru' : $hours . ' jam' }}
                            </span>
                            @endif
                        </div>
                        <p class="font-mono text-xs mt-0.5" style="color:var(--primary)">
                            {{ $laundry->transaksi->invoice_number ?? '-' }}
                        </p>
                        <p class="text-xs mt-1" style="color:var(--text-soft)">
                            {{ $laundry->transaksi->customer->name ?? '-' }}
                            @if($laundry->dikembalikan_at)
                            · {{ $laundry->dikembalikan_at->format('d/m/Y H:i') }}
                            @endif
                        </p>

                        {{-- Aksi --}}
                        <div class="flex items-center gap-2 mt-3">
                            <button type="button"
                                    class="btn-mulai-laundry flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white"
                                    style="background:#3B82F6"
                                    data-id="{{ $laundry->id }}"
                                    data-nama="{{ $laundry->produk->name ?? '' }}">
                                <i data-lucide="play" class="w-3 h-3"></i> Mulai Laundry
                            </button>
                            <a href="{{ route('laundry.show', $laundry) }}"
                               class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border transition-colors"
                               style="color:var(--text-soft); border-color:var(--border)">
                                <i data-lucide="eye" class="w-3 h-3"></i> Detail
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="px-5 py-4 border-t flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
             style="border-color:var(--border)">
            <p class="text-xs" style="color:var(--text-soft)">
                Menampilkan {{ $laundries->firstItem() }}–{{ $laundries->lastItem() }}
                dari {{ $laundries->total() }} data
            </p>
            {{ $laundries->links() }}
        </div>

        @endif
    </div>
</div>

{{-- Modal Mulai Laundry --}}
<div id="modalMulaiLaundry"
     class="fixed inset-0 z-50 hidden items-center justify-center p-4"
     style="background:rgba(0,0,0,.5)">
    <div class="card w-full max-w-md">
        <div class="px-5 py-4 border-b flex items-center justify-between"
             style="border-color:var(--border); background:linear-gradient(135deg,#EFF6FF,#fff)">
            <div class="flex items-center gap-2">
                <i data-lucide="play-circle" class="w-5 h-5" style="color:#3B82F6"></i>
                <h3 class="font-playfair font-semibold" style="color:var(--text-dark)">Mulai Proses Laundry</h3>
            </div>
            <button id="closeModal" class="p-1 rounded-lg hover:bg-gray-100">
                <i data-lucide="x" class="w-4 h-4" style="color:var(--text-soft)"></i>
            </button>
        </div>
        <form id="formMulaiLaundry" method="POST" action="">
            @csrf
            <div class="p-5 space-y-4">
                <p class="text-sm" style="color:var(--text-dark)">
                    Mulai proses laundry untuk jas:
                    <strong id="modalNamaProduk" style="color:var(--primary)"></strong>?
                </p>
                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color:var(--text-dark)">
                        Catatan <span style="color:var(--text-soft)">(opsional)</span>
                    </label>
                    <textarea name="catatan" rows="3"
                              placeholder="Kondisi jas, noda khusus, dll..."
                              class="w-full px-3 py-2 rounded-xl text-sm resize-none outline-none transition-all"
                              style="border:1.5px solid var(--border); color:var(--text-dark);
                                     font-family:inherit; background:var(--bg-page)"></textarea>
                </div>
            </div>
            <div class="px-5 py-4 border-t flex justify-end gap-2" style="border-color:var(--border)">
                <button type="button" id="cancelModal"
                        class="px-4 py-2 rounded-xl text-sm font-semibold border transition-colors"
                        style="color:var(--text-soft); border-color:var(--border)">
                    Batal
                </button>
                <button type="submit"
                        class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold text-white transition-all hover:opacity-90"
                        style="background:linear-gradient(135deg,#3B82F6,#1D4ED8)">
                    <i data-lucide="play" class="w-4 h-4"></i> Mulai Laundry
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ─── Lucide icons ─────────────────────────────────────────────────────────
    lucide.createIcons();

    // ─── Checkbox batch ───────────────────────────────────────────────────────
    const checkAll  = document.getElementById('checkAll');
    const checkItems = () => document.querySelectorAll('.check-item');
    const btnBatch  = document.getElementById('btnBatchMulai');

    function updateBatchBtn() {
        const checked = document.querySelectorAll('.check-item:checked').length;
        btnBatch.disabled = checked === 0;
        btnBatch.querySelector('span').textContent = checked > 0
            ? `Mulai Laundry (${checked} terpilih)`
            : 'Mulai Laundry Terpilih';
    }

    checkAll?.addEventListener('change', function () {
        checkItems().forEach(cb => cb.checked = this.checked);
        updateBatchBtn();
    });

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('check-item')) updateBatchBtn();
    });

    // ─── Modal ────────────────────────────────────────────────────────────────
    const modal = document.getElementById('modalMulaiLaundry');

    function openModal() { modal.classList.remove('hidden'); modal.classList.add('flex'); }
    function closeModal() { modal.classList.add('hidden'); modal.classList.remove('flex'); }

    document.getElementById('closeModal')?.addEventListener('click', closeModal);
    document.getElementById('cancelModal')?.addEventListener('click', closeModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

    document.querySelectorAll('.btn-mulai-laundry').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('modalNamaProduk').textContent = this.dataset.nama;
            document.getElementById('formMulaiLaundry').action =
                `{{ url('laundry') }}/${this.dataset.id}/mulai`;
            openModal();
        });
    });

    // ─── Batch mulai laundry ──────────────────────────────────────────────────
    btnBatch?.addEventListener('click', function () {
        const ids = Array.from(document.querySelectorAll('.check-item:checked'))
                        .map(cb => cb.value);
        if (!ids.length) return;
        if (!confirm(`Mulai laundry untuk ${ids.length} item terpilih?`)) return;

        fetch('{{ route("laundry.batch.mulai") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({ ids }),
        })
        .then(r => r.json())
        .then(data => {
            showToast(data.message, data.success ? 'success' : 'error');
            if (data.success) setTimeout(() => location.reload(), 1200);
        })
        .catch(() => showToast('Terjadi kesalahan.', 'error'));
    });

    // ─── Toast ────────────────────────────────────────────────────────────────
    function showToast(message, type = 'success') {
        const colors = {
            success: 'background:#F0FDF4; color:#15803D; border:1px solid #BBF7D0',
            error:   'background:#FFF1F0; color:#C0392B; border:1px solid #FECACA',
        };
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-4 right-4 z-50 px-4 py-3 rounded-xl text-sm font-medium shadow-lg';
        toast.style.cssText = colors[type] || colors.success;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }
});
</script>
@endpush