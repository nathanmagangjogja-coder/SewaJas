@extends('Layouts.app')

@section('title', 'Edit Cabang')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ route('branches.index') }}" class="btn-secondary p-2">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div>
            <h1 class="font-playfair text-2xl font-bold" style="color: var(--text-dark)">Edit Cabang</h1>
            <p class="text-sm mt-0.5" style="color: var(--text-soft)">{{ $branch->name }}</p>
        </div>
    </div>

    <div class="card p-6">
        <form method="POST" action="{{ route('branches.update', $branch) }}" class="space-y-4">
            @csrf @method('PATCH')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Nama Cabang <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $branch->name) }}"
                        class="form-input @error('name') border-red-400 @enderror">
                    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Kode Cabang <span class="text-red-500">*</span></label>
                    <input type="text" name="code" value="{{ old('code', $branch->code) }}"
                        class="form-input @error('code') border-red-400 @enderror"
                        style="text-transform:uppercase">
                    @error('code')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">No. Telepon</label>
                    <input type="text" name="phone" value="{{ old('phone', $branch->phone) }}" class="form-input">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Email</label>
                    <input type="email" name="email" value="{{ old('email', $branch->email) }}"
                        class="form-input @error('email') border-red-400 @enderror">
                    @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Kota</label>
                    <input type="text" name="city" value="{{ old('city', $branch->city) }}" class="form-input">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Provinsi</label>
                    <input type="text" name="province" value="{{ old('province', $branch->province) }}" class="form-input">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Alamat Lengkap</label>
                    <textarea name="address" rows="3" class="form-input">{{ old('address', $branch->address) }}</textarea>
                </div>

                <div class="sm:col-span-2 flex items-center gap-3">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                        {{ old('is_active', $branch->is_active) ? 'checked' : '' }}
                        class="w-4 h-4 rounded">
                    <label for="is_active" class="text-sm font-medium" style="color:var(--text-dark)">Cabang Aktif</label>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary">
                    <i data-lucide="save" class="w-4 h-4"></i> Perbarui
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
