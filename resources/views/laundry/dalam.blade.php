@extends('layouts.app')

@section('title', 'Dalam Laundry')

@section('content')
<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="font-playfair text-2xl font-bold" style="color:var(--text-dark)">
                Dalam Laundry
            </h1>
            <p class="text-sm mt-0.5" style="color:var(--text-soft)">
                Jas yang sedang dalam proses laundry
            </p>
        </div>
        <div class="flex items-center gap-2">
        <a href="{{ route('laundry.index') }}"
           class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold border transition-colors"
           style="color:var(--text-soft); border-color:var(--border)">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Kembali</span>
        </a>
        <button id="btnBatchSelesai" disabled

                class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white
                       transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                style="background: linear-gradient(135deg, #10B981, #059669)">
            <i data-lucide="check" class="w-4 h-4"></i>
            <span>Selesai Laundry Terpilih</span>
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

    {{-- Tabel --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b flex items-center justify-between" style="border-color:var(--border)">
            <div class="flex items-center gap-2">
                <i data-lucide="loader-2" class="w-4 h-4" style="color:#3B82F6"></i>
                <h3 class="font-playfair font-semibold" style="color:var(--text-dark)">Sedang Dilaundry</h3>
                <span class="badge text-[10px]" style="background:#DBEAFE;color:#1D4ED8">{{ $laundries->total() }}</span>
            </div>
        </div>

        @if($laundries->isEmpty())
        <div class="py-16 text-center">
            <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-3" style="color:#D6B98C; opacity:.5"></i>
            <p class="text-sm" style="color:var(--text-soft)">Tidak ada jas yang sedang dilaundry</p>
        </div>

        @else

        {{-- Desktop Table --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full elegant-table">
                <thead>
                    <tr>
                        <th class="w-10">
                            <input type="checkbox" id="checkAll"
                                   class="w-4 h-4 rounded cursor-pointer accent-emerald-500">
                        </th>
                        <th class="text-left">#</th>
                        <th class="text-left">Jas / Produk</th>
                        <th class="text-left">Customer</th>
                        <th class="text-left">Mulai Laundry</th>
                        <th class="text-left">Durasi</th>
                        <th class="text-left">Diproses Oleh</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($laundries as $index => $laundry)
                    @php $durasi = (int) ($laundry->mulai_laundry_at?->diffInHours(now()) ?? 0); @endphp
                    <tr>
                        <td>
                            <input type="checkbox" class="check-item w-4 h-4 rounded cursor-pointer accent-emerald-500"
                                   value="{{ $laundry->id }}">
                        </td>
                        <td class="text-sm" style="color:var(--text-soft)">
                            {{ $laundries->firstItem() + $index }}
                        </td>
                        <td>
                            <p class="text-sm font-semibold" style="color:var(--text-dark)">
                                {{ $laundry->produk->name ?? '-' }}
                            </p>
                            <p class="font-mono text-[11px]" style="color:var(--primary)">
                                {{ $laundry->transaksi->invoice_number ?? '-' }}
                            </p>
                        </td>
                        <td class="text-sm" style="color:var(--text-dark)">
                            {{ $laundry->transaksi->customer->name ?? '-' }}
                        </td>
                        <td>
                            <p class="text-sm" style="color:var(--text-dark)">
                                {{ $laundry->mulai_laundry_at?->format('d/m/Y') ?? '-' }}
                            </p>
                            <p class="text-[11px]" style="color:var(--text-soft)">
                                {{ $laundry->mulai_laundry_at?->format('H:i') ?? '' }}
                            </p>
                        </td>
                        <td>
                            @if($laundry->mulai_laundry_at)
                            <span class="badge text-[10px]" style="background:#DBEAFE;color:#1D4ED8">
                                {{ $durasi < 1 ? 'Baru saja' : $durasi . ' jam' }}
                            </span>
                            @else
                            <span class="text-xs" style="color:var(--text-soft)">-</span>
                            @endif
                        </td>
                        <td class="text-sm" style="color:var(--text-soft)">
                            {{ $laundry->diprosesByUser->name ?? '-' }}
                        </td>
                        <td>
                            <div class="flex items-center justify-center gap-1.5">
                                <button type="button"
                                        class="btn-selesai-laundry flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white transition-all hover:opacity-90"
                                        style="background:#10B981"
                                        data-id="{{ $laundry->id }}"
                                        data-nama="{{ $laundry->produk->name ?? '' }}">
                                    <i data-lucide="check" class="w-3 h-3"></i> Selesai
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
            @foreach($laundries as $laundry)
            @php $durasi = (int) ($laundry->mulai_laundry_at?->diffInHours(now()) ?? 0); @endphp
            <div class="p-4">
                <div class="flex items-start gap-3">
                    <input type="checkbox" class="check-item mt-1 w-4 h-4 rounded accent-emerald-500"
                           value="{{ $laundry->id }}">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                         style="background:#EFF6FF">
                        <i data-lucide="loader-2" class="w-5 h-5" style="color:#3B82F6"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-semibold text-sm truncate" style="color:var(--text-dark)">
                                {{ $laundry->produk->name ?? '-' }}
                            </p>
                            @if($laundry->mulai_laundry_at)
                            <span class="badge text-[10px] flex-shrink-0" style="background:#DBEAFE;color:#1D4ED8">
                                {{ $durasi < 1 ? 'Baru' : $durasi . ' jam' }}
                            </span>
                            @endif
                        </div>
                        <p class="font-mono text-xs mt-0.5" style="color:var(--primary)">
                            {{ $laundry->transaksi->invoice_number ?? '-' }}
                        </p>
                        <p class="text-xs mt-1" style="color:var(--text-soft)">
                            {{ $laundry->transaksi->customer->name ?? '-' }}
                            @if($laundry->mulai_laundry_at)
                            · Mulai {{ $laundry->mulai_laundry_at->format('d/m H:i') }}
                            @endif
                        </p>
                        @if($laundry->diprosesByUser)
                        <p class="text-xs mt-0.5" style="color:var(--text-soft)">
                            Oleh: {{ $laundry->diprosesByUser->name }}
                        </p>
                        @endif
                        <div class="flex items-center gap-2 mt-3">
                            <button type="button"
                                    class="btn-selesai-laundry flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white"
                                    style="background:#10B981"
                                    data-id="{{ $laundry->id }}"
                                    data-nama="{{ $laundry->produk->name ?? '' }}">
                                <i data-lucide="check" class="w-3 h-3"></i> Selesai Laundry
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

{{-- Modal Selesai Laundry --}}
<div id="modalSelesaiLaundry"
     class="fixed inset-0 z-50 hidden items-center justify-center p-4"
     style="background:rgba(0,0,0,.5)">
    <div class="card w-full max-w-md">
        <div class="px-5 py-4 border-b flex items-center justify-between"
             style="border-color:var(--border); background:linear-gradient(135deg,#F0FDF4,#fff)">
            <div class="flex items-center gap-2">
                <i data-lucide="check-circle-2" class="w-5 h-5" style="color:#10B981"></i>
                <h3 class="font-playfair font-semibold" style="color:var(--text-dark)">Selesai Laundry</h3>
            </div>
            <button id="closeModal" class="p-1 rounded-lg hover:bg-gray-100">
                <i data-lucide="x" class="w-4 h-4" style="color:var(--text-soft)"></i>
            </button>
        </div>
        <form id="formSelesaiLaundry" method="POST" action="">
            @csrf
            <div class="p-5 space-y-4">
                <div class="flex items-start gap-3 px-3 py-3 rounded-xl"
                     style="background:#F0FDF4; border:1px solid #BBF7D0">
                    <i data-lucide="info" class="w-4 h-4 mt-0.5 flex-shrink-0" style="color:#15803D"></i>
                    <p class="text-xs" style="color:#15803D">
                        Stok jas akan otomatis bertambah setelah selesai laundry.
                    </p>
                </div>
                <p class="text-sm" style="color:var(--text-dark)">
                    Tandai laundry selesai untuk:
                    <strong id="modalNamaProdukSelesai" style="color:var(--primary)"></strong>?
                </p>
                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color:var(--text-dark)">
                        Catatan <span style="color:var(--text-soft)">(opsional)</span>
                    </label>
                    <textarea name="catatan" rows="3"
                              placeholder="Kondisi jas setelah laundry..."
                              class="w-full px-3 py-2 rounded-xl text-sm resize-none outline-none"
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
                        style="background:linear-gradient(135deg,#10B981,#059669)">
                    <i data-lucide="check" class="w-4 h-4"></i> Konfirmasi Selesai
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    lucide.createIcons();

    // ── Checkbox batch ────────────────────────────────────────────────────────
    const checkAll = document.getElementById('checkAll');
    const btnBatch = document.getElementById('btnBatchSelesai');

    function updateBatchBtn() {
        const n = document.querySelectorAll('.check-item:checked').length;
        btnBatch.disabled = n === 0;
        btnBatch.querySelector('span').textContent = n > 0
            ? `Selesai Laundry (${n} terpilih)`
            : 'Selesai Laundry Terpilih';
    }

    checkAll?.addEventListener('change', function () {
        document.querySelectorAll('.check-item').forEach(cb => cb.checked = this.checked);
        updateBatchBtn();
    });
    document.addEventListener('change', e => {
        if (e.target.classList.contains('check-item')) updateBatchBtn();
    });

    // ── Modal ─────────────────────────────────────────────────────────────────
    const modal = document.getElementById('modalSelesaiLaundry');
    const openModal  = () => { modal.classList.remove('hidden'); modal.classList.add('flex'); };
    const closeModal = () => { modal.classList.add('hidden');   modal.classList.remove('flex'); };

    document.getElementById('closeModal')?.addEventListener('click', closeModal);
    document.getElementById('cancelModal')?.addEventListener('click', closeModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

    document.querySelectorAll('.btn-selesai-laundry').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('modalNamaProdukSelesai').textContent = this.dataset.nama;
            document.getElementById('formSelesaiLaundry').action =
                `{{ url('laundry') }}/${this.dataset.id}/selesai`;
            openModal();
        });
    });

    // ── Batch selesai ─────────────────────────────────────────────────────────
    btnBatch?.addEventListener('click', function () {
        const ids = Array.from(document.querySelectorAll('.check-item:checked')).map(cb => cb.value);
        if (!ids.length) return;
        if (!confirm(`Tandai ${ids.length} item selesai laundry? Stok akan otomatis bertambah.`)) return;

        fetch('{{ route("laundry.batch.selesai") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ ids }),
        })
        .then(r => r.json())
        .then(data => {
            showToast(data.message, data.success ? 'success' : 'error');
            if (data.success) setTimeout(() => location.reload(), 1500);
        })
        .catch(() => showToast('Terjadi kesalahan.', 'error'));
    });

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