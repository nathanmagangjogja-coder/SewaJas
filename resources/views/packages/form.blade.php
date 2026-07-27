@extends('layouts.app')
@section('title', isset($package->id) ? 'Edit Paket' : 'Tambah Paket')
@section('page-title', isset($package->id) ? 'Edit Paket Sewa' : 'Tambah Paket Sewa')

@section('content')
<div class="max-w-2xl mx-auto space-y-5" x-data="packageForm()">

    <div class="flex items-center gap-3">
        <a href="{{ route('packages.index') }}"
           class="p-2 rounded-xl transition-colors hover:bg-[var(--bg-soft)]">
            <i data-lucide="arrow-left" class="w-5 h-5" style="color:var(--text-soft)"></i>
        </a>
        <div>
            <h1 class="font-playfair text-xl font-bold" style="color:var(--text-dark)">
                {{ isset($package->id) ? 'Edit Paket' : 'Tambah Paket Baru' }}
            </h1>
        </div>
    </div>

    <form action="{{ isset($package->id) ? route('packages.update', $package) : route('packages.store') }}"
          method="POST" class="space-y-5">
        @csrf
        @if(isset($package->id)) @method('PUT') @endif

        {{-- Info Dasar --}}
        <div class="card p-5 space-y-4">
            <h3 class="font-semibold text-sm" style="color:var(--text-dark)">Informasi Paket</h3>

            <div>
                <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">
                    Nama Paket <span class="text-red-400">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name', $package->name) }}"
                       class="form-input w-full" placeholder="Contoh: Paket 3 Hari" required>
                @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">
                        Durasi (hari) <span class="text-red-400">*</span>
                    </label>
                    <input type="number" name="duration_days"
                           value="{{ old('duration_days', $package->duration_days) }}"
                           x-model.number="durationDays"
                           class="form-input w-full" min="0" required>
                    <p class="text-xs mt-1" style="color:var(--text-soft)">
                        Isi <strong>0</strong> untuk paket custom (durasi isi manual saat transaksi)
                    </p>
                    @error('duration_days') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">
                        Urutan Tampil
                    </label>
                    <input type="number" name="sort_order"
                           value="{{ old('sort_order', $package->sort_order ?? 1) }}"
                           class="form-input w-full" min="0">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">
                    Deskripsi
                </label>
                <textarea name="description" rows="2"
                          class="form-input w-full" placeholder="Cocok untuk acara ...">{{ old('description', $package->description) }}</textarea>
            </div>

            <div class="flex items-center gap-3">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="sr-only peer"
                           {{ old('is_active', $package->is_active ?? true) ? 'checked' : '' }}>
                    <div class="w-10 h-5 rounded-full peer peer-checked:bg-[var(--primary)]"
                         style="background:var(--border)"></div>
                    <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full transition-all
                                peer-checked:translate-x-5 shadow"></div>
                </label>
                <span class="text-sm font-medium" style="color:var(--text-dark)">Paket Aktif</span>
            </div>
        </div>

        {{-- Pengaturan Denda --}}
        <div class="card p-5 space-y-4">
            <div class="flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-4 h-4" style="color:#D97706"></i>
                <h3 class="font-semibold text-sm" style="color:var(--text-dark)">Pengaturan Denda Keterlambatan</h3>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">
                        Denda per Hari <span class="text-red-400">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" name="penalty_percent"
                               value="{{ old('penalty_percent', $package->penalty_percent ?? 10) }}"
                               x-model.number="penaltyPct"
                               class="form-input w-full pr-8" step="0.5" min="0" max="100" required>
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm font-bold"
                              style="color:var(--text-soft)">%</span>
                    </div>
                    <p class="text-xs mt-1" style="color:var(--text-soft)">
                        Persentase dari subtotal item per hari keterlambatan
                    </p>
                    @error('penalty_percent') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">
                        Maksimum Denda
                    </label>
                    <div class="relative">
                        <input type="number" name="max_penalty_percent"
                               value="{{ old('max_penalty_percent', $package->max_penalty_percent) }}"
                               x-model.number="maxPct"
                               class="form-input w-full pr-8" step="5" min="0" max="500"
                               placeholder="Kosongkan = tanpa batas">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm font-bold"
                              style="color:var(--text-soft)">%</span>
                    </div>
                    <p class="text-xs mt-1" style="color:var(--text-soft)">
                        Total denda tidak akan melebihi persentase ini dari subtotal.
                        Kosongkan jika tanpa batas.
                    </p>
                    @error('max_penalty_percent') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Preview Formula Denda --}}
            <div class="p-4 rounded-xl" style="background:#FFFBEB; border:1px solid #FDE68A">
                <p class="text-xs font-semibold mb-2" style="color:#92400E">Preview Formula Denda</p>
                <p class="text-sm font-medium" style="color:#D97706" x-text="formulaText()"></p>
                <div class="mt-3 grid grid-cols-3 gap-3 text-center">
                    <div>
                        <p class="text-xs" style="color:var(--text-soft)">Terlambat 1 hari</p>
                        <p class="font-bold text-sm" style="color:#D97706"
                           x-text="'Rp ' + formatNumber(simulate(200000, 1))"></p>
                        <p class="text-xs" style="color:var(--text-soft)">(subtotal Rp 200rb)</p>
                    </div>
                    <div>
                        <p class="text-xs" style="color:var(--text-soft)">Terlambat 3 hari</p>
                        <p class="font-bold text-sm" style="color:#D97706"
                           x-text="'Rp ' + formatNumber(simulate(200000, 3))"></p>
                        <p class="text-xs" style="color:var(--text-soft)">(subtotal Rp 200rb)</p>
                    </div>
                    <div>
                        <p class="text-xs" style="color:var(--text-soft)">Terlambat 7 hari</p>
                        <p class="font-bold text-sm" style="color:#D97706"
                           x-text="'Rp ' + formatNumber(simulate(200000, 7))"></p>
                        <p class="text-xs" style="color:var(--text-soft)">(subtotal Rp 200rb)</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Action --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('packages.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-primary flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i>
                {{ isset($package->id) ? 'Simpan Perubahan' : 'Buat Paket' }}
            </button>
        </div>
    </form>
</div>

<script>
function packageForm() {
    return {
        durationDays: {{ old('duration_days', $package->duration_days ?? 3) }},
        penaltyPct: {{ old('penalty_percent', $package->penalty_percent ?? 10) }},
        maxPct: {{ old('max_penalty_percent', $package->max_penalty_percent ?? 'null') }},

        simulate(subtotal, days) {
            const pct = parseFloat(this.penaltyPct) || 0;
            let pen = subtotal * (pct / 100) * days;
            if (this.maxPct) {
                pen = Math.min(pen, subtotal * (parseFloat(this.maxPct) / 100));
            }
            return Math.round(pen);
        },

        formulaText() {
            const pct = parseFloat(this.penaltyPct) || 0;
            let txt = `Denda = Subtotal × ${pct}% × Jumlah Hari Terlambat`;
            if (this.maxPct) {
                txt += ` (maks. ${this.maxPct}% dari Subtotal)`;
            }
            return txt;
        },

        formatNumber(n) {
            return new Intl.NumberFormat('id-ID').format(n);
        }
    }
}
</script>
@endsection
