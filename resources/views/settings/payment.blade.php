@extends('layouts.app')

@section('title', 'Metode Pembayaran')
@section('page-title', 'Metode Pembayaran')

@section('content')
@php
    $s = $settings;
    $qrisImageUrl = $s['payment_qris_image_url'];
    $initial = [
        'methods' => old('methods', $s['payment_methods_enabled']),
        'banks' => old('banks', count($s['payment_banks']) ? $s['payment_banks'] : [['name' => '', 'account_number' => '', 'account_holder' => '']]),
        'qrisImageUrl' => $qrisImageUrl,
    ];
@endphp

<div class="max-w-5xl mx-auto space-y-5" x-data="paymentSettings({{ Illuminate\Support\Js::from($initial) }})">
    <div>
        <h1 class="font-playfair text-2xl font-bold" style="color:var(--text-dark)">Metode Pembayaran</h1>
        <p class="text-sm mt-0.5" style="color:var(--text-soft)">Atur metode pembayaran yang aktif, rekening bank tujuan, dan foto QRIS toko.</p>
    </div>

    <form method="POST" action="{{ route('payment-settings.update') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @method('PATCH')

        {{-- ── METODE PEMBAYARAN AKTIF ────────────────────────────────────── --}}
        <div class="card p-5 space-y-4">
            <div class="flex items-center gap-2">
                <i data-lucide="wallet" class="w-4 h-4" style="color:var(--primary)"></i>
                <h2 class="font-semibold text-sm" style="color:var(--text-dark)">Metode Aktif</h2>
            </div>
            <p class="text-xs" style="color:var(--text-soft)">Metode yang dinonaktifkan tidak akan muncul di form pembayaran kasir.</p>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach([
                    'cash'     => ['Tunai',    'banknote'],
                    'transfer' => ['Transfer', 'building-2'],
                    'qris'     => ['QRIS',     'qr-code'],
                    'other'    => ['Lainnya',  'credit-card'],
                ] as $val => [$label, $icon])
                <label class="flex items-center gap-2 p-3 rounded-lg border cursor-pointer has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50"
                       style="border-color:var(--border); color:var(--text-dark)">
                    <input type="checkbox" name="methods[]" value="{{ $val }}" class="w-4 h-4 rounded"
                           {{ in_array($val, $initial['methods']) ? 'checked' : '' }}>
                    <i data-lucide="{{ $icon }}" class="w-4 h-4" style="color:var(--primary)"></i>
                    <span class="text-sm font-medium">{{ $label }}</span>
                </label>
                @endforeach
            </div>
            @error('methods')<p class="text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        {{-- ── REKENING BANK ───────────────────────────────────────────────── --}}
        <div class="card p-5 space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i data-lucide="landmark" class="w-4 h-4" style="color:var(--primary)"></i>
                    <h2 class="font-semibold text-sm" style="color:var(--text-dark)">Rekening Bank</h2>
                </div>
                <button type="button" @click="addBank()" class="btn-secondary text-xs px-3 py-1.5">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    Tambah Rekening
                </button>
            </div>
            <p class="text-xs" style="color:var(--text-soft)">
                Rekening ini akan muncul sebagai pilihan "Bank Tujuan" saat kasir memproses pembayaran transfer.
                Nomor rekening akan terisi otomatis begitu bank dipilih.
            </p>

            <div class="space-y-3">
                <template x-for="(bank, index) in banks" :key="index">
                    <div class="grid grid-cols-1 sm:grid-cols-[1fr_1fr_1fr_auto] gap-2 p-3 rounded-lg border" style="border-color:var(--border)">
                        <div>
                            <label class="block text-[11px] font-semibold mb-1" style="color:var(--text-soft)">Nama Bank</label>
                            <input type="text" :name="'banks[' + index + '][name]'" x-model="bank.name"
                                   placeholder="Contoh: BCA" class="form-input w-full text-sm" style="padding-top:.5rem;padding-bottom:.5rem;">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold mb-1" style="color:var(--text-soft)">Nomor Rekening</label>
                            <input type="text" :name="'banks[' + index + '][account_number]'" x-model="bank.account_number"
                                   placeholder="Contoh: 1234567890" class="form-input w-full text-sm" style="padding-top:.5rem;padding-bottom:.5rem;">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold mb-1" style="color:var(--text-soft)">Atas Nama</label>
                            <input type="text" :name="'banks[' + index + '][account_holder]'" x-model="bank.account_holder"
                                   placeholder="Contoh: PT Sewa Jas Jaya" class="form-input w-full text-sm" style="padding-top:.5rem;padding-bottom:.5rem;">
                        </div>
                        <div class="flex sm:items-end">
                            <button type="button" @click="removeBank(index)"
                                    class="w-full sm:w-auto flex items-center justify-center gap-1 px-3 py-2 rounded-lg text-xs font-semibold text-red-600 hover:bg-red-50 border border-red-200">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                <span class="sm:hidden">Hapus</span>
                            </button>
                        </div>
                    </div>
                </template>

                <p x-show="banks.length === 0" class="text-xs text-center py-3" style="color:var(--text-soft)">
                    Belum ada rekening bank. Klik "Tambah Rekening" untuk menambahkan.
                </p>
            </div>
            @error('banks')<p class="text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        {{-- ── QRIS ─────────────────────────────────────────────────────────── --}}
        <div class="card p-5 space-y-4">
            <div class="flex items-center gap-2">
                <i data-lucide="qr-code" class="w-4 h-4" style="color:var(--primary)"></i>
                <h2 class="font-semibold text-sm" style="color:var(--text-dark)">Foto QRIS</h2>
            </div>
            <p class="text-xs" style="color:var(--text-soft)">
                Unggah foto/screenshot QRIS statis milik toko. Foto akan otomatis disesuaikan ke bentuk persegi
                sesuai resolusi yang dipilih, supaya ukurannya konsisten dan ringan ditampilkan di form pembayaran.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-[1fr_180px] gap-5 items-start">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-4 items-end">
                        <div>
                            <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Foto QRIS</label>
                            <input type="file" name="qris_image" accept="image/*" class="form-input w-full"
                                   @change="previewQris($event)">
                            @error('qris_image')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        @if($qrisImageUrl)
                            <label class="flex items-center gap-2 text-sm pb-2" style="color:var(--text-dark)">
                                <input type="checkbox" name="remove_qris_image" value="1" class="w-4 h-4 rounded"
                                       @change="if($event.target.checked) qrisImageUrl = null">
                                Hapus foto
                            </label>
                        @endif
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Resolusi Barcode</label>
                        <select name="qris_resolution" class="form-input w-full">
                            @foreach($resolutions as $res)
                                <option value="{{ $res }}" {{ (int) $s['payment_qris_resolution'] === $res ? 'selected' : '' }}>
                                    {{ $res }} × {{ $res }} px {{ $res <= 256 ? '(hemat data)' : ($res >= 1024 ? '(paling tajam)' : '') }}
                                </option>
                            @endforeach
                        </select>
                        @error('qris_resolution')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Nama Merchant</label>
                        <input type="text" name="qris_merchant_name"
                               value="{{ old('qris_merchant_name', $s['payment_qris_merchant_name']) }}"
                               placeholder="Contoh: Sewa Jas Jaya" class="form-input w-full">
                        <p class="text-[11px] mt-1" style="color:var(--text-soft)">Ditampilkan sebagai keterangan di bawah QR (opsional).</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Channel QRIS</label>
                        <input type="text" name="qris_channels"
                               value="{{ old('qris_channels', implode(', ', $s['payment_qris_channels'])) }}"
                               placeholder="Contoh: GoPay, OVO, Dana, ShopeePay" class="form-input w-full">
                        <p class="text-[11px] mt-1" style="color:var(--text-soft)">
                            Pisahkan dengan koma. Muncul sebagai pilihan "QRIS via" di form pembayaran, digabung dengan daftar bank di atas.
                        </p>
                        @error('qris_channels')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="flex flex-col items-center gap-2 p-3 rounded-lg border" style="border-color:var(--border); background:var(--bg-main)">
                    <template x-if="qrisImageUrl">
                        <img :src="qrisImageUrl" alt="Preview QRIS" class="w-36 h-36 rounded-lg object-contain bg-white p-2 border" style="border-color:var(--border)">
                    </template>
                    <template x-if="!qrisImageUrl">
                        <div class="w-36 h-36 rounded-lg flex items-center justify-center border-2 border-dashed" style="border-color:var(--border)">
                            <i data-lucide="qr-code" class="w-8 h-8" style="color:var(--text-soft)"></i>
                        </div>
                    </template>
                    <p class="text-[11px] text-center" style="color:var(--text-soft)">Preview foto QRIS</p>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary">
                <i data-lucide="save" class="w-4 h-4"></i>
                Simpan Pengaturan
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function paymentSettings(initial) {
    return {
        banks: initial.banks,
        qrisImageUrl: initial.qrisImageUrl,
        addBank() {
            this.banks.push({ name: '', account_number: '', account_holder: '' });
        },
        removeBank(index) {
            this.banks.splice(index, 1);
        },
        previewQris(event) {
            const file = event.target.files && event.target.files[0];
            if (!file) return;
            this.qrisImageUrl = URL.createObjectURL(file);
        },
    };
}
</script>
@endpush
