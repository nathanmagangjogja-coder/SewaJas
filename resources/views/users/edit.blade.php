@extends('layouts.app')
@section('title','Edit Pengguna — ' . $user->name)
@section('page-title','Edit Pengguna')
@section('subtitle','Ubah data akun pengguna')

@section('content')
<div class="space-y-6">

    {{-- ── HEADER ──────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('users.index') }}"
               class="p-2 rounded-xl hover:opacity-70 transition-opacity"
               style="background:var(--secondary)">
                <i data-lucide="arrow-left" class="w-4 h-4" style="color:var(--primary)"></i>
            </a>
            <div>
                <h1 class="font-playfair text-2xl font-bold" style="color:var(--text-dark)">Edit Pengguna</h1>
                <p class="text-sm" style="color:var(--text-soft)">{{ $user->email }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="badge {{ $user->is_active ? 'badge-green' : 'badge-red' }}">
                {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
            </span>
            <span class="badge
                {{ match($user->role) {
                    'super_admin' => 'badge-gold',
                    'admin_toko'  => 'badge-blue',
                    'sales'       => 'badge-green',
                    default       => 'badge-gray'
                } }}">
                {{ ucfirst(str_replace('_', ' ', $user->role ?? '-')) }}
            </span>
        </div>
    </div>

    {{-- ── FORM ─────────────────────────────────────────────── --}}
    <form method="POST" action="{{ route('users.update', $user) }}" enctype="multipart/form-data"
          x-data="{ isLoading: false }" @submit="isLoading = true">
        @csrf
        @method('PATCH')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- ── KOLOM KIRI (2/3) ─────────────────────────── --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Informasi Akun --}}
                <div class="card p-6 space-y-5">
                    <div class="flex items-center gap-2 pb-3 border-b" style="border-color:var(--border)">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:var(--secondary)">
                            <i data-lucide="user" class="w-3.5 h-3.5" style="color:var(--primary)"></i>
                        </div>
                        <h2 class="font-semibold text-sm" style="color:var(--text-dark)">Informasi Akun</h2>
                    </div>

                    {{-- Nama --}}
                    <div>
                        <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">
                            Nama Lengkap <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}"
                               class="form-input @error('name') border-red-400 @enderror">
                        @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <i data-lucide="mail" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" style="color:var(--text-soft)"></i>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}"
                                   class="form-input pl-9 @error('email') border-red-400 @enderror">
                        </div>
                        @error('email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    {{-- No. HP --}}
                    <div>
                        <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">No. Telepon</label>
                        <div class="relative">
                            <i data-lucide="phone" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" style="color:var(--text-soft)"></i>
                            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                                   class="form-input pl-9 @error('phone') border-red-400 @enderror">
                        </div>
                        @error('phone')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Password --}}
                <div class="card p-6 space-y-5">
                    <div class="flex items-center gap-2 pb-3 border-b" style="border-color:var(--border)">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:var(--secondary)">
                            <i data-lucide="lock" class="w-3.5 h-3.5" style="color:var(--primary)"></i>
                        </div>
                        <div>
                            <h2 class="font-semibold text-sm" style="color:var(--text-dark)">Ganti Password</h2>
                            <p class="text-xs mt-0.5" style="color:var(--text-soft)">Kosongkan jika tidak ingin mengubah password</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Password Baru</label>
                            <div class="relative">
                                <input type="password" name="password" id="password"
                                       placeholder="Min. 8 karakter"
                                       class="form-input pr-10 @error('password') border-red-400 @enderror">
                                <button type="button" onclick="togglePassword('password','eye-password')"
                                        class="absolute right-3 top-1/2 -translate-y-1/2">
                                    <i data-lucide="eye" id="eye-password" class="w-4 h-4" style="color:var(--text-soft)"></i>
                                </button>
                            </div>
                            @error('password')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Konfirmasi Password</label>
                            <div class="relative">
                                <input type="password" name="password_confirmation" id="password_confirmation"
                                       placeholder="Ulangi password baru"
                                       class="form-input pr-10">
                                <button type="button" onclick="togglePassword('password_confirmation','eye-confirm')"
                                        class="absolute right-3 top-1/2 -translate-y-1/2">
                                    <i data-lucide="eye" id="eye-confirm" class="w-4 h-4" style="color:var(--text-soft)"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- ── KOLOM KANAN (1/3) ────────────────────────── --}}
            <div class="space-y-6">

                {{-- Foto Avatar --}}
                <div class="card p-6 space-y-4">
                    <div class="flex items-center gap-2 pb-3 border-b" style="border-color:var(--border)">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:var(--secondary)">
                            <i data-lucide="image" class="w-3.5 h-3.5" style="color:var(--primary)"></i>
                        </div>
                        <h2 class="font-semibold text-sm" style="color:var(--text-dark)">Foto Profil</h2>
                    </div>

                    <div class="flex flex-col items-center gap-4">
                        <div class="w-24 h-24 rounded-2xl overflow-hidden relative group cursor-pointer"
                             onclick="document.getElementById('avatar-input').click()">
                            <img id="avatar-preview"
                                 src="{{ $user->avatar_url }}"
                                 alt="{{ $user->name }}"
                                 class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <i data-lucide="camera" class="w-5 h-5 text-white"></i>
                            </div>
                        </div>
                        <div class="w-full border-2 border-dashed rounded-xl p-3 text-center cursor-pointer"
                             style="border-color:var(--border)"
                             onclick="document.getElementById('avatar-input').click()">
                            <p class="text-xs" style="color:var(--text-soft)">
                                <span class="font-medium" style="color:var(--primary)">Klik</span> untuk ganti foto
                            </p>
                            <input type="file" id="avatar-input" name="avatar" accept="image/*" class="hidden">
                        </div>
                    </div>
                    @error('avatar')<p class="text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                {{-- Role & Cabang --}}
                <div class="card p-6 space-y-4">
                    <div class="flex items-center gap-2 pb-3 border-b" style="border-color:var(--border)">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:var(--secondary)">
                            <i data-lucide="shield" class="w-3.5 h-3.5" style="color:var(--primary)"></i>
                        </div>
                        <h2 class="font-semibold text-sm" style="color:var(--text-dark)">Hak Akses</h2>
                    </div>

                    {{-- Role --}}
                    <div>
                        <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">
                            Role <span class="text-red-500">*</span>
                        </label>
                        <select name="role" id="role-select"
                                onchange="toggleBranch(this.value)"
                                class="form-input @error('role') border-red-400 @enderror">
                            @foreach($roles as $role)
                            <option value="{{ $role }}" {{ old('role', $user->role) === $role ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $role)) }}
                            </option>
                            @endforeach
                        </select>
                        @error('role')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror

                        <div id="role-info" class="mt-2 rounded-xl p-3 text-xs hidden" style="background:var(--secondary)">
                            <p id="role-info-text" style="color:var(--text-soft)"></p>
                        </div>
                    </div>

                    {{-- Cabang --}}
                    <div id="branch-wrap">
                        <label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Cabang <span class="text-red-500">*</span></label>
                        <select name="branch_id" id="branch-select" required class="form-input @error('branch_id') border-red-400 @enderror">
                            <option value="">Pilih Cabang</option>
                            @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ old('branch_id', $user->branch_id) == $b->id ? 'selected' : '' }}>
                                {{ $b->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('branch_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Info --}}
                <div class="card p-5 space-y-3">
                    <div class="flex items-center gap-2">
                        <i data-lucide="clock" class="w-3.5 h-3.5 flex-shrink-0" style="color:var(--text-soft)"></i>
                        <div>
                            <p class="text-xs" style="color:var(--text-soft)">Terakhir diperbarui</p>
                            <p class="text-sm font-medium" style="color:var(--text-dark)">{{ $user->updated_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <i data-lucide="calendar" class="w-3.5 h-3.5 flex-shrink-0" style="color:var(--text-soft)"></i>
                        <div>
                            <p class="text-xs" style="color:var(--text-soft)">Terdaftar sejak</p>
                            <p class="text-sm font-medium" style="color:var(--text-dark)">{{ $user->created_at->format('d M Y') }}</p>
                        </div>
                    </div>
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
                        <span x-text="isLoading ? '\u00A0Memproses...' : 'Simpan Perubahan'"></span>
                    </button>
                    <a href="{{ route('users.index') }}" class="btn-secondary w-full justify-center text-center">
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

    // Avatar preview
    document.getElementById('avatar-input').addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('avatar-preview').src = e.target.result;
        };
        reader.readAsDataURL(file);
    });

    // Toggle password visibility
    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon  = document.getElementById(iconId);
        if (input.type === 'password') {
            input.type = 'text';
            icon.setAttribute('data-lucide', 'eye-off');
        } else {
            input.type = 'password';
            icon.setAttribute('data-lucide', 'eye');
        }
        lucide.createIcons();
    }

    // Toggle cabang berdasarkan role
    const roleDesc = {
        'super_admin': 'Akses penuh ke semua cabang. Tidak perlu memilih cabang.',
        'admin_toko' : 'Mengelola satu cabang. Wajib memilih cabang.',
        'sales'      : 'Hanya transaksi harian. Wajib memilih cabang.',
    };

    function toggleBranch(role) {
        const wrap = document.getElementById('branch-wrap');
        const info = document.getElementById('role-info');
        const text = document.getElementById('role-info-text');
        const branchSelect = document.getElementById('branch-select');

        wrap.style.opacity       = role === 'super_admin' ? '0.4' : '1';
        wrap.style.pointerEvents = role === 'super_admin' ? 'none' : 'auto';

        // FIX: super_admin tidak wajib cabang, admin_toko & sales wajib
        if (role === 'super_admin') {
            branchSelect.required = false;
        } else {
            branchSelect.required = true;
        }

        if (roleDesc[role]) {
            text.textContent = roleDesc[role];
            info.classList.remove('hidden');
        } else {
            info.classList.add('hidden');
        }
    }

    // Trigger on load
    toggleBranch(document.getElementById('role-select').value);
</script>
@endpush
@endsection