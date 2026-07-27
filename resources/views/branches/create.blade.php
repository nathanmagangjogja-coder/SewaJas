@extends('Layouts.app')

@section('title', 'Tambah Cabang')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ route('branches.index') }}" class="btn-secondary p-2">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div>
            <h1 class="font-playfair text-2xl font-bold" style="color: var(--text-dark)">Tambah Cabang</h1>
            <p class="text-sm mt-0.5" style="color: var(--text-soft)">Daftarkan cabang baru</p>
        </div>
    </div>

    <div class="card p-6">
        <form method="POST" action="{{ route('branches.store') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Nama Cabang <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}"
                        class="form-input @error('name') border-red-400 @enderror"
                        placeholder="cth: Cabang Sleman">
                    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Kode Cabang <span class="text-red-500">*</span></label>
                    <input type="text" name="code" value="{{ old('code') }}"
                        class="form-input @error('code') border-red-400 @enderror"
                        placeholder="cth: SLM01" style="text-transform:uppercase">
                    @error('code')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">No. Telepon</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                        class="form-input" placeholder="cth: 0274-123456">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                        class="form-input @error('email') border-red-400 @enderror"
                        placeholder="cabang@jasrental.id">
                    @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Kota</label>
                    <input type="text" name="city" value="{{ old('city') }}"
                        class="form-input" placeholder="cth: Yogyakarta">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Provinsi</label>
                    <input type="text" name="province" value="{{ old('province') }}"
                        class="form-input" placeholder="cth: DI Yogyakarta">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Alamat Lengkap</label>
                    <textarea name="address" rows="3"
                        class="form-input"
                        placeholder="Alamat lengkap cabang">{{ old('address') }}</textarea>
                </div>

                <div class="sm:col-span-2 flex items-center gap-3">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                        {{ old('is_active', true) ? 'checked' : '' }}
                        class="w-4 h-4 rounded">
                    <label for="is_active" class="text-sm font-medium" style="color:var(--text-dark)">Cabang Aktif</label>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary">
                    <i data-lucide="save" class="w-4 h-4"></i> Simpan
                </button>
                <a href="{{ route('branches.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>lucide.createIcons();</script>
@endpush
@endsection
