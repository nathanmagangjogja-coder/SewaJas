@extends('Layouts.app')

@section('title', 'Tambah Kategori')

@section('content')
<div class="max-w-xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ route('categories.index') }}" class="btn-secondary p-2">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div>
            <h1 class="font-playfair text-2xl font-bold" style="color: var(--text-dark)">Tambah Kategori</h1>
            <p class="text-sm mt-0.5" style="color: var(--text-soft)">Buat kategori produk baru</p>
        </div>
    </div>

    <div class="card p-6">
        <form method="POST" action="{{ route('categories.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Nama Kategori <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}"
                    class="form-input @error('name') border-red-400 @enderror"
                    placeholder="cth: Jas Formal"
                    oninput="generateSlug(this.value)">
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Slug <span class="text-red-500">*</span></label>
                <input type="text" name="slug" id="slug" value="{{ old('slug') }}"
                    class="form-input @error('slug') border-red-400 @enderror"
                    placeholder="cth: jas-formal">
                @error('slug')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Icon (Lucide)</label>
                <input type="text" name="icon" value="{{ old('icon') }}"
                    class="form-input"
                    placeholder="cth: briefcase, award, shirt">
                <p class="text-xs mt-1" style="color:var(--text-soft)">Nama icon dari <a href="https://lucide.dev/icons" target="_blank" class="underline">lucide.dev</a></p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Urutan Tampil</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}"
                    class="form-input" min="0">
            </div>

            <div class="flex items-center gap-3">
                <input type="checkbox" name="is_active" id="is_active" value="1"
                    {{ old('is_active', true) ? 'checked' : '' }}
                    class="w-4 h-4 rounded">
                <label for="is_active" class="text-sm font-medium" style="color:var(--text-dark)">Kategori Aktif</label>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary">
                    <i data-lucide="save" class="w-4 h-4"></i> Simpan
                </button>
                <a href="{{ route('categories.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
lucide.createIcons();
function generateSlug(val) {
    document.getElementById('slug').value = val.toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .trim().replace(/\s+/g, '-');
}
</script>
@endpush
@endsection
