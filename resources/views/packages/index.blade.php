@extends('layouts.app')
@section('title', 'Manajemen Paket Sewa')
@section('page-title', 'Paket Sewa')
@section('subtitle', 'Atur paket durasi dan persentase denda keterlambatan')

@section('content')
<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-playfair text-2xl font-bold" style="color:var(--text-dark)">Paket Sewa</h1>
            <p class="text-sm mt-0.5" style="color:var(--text-soft)">
                Setiap paket memiliki durasi dan persentase denda tersendiri
            </p>
        </div>
        <a href="{{ route('packages.create') }}" class="btn-primary flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Tambah Paket
        </a>
    </div>

    {{-- Info Box --}}
    <div class="p-4 rounded-xl flex items-start gap-3"
         style="background:#FFFBEB; border:1px solid #FDE68A">
        <i data-lucide="info" class="w-4 h-4 mt-0.5 flex-shrink-0" style="color:#D97706"></i>
        <div class="text-sm" style="color:#92400E">
            <strong>Cara kerja denda:</strong>
            Denda dihitung dari <strong>subtotal item</strong> × <strong>persentase per hari</strong> × <strong>jumlah hari terlambat</strong>.
            Jika ada batas maksimum, total denda tidak akan melebihi persentase tersebut dari subtotal.
            <br>Contoh: subtotal Rp 200.000, denda 10%/hari, terlambat 3 hari → denda = Rp 60.000.
        </div>
    </div>

    {{-- Tabel Paket --}}
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr style="background:var(--bg-soft)">
                    <th class="text-left px-5 py-3 text-xs font-semibold" style="color:var(--text-soft)">Urutan</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold" style="color:var(--text-soft)">Nama Paket</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold" style="color:var(--text-soft)">Durasi</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold" style="color:var(--text-soft)">Denda/Hari</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold" style="color:var(--text-soft)">Maks. Denda</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold" style="color:var(--text-soft)">Status</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y" style="border-color:var(--border)">
                @forelse($packages as $pkg)
                <tr class="hover:bg-[var(--bg-soft)] transition-colors">
                    <td class="px-5 py-4">
                        <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold"
                              style="background:var(--bg-soft); color:var(--text-soft)">
                            {{ $pkg->sort_order }}
                        </span>
                    </td>
                    <td class="px-5 py-4">
                        <p class="font-semibold" style="color:var(--text-dark)">{{ $pkg->name }}</p>
                        @if($pkg->description)
                        <p class="text-xs mt-0.5" style="color:var(--text-soft)">{{ $pkg->description }}</p>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        @if($pkg->is_custom)
                        <span class="px-2 py-1 rounded-lg text-xs font-semibold"
                              style="background:#EDE9FE; color:#7C3AED">
                            Custom (bebas)
                        </span>
                        @else
                        <span class="font-semibold" style="color:var(--text-dark)">
                            {{ $pkg->duration_days }} hari
                        </span>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        <span class="px-2.5 py-1 rounded-lg text-sm font-bold"
                              style="background:#FEF3C7; color:#D97706">
                            {{ number_format($pkg->penalty_percent, 0) }}%
                        </span>
                        <span class="text-xs ml-1" style="color:var(--text-soft)">/ hari</span>
                    </td>
                    <td class="px-5 py-4 text-sm" style="color:var(--text-dark)">
                        @if($pkg->max_penalty_percent)
                            <span class="font-medium">{{ number_format($pkg->max_penalty_percent, 0) }}%</span>
                            <span class="text-xs" style="color:var(--text-soft)"> dari subtotal</span>
                        @else
                            <span style="color:var(--text-soft)">Tanpa batas</span>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        @if($pkg->is_active)
                        <span class="badge badge-green text-xs">Aktif</span>
                        @else
                        <span class="badge text-xs" style="background:var(--bg-soft); color:var(--text-soft)">
                            Non-aktif
                        </span>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('packages.edit', $pkg) }}"
                               class="p-1.5 rounded-lg transition-colors hover:bg-[var(--bg-soft)]"
                               title="Edit">
                                <i data-lucide="pencil" class="w-4 h-4" style="color:var(--primary)"></i>
                            </a>
                            <form action="{{ route('packages.destroy', $pkg) }}" method="POST"
                                  onsubmit="return confirm('Hapus paket {{ $pkg->name }}?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="p-1.5 rounded-lg transition-colors hover:bg-red-50"
                                        title="Hapus">
                                    <i data-lucide="trash-2" class="w-4 h-4 text-red-400"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-12 text-center text-sm" style="color:var(--text-soft)">
                        <i data-lucide="package-open" class="w-10 h-10 mx-auto mb-3 opacity-20"></i>
                        <p>Belum ada paket. Buat paket pertama Anda.</p>
                        <a href="{{ route('packages.create') }}" class="btn-primary mt-3 inline-flex items-center gap-2">
                            <i data-lucide="plus" class="w-4 h-4"></i> Buat Paket
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Simulasi Denda --}}
    <div class="card p-5" x-data="dendaSimulator()">
        <h3 class="font-playfair font-semibold mb-4" style="color:var(--text-dark)">
            🧮 Simulator Denda
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-xs font-semibold mb-1.5" style="color:var(--text-soft)">Subtotal Item</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-medium"
                          style="color:var(--text-soft)">Rp</span>
                    <input type="number" x-model="subtotal" min="0" step="10000"
                           class="form-input w-full pl-10" placeholder="200000">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1.5" style="color:var(--text-soft)">Hari Terlambat</label>
                <input type="number" x-model="lateDays" min="1" max="30"
                       class="form-input w-full" placeholder="3">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1.5" style="color:var(--text-soft)">Paket</label>
                <select x-model="selectedPkg" class="form-input w-full">
                    @foreach($packages->where('is_active', true) as $pkg)
                    <option value="{{ $pkg->id }}"
                            data-pct="{{ $pkg->penalty_percent }}"
                            data-max="{{ $pkg->max_penalty_percent ?? '' }}">
                        {{ $pkg->name }} ({{ number_format($pkg->penalty_percent, 0) }}%/hari)
                    </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="p-4 rounded-xl" style="background:var(--bg-soft)" x-show="subtotal > 0 && lateDays > 0">
            <p class="text-xs mb-1" style="color:var(--text-soft)">Estimasi Denda</p>
            <p class="text-2xl font-bold font-playfair" style="color:#D97706"
               x-text="'Rp ' + formatNumber(calculatePenalty())"></p>
            <p class="text-xs mt-1" style="color:var(--text-soft)" x-text="penaltyDetail()"></p>
        </div>
    </div>

</div>

<script>
function dendaSimulator() {
    return {
        subtotal: 200000,
        lateDays: 3,
        selectedPkg: '{{ $packages->where("is_active", true)->first()?->id }}',
        packages: @json($packages->where('is_active', true)->values()),

        get pkg() {
            return this.packages.find(p => p.id == this.selectedPkg);
        },

        calculatePenalty() {
            if (!this.pkg) return 0;
            const sub  = parseFloat(this.subtotal) || 0;
            const days = parseInt(this.lateDays) || 0;
            const pct  = parseFloat(this.pkg.penalty_percent) / 100;
            let   pen  = sub * pct * days;

            if (this.pkg.max_penalty_percent) {
                const maxPen = sub * (parseFloat(this.pkg.max_penalty_percent) / 100);
                pen = Math.min(pen, maxPen);
            }
            return Math.round(pen);
        },

        penaltyDetail() {
            if (!this.pkg) return '';
            const sub = parseFloat(this.subtotal) || 0;
            const pct = parseFloat(this.pkg.penalty_percent);
            const perDay = sub * (pct / 100);
            let detail = `${pct}%/hari × Rp ${this.formatNumber(sub)} × ${this.lateDays} hari = Rp ${this.formatNumber(perDay * this.lateDays)}`;
            if (this.pkg.max_penalty_percent) {
                const max = sub * (parseFloat(this.pkg.max_penalty_percent) / 100);
                if (perDay * this.lateDays > max) {
                    detail += ` → dicap maks ${this.pkg.max_penalty_percent}% = Rp ${this.formatNumber(max)}`;
                }
            }
            return detail;
        },

        formatNumber(n) {
            return new Intl.NumberFormat('id-ID').format(n);
        }
    }
}
</script>
@endsection
