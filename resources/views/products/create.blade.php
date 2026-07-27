@extends('Layouts.app')

@section('title', 'Tambah Produk Jas')

@section('content')
<div class="space-y-6">

    {{-- ── HEADER ──────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('products.index') }}" class="text-sm flex items-center gap-1 hover:opacity-70 transition-opacity" style="color:var(--text-soft)">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Kembali
                </a>
            </div>
            <h1 class="font-playfair text-2xl font-bold" style="color: var(--text-dark)">Tambah Produk Baru</h1>
            <p class="text-sm mt-0.5" style="color: var(--text-soft)">Isi detail produk jas untuk rental</p>
        </div>
    </div>

    {{-- ── FORM ─────────────────────────────────────────────── --}}
    <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data"
          x-data="{ isLoading: false }" @submit="isLoading = true">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- ── KOLOM KIRI (2/3) ─────────────────────────── --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Informasi Dasar --}}
                <div class="card p-6 space-y-5">
                    <div class="flex items-center gap-2 pb-3 border-b" style="border-color:var(--border)">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:var(--secondary)">
                            <i data-lucide="info" class="w-3.5 h-3.5" style="color:var(--primary)"></i>
                        </div>
                        <h2 class="font-semibold text-sm" style="color:var(--text-dark)">Informasi Dasar</h2>
                    </div>

                    {{-- Nama Produk --}}
                    <div>
                        <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">
                            Nama Produk <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}"
                               placeholder="Contoh: Jas Formal Classic Black"
                               class="form-input @error('name') border-red-400 @enderror">
                        @error('name')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Kode & Kategori --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">
                                Kode Produk <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="code" value="{{ old('code') }}"
                                   placeholder="Contoh: JAS-001"
                                   class="form-input @error('code') border-red-400 @enderror">
                            @error('code')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">
                                Kategori <span class="text-red-500">*</span>
                            </label>
                            <select name="category_id" class="form-input @error('category_id') border-red-400 @enderror">
                                <option value="">Pilih Kategori</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Deskripsi --}}
                    <div>
                        <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Deskripsi</label>
                        <textarea name="description" rows="4"
                                  placeholder="Deskripsi singkat tentang produk..."
                                  class="form-input resize-none @error('description') border-red-400 @enderror">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Detail Fisik --}}
                <div class="card p-6 space-y-5">
                    <div class="flex items-center gap-2 pb-3 border-b" style="border-color:var(--border)">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:var(--secondary)">
                            <i data-lucide="shirt" class="w-3.5 h-3.5" style="color:var(--primary)"></i>
                        </div>
                        <h2 class="font-semibold text-sm" style="color:var(--text-dark)">Detail Fisik</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        {{-- Ukuran --}}
                        <div>
                            <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Ukuran</label>
                            <select name="size" class="form-input @error('size') border-red-400 @enderror">
                                <option value="">Pilih Ukuran</option>
                                @foreach(['XS','S','M','L','XL','XXL'] as $sz)
                                    <option value="{{ $sz }}" {{ old('size') === $sz ? 'selected' : '' }}>{{ $sz }}</option>
                                @endforeach
                            </select>
                            @error('size')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Warna --}}
                        <div>
                            <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Warna</label>
                            <input type="text" name="color" value="{{ old('color') }}"
                                   placeholder="Contoh: Hitam, Navy"
                                   class="form-input @error('color') border-red-400 @enderror">
                            @error('color')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Kondisi --}}
                        <div>
                            <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Kondisi</label>
                            <select name="condition" class="form-input @error('condition') border-red-400 @enderror">
                                <option value="">Pilih Kondisi</option>
                                <option value="excellent" {{ old('condition') === 'excellent' ? 'selected' : '' }}>Excellent</option>
                                <option value="good"      {{ old('condition') === 'good'      ? 'selected' : '' }}>Good</option>
                                <option value="fair"      {{ old('condition') === 'fair'      ? 'selected' : '' }}>Fair</option>
                                <option value="poor"      {{ old('condition') === 'poor'      ? 'selected' : '' }}>Poor</option>
                            </select>
                            @error('condition')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Stok & Harga --}}
                <div class="card p-6 space-y-5">
                    <div class="flex items-center gap-2 pb-3 border-b" style="border-color:var(--border)">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:var(--secondary)">
                            <i data-lucide="package" class="w-3.5 h-3.5" style="color:var(--primary)"></i>
                        </div>
                        <h2 class="font-semibold text-sm" style="color:var(--text-dark)">Stok & Harga</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        {{-- Stok Total --}}
                        <div>
                            <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">
                                Stok Total <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="stock_total" value="{{ old('stock_total', 1) }}" min="0"
                                   class="form-input @error('stock_total') border-red-400 @enderror">
                            @error('stock_total')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Stok Tersedia --}}
                        <div>
                            <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">
                                Stok Tersedia <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="stock_available" value="{{ old('stock_available', 1) }}" min="0"
                                   class="form-input @error('stock_available') border-red-400 @enderror">
                            @error('stock_available')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Harga Sewa --}}
                        <div>
                            <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">
                                Harga Sewa/Hari <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-medium" style="color:var(--text-soft)">Rp</span>
                                <input type="number" name="rental_price" value="{{ old('rental_price') }}" min="0"
                                       placeholder="0"
                                       class="form-input pl-10 @error('rental_price') border-red-400 @enderror">
                            </div>
                            @error('rental_price')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

            </div>

            {{-- ── KOLOM KANAN (1/3) ────────────────────────── --}}
            <div class="space-y-6">

                {{-- Foto Produk --}}
                <div class="card p-6 space-y-4">
                    <div class="flex items-center gap-2 pb-3 border-b" style="border-color:var(--border)">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:var(--secondary)">
                            <i data-lucide="image" class="w-3.5 h-3.5" style="color:var(--primary)"></i>
                        </div>
                        <h2 class="font-semibold text-sm" style="color:var(--text-dark)">Foto Produk</h2>
                    </div>

                    <div id="drop-zone"
                         class="border-2 border-dashed rounded-xl p-6 text-center cursor-pointer transition-colors"
                         style="border-color:var(--border)"
                         onclick="document.getElementById('image-input').click()">
                        <div id="preview-wrap" class="hidden mb-3">
                            <img id="preview-img" src="#" alt="Preview" class="w-full h-40 object-cover rounded-lg">
                        </div>
                        <div id="upload-placeholder">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center mx-auto mb-3" style="background:var(--secondary)">
                                <i data-lucide="upload-cloud" class="w-6 h-6" style="color:var(--primary)"></i>
                            </div>
                            <p class="text-sm font-medium" style="color:var(--text-dark)">Klik atau seret foto ke sini</p>
                            <p class="text-xs mt-1" style="color:var(--text-soft)">PNG, JPG, WEBP — Maks. 2MB</p>
                        </div>
                        <input type="file" id="image-input" name="image" accept="image/*" class="hidden">
                    </div>
                    @error('image')
                        <p class="text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Status & Publish --}}
                <div class="card p-6 space-y-4">
                    <div class="flex items-center gap-2 pb-3 border-b" style="border-color:var(--border)">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:var(--secondary)">
                            <i data-lucide="toggle-right" class="w-3.5 h-3.5" style="color:var(--primary)"></i>
                        </div>
                        <h2 class="font-semibold text-sm" style="color:var(--text-dark)">Status Produk</h2>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Status</label>
                        <select name="status" class="form-input @error('status') border-red-400 @enderror">
                            <option value="available"   {{ old('status','available') === 'available'   ? 'selected' : '' }}>Tersedia</option>
                            <option value="maintenance" {{ old('status') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                        </select>
                        @error('status')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Catatan --}}
                <div class="card p-6 space-y-4">
                    <div class="flex items-center gap-2 pb-3 border-b" style="border-color:var(--border)">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:var(--secondary)">
                            <i data-lucide="file-text" class="w-3.5 h-3.5" style="color:var(--primary)"></i>
                        </div>
                        <h2 class="font-semibold text-sm" style="color:var(--text-dark)">Catatan Internal</h2>
                    </div>
                    <textarea name="notes" rows="3"
                              placeholder="Catatan khusus tentang produk ini..."
                              class="form-input resize-none text-sm">{{ old('notes') }}</textarea>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-col gap-3">
                    <button type="submit" class="btn-primary w-full justify-center"
                            data-no-loading
                            :disabled="isLoading"
                            :class="isLoading ? 'btn-loading' : ''">
                        <template x-if="isLoading">
                            <span class="btn-spinner"></span>
                        </template>
                        <template x-if="!isLoading">
                            <i data-lucide="save" class="w-4 h-4"></i>
                        </template>
                        <span x-text="isLoading ? '\u00A0Memproses...' : 'Simpan Produk'"></span>
                    </button>
                    <a href="{{ route('products.index') }}" class="btn-secondary w-full justify-center text-center">
                        Batal
                    </a>
                </div>
            </div>

        </div>
    </form>
</div>

@push('scripts')
<script>
    lucide.createIcons();

    // Image preview
    const input    = document.getElementById('image-input');
    const preview  = document.getElementById('preview-img');
    const prevWrap = document.getElementById('preview-wrap');
    const placeholder = document.getElementById('upload-placeholder');
    const dropZone = document.getElementById('drop-zone');

    input.addEventListener('change', function () {
        showPreview(this.files[0]);
    });

    // Drag & drop
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.style.borderColor = 'var(--primary)'; });
    dropZone.addEventListener('dragleave', () => { dropZone.style.borderColor = 'var(--border)'; });
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.style.borderColor = 'var(--border)';
        const file = e.dataTransfer.files[0];
        if (file) {
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            showPreview(file);
        }
    });

    function showPreview(file) {
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            preview.src     = e.target.result;
            prevWrap.classList.remove('hidden');
            placeholder.classList.add('hidden');
        };
        reader.readAsDataURL(file);
    }
</script>
@endpush
@endsection
