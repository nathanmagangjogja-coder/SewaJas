@extends('layouts.app')

@section('title', 'Custom Invoice')
@section('page-title', 'Custom Invoice')

@section('content')
@php
    $s = $settings;
    $logoUrl = $s['invoice_logo_path'] ? asset('storage/' . $s['invoice_logo_path']) : null;
    $initial = [
        'theme' => old('theme', $s['invoice_theme']),
        'primary' => old('primary_color', $s['invoice_primary_color']),
        'heading' => old('heading_color', $s['invoice_heading_color']),
        'text' => old('text_color', $s['invoice_text_color']),
        'muted' => old('muted_color', $s['invoice_muted_color']),
        'company' => old('company_name', $s['invoice_company_name'] ?: config('app.name')),
        'tagline' => old('tagline', $s['invoice_tagline']),
        'footer' => old('footer_text', $s['invoice_footer_text'] ?: 'Dokumen dicetak otomatis'),
        'terms' => old('terms', $s['invoice_terms']),
        'logoUrl' => $logoUrl,
        'themes' => $themes,
    ];
@endphp

<div class="max-w-6xl mx-auto space-y-5" x-data="invoiceSettings({{ Illuminate\Support\Js::from($initial) }})">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="font-playfair text-2xl font-bold" style="color:var(--text-dark)">Custom Invoice</h1>
            <p class="text-sm mt-0.5" style="color:var(--text-soft)">Logo, nama brand, warna, footer, dan konten bawah invoice.</p>
        </div>

        <form method="POST" action="{{ route('invoice-settings.reset') }}"
              onsubmit="return confirm('Reset pengaturan invoice ke default?')">
            @csrf
            <button type="submit" class="btn-secondary">
                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                Reset
            </button>
        </form>
    </div>

    <form method="POST" action="{{ route('invoice-settings.update') }}" enctype="multipart/form-data"
          class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_420px] gap-5 items-start">
        @csrf
        @method('PATCH')

        <div class="space-y-5">
            <div class="card p-5 space-y-4">
                <div class="flex items-center gap-2">
                    <i data-lucide="badge-info" class="w-4 h-4" style="color:var(--primary)"></i>
                    <h2 class="font-semibold text-sm" style="color:var(--text-dark)">Identitas Invoice</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Nama Brand</label>
                        <input type="text" name="company_name" x-model="company"
                               value="{{ old('company_name', $s['invoice_company_name']) }}"
                               class="form-input w-full" placeholder="{{ config('app.name') }}">
                        @error('company_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Tagline</label>
                        <input type="text" name="tagline" x-model="tagline"
                               value="{{ old('tagline', $s['invoice_tagline']) }}"
                               class="form-input w-full" placeholder="Premium Suit Rental">
                        @error('tagline')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Logo Invoice</label>
                        <input type="file" name="logo" accept="image/*" class="form-input w-full"
                               @change="previewLogo($event)">
                        @error('logo')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    @if($logoUrl)
                        <label class="flex items-center gap-2 text-sm pb-2" style="color:var(--text-dark)">
                            <input type="checkbox" name="remove_logo" value="1" class="w-4 h-4 rounded">
                            Hapus logo
                        </label>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <label class="flex items-center gap-3 p-3 rounded-lg border" style="border-color:var(--border); color:var(--text-dark)">
                        <input type="hidden" name="show_logo" value="0">
                        <input type="checkbox" name="show_logo" value="1" class="w-4 h-4 rounded"
                               {{ old('show_logo', $s['invoice_show_logo']) ? 'checked' : '' }}>
                        <span class="text-sm font-medium">Tampilkan logo</span>
                    </label>

                    <label class="flex items-center gap-3 p-3 rounded-lg border" style="border-color:var(--border); color:var(--text-dark)">
                        <input type="hidden" name="use_branch_logo" value="0">
                        <input type="checkbox" name="use_branch_logo" value="1" class="w-4 h-4 rounded"
                               {{ old('use_branch_logo', $s['invoice_use_branch_logo']) ? 'checked' : '' }}>
                        <span class="text-sm font-medium">Logo cabang</span>
                    </label>

                    <label class="flex items-center gap-3 p-3 rounded-lg border" style="border-color:var(--border); color:var(--text-dark)">
                        <input type="hidden" name="show_qr" value="0">
                        <input type="checkbox" name="show_qr" value="1" class="w-4 h-4 rounded"
                               {{ old('show_qr', $s['invoice_show_qr']) ? 'checked' : '' }}>
                        <span class="text-sm font-medium">Tampilkan QR</span>
                    </label>
                </div>
            </div>
            
            <div class="card p-5 space-y-4">
                <div class="flex items-center gap-2">
                    <i data-lucide="text" class="w-4 h-4" style="color:var(--primary)"></i>
                    <h2 class="font-semibold text-sm" style="color:var(--text-dark)">Footer & Label</h2>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Syarat & Ketentuan</label>
                    <textarea name="terms" rows="5" x-model="terms" class="form-input w-full">{{ old('terms', $s['invoice_terms']) }}</textarea>
                    @error('terms')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Footer</label>
                    <textarea name="footer_text" rows="2" x-model="footer" class="form-input w-full" placeholder="TailorJogja.com | +62 ...">{{ old('footer_text', $s['invoice_footer_text']) }}</textarea>
                    @error('footer_text')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Label penerima</label>
                        <input type="text" name="created_label" value="{{ old('created_label', $s['invoice_created_label']) }}"
                               class="form-input w-full">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Label retur</label>
                        <input type="text" name="returned_label" value="{{ old('returned_label', $s['invoice_returned_label']) }}"
                               class="form-input w-full">
                    </div>
                </div>

                <label class="flex items-center gap-3 p-3 rounded-lg border max-w-sm" style="border-color:var(--border); color:var(--text-dark)">
                    <input type="hidden" name="show_watermark" value="0">
                    <input type="checkbox" name="show_watermark" value="1" class="w-4 h-4 rounded"
                           {{ old('show_watermark', $s['invoice_show_watermark']) ? 'checked' : '' }}>
                    <span class="text-sm font-medium">Watermark lunas di PDF</span>
                </label>
            </div>

            <div class="flex justify-end gap-3">
                <button type="submit" class="btn-primary">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Simpan Pengaturan
                </button>
            </div>
        </div>

        <div class="card p-5 xl:sticky xl:top-5">
            <div class="rounded-lg bg-white overflow-hidden border shadow-sm" style="border-color:#E5E7EB">
                <div class="p-5" :style="{ color: text }">
                    <div class="flex justify-between items-start pb-4 border-b-2" :style="{ borderColor: primary }">
                        <div>
                            <template x-if="logoUrl">
                                <img :src="logoUrl" alt="Logo" class="h-10 object-contain mb-2">
                            </template>
                            <h3 class="font-playfair text-xl font-bold" :style="{ color: heading }" x-text="company || '{{ config('app.name') }}'"></h3>
                            <p class="text-[10px] font-bold uppercase tracking-widest mt-1" :style="{ color: primary }" x-text="tagline"></p>
                            <p class="text-xs mt-3 leading-5" :style="{ color: muted }">
                                Cabang Utama<br>Jl. Contoh No. 12<br>Telp: 0274-000000
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold tracking-wider" :style="{ color: primary }">INVOICE</p>
                            <p class="font-mono font-bold text-sm mt-1" :style="{ color: text }">INV202608150001</p>
                            <span class="inline-block mt-3 px-3 py-1 rounded-full text-[10px] font-bold"
                                  style="background:#DCFCE7;color:#15803D">LUNAS</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3 py-4 text-xs border-b" style="border-color:#F0EBE3">
                        <div>
                            <p class="font-bold uppercase mb-2" :style="{ color: primary }">Customer</p>
                            <p class="font-bold">Andi Saputra</p>
                            <p :style="{ color: muted }">0812-0000-0000</p>
                        </div>
                        <div>
                            <p class="font-bold uppercase mb-2" :style="{ color: primary }">Jadwal</p>
                            <p :style="{ color: muted }">Mulai</p>
                            <p class="font-bold">15 Agustus 2026</p>
                        </div>
                        <div>
                            <p class="font-bold uppercase mb-2" :style="{ color: primary }">Total</p>
                            <p class="font-playfair text-lg font-bold" :style="{ color: primary }">Rp 350.000</p>
                        </div>
                    </div>

                    <div class="mt-4 rounded-lg border p-3" style="border-color:#E5E7EB;background:#FAFAFA">
                        <p class="text-[10px] font-bold uppercase mb-2" :style="{ color: primary }">Syarat & Ketentuan</p>
                        <p class="text-xs whitespace-pre-line leading-5" :style="{ color: muted }" x-text="terms"></p>
                    </div>

                    <div class="mt-4 text-center border-t pt-3" style="border-color:#F0EBE3">
                        <p class="text-xs" :style="{ color: muted }" x-text="footer || 'Dokumen dicetak otomatis'"></p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function invoiceSettings(initial) {
    return {
        ...initial,
        applyTheme(key) {
            const theme = this.themes[key];
            if (!theme) return;
            this.primary = theme.primary;
            this.heading = theme.heading;
            this.text = theme.text;
            this.muted = theme.muted;
        },
        previewLogo(event) {
            const file = event.target.files && event.target.files[0];
            if (!file) return;
            this.logoUrl = URL.createObjectURL(file);
        }
    };
}
</script>
@endpush
