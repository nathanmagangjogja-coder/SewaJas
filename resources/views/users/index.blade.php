@extends('layouts.app')
@section('title','Kelola Pengguna')
@section('page-title','Kelola Pengguna')
@section('subtitle','Manajemen akun & hak akses — Super Admin Only')

@section('content')
<div class="space-y-5">

    {{-- FIX: peringatan akun yang belum dikaitkan ke cabang (tidak bisa login) --}}
    @if($brokenUsersCount > 0)
    <div class="card p-4 flex items-center gap-3" style="background:#FFF1F0;border:1px solid #FECACA">
        <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0" style="color:#C0392B"></i>
        <div>
            <p class="text-sm font-semibold" style="color:#C0392B">
                {{ $brokenUsersCount }} akun belum dikaitkan ke cabang manapun
            </p>
            <p class="text-xs" style="color:#C0392B">
                Akun Admin Toko / Sales tanpa cabang akan otomatis ditolak (403) saat mencoba login.
                Cari badge <strong>"Belum dikaitkan cabang"</strong> di tabel bawah dan lengkapi cabangnya.
            </p>
        </div>
    </div>
    @endif

    {{-- Info Role --}}
    <div class="grid grid-cols-3 gap-4">
        @foreach([
            ['role'=>'super_admin','label'=>'Super Admin','color'=>'#D6B98C','bg'=>'#D6B98C15','icon'=>'shield-check','desc'=>'Akses penuh semua cabang'],
            ['role'=>'admin_toko', 'label'=>'Admin Toko', 'color'=>'#3B82F6','bg'=>'#3B82F615','icon'=>'store',       'desc'=>'Kelola satu cabang'],
            ['role'=>'sales',      'label'=>'Sales',       'color'=>'#10B981','bg'=>'#10B98115','icon'=>'user-check',  'desc'=>'Transaksi harian saja'],
        ] as $r)
        <div class="card p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background:{{ $r['bg'] }}">
                <i data-lucide="{{ $r['icon'] }}" class="w-5 h-5" style="color:{{ $r['color'] }}"></i>
            </div>
            <div>
                <p class="font-semibold text-sm" style="color:var(--text-dark)">
                    {{-- ← ganti: pakai kolom role langsung, bukan Spatie --}}
                    {{ \App\Models\User::where('role', $r['role'])->count() }}
                    <span style="color:{{ $r['color'] }}">{{ $r['label'] }}</span>
                </p>
                <p class="text-xs" style="color:var(--text-soft)">{{ $r['desc'] }}</p>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Filter & Add --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <form method="GET" action="{{ route('users.index') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari nama, email..."
                       class="form-input">
            </div>
            <div>
                <select name="role" class="form-input">
                    <option value="">Semua Role</option>
                    {{-- ← ganti: $roles sekarang array string, bukan collection object --}}
                    @foreach($roles as $role)
                    <option value="{{ $role }}" {{ request('role') === $role ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_', ' ', $role)) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="branch" class="form-input">
                    <option value="">Semua Cabang</option>
                    @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ request('branch') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-secondary">
                <i data-lucide="filter" class="w-4 h-4"></i> Filter
            </button>
            @if(request()->hasAny(['search','role','branch']))
            <a href="{{ route('users.index') }}" class="btn-secondary">
                <i data-lucide="x" class="w-4 h-4"></i> Reset
            </a>
            @endif
        </form>
        <a href="{{ route('users.create') }}" class="btn-primary">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            Tambah Pengguna
        </a>
    </div>

    {{-- Tabel --}}
    <div class="card overflow-hidden">
        <table class="w-full elegant-table">
            <thead>
                <tr>
                    <th class="text-left">#</th>
                    <th class="text-left">Pengguna</th>
                    <th class="text-left">Role</th>
                    <th class="text-left">Cabang</th>
                    <th class="text-left">Kontak</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr class="{{ !$user->is_active ? 'opacity-60' : '' }}">
                    <td class="text-xs" style="color:var(--text-soft)">{{ $users->firstItem() + $loop->index }}</td>

                    {{-- Pengguna --}}
                    <td>
                        <a href="{{ route('users.show', $user) }}" class="flex items-center gap-3 hover:opacity-80 transition-opacity">
                            <img src="{{ $user->avatar_url }}" class="w-9 h-9 rounded-xl object-cover">
                            <div>
                                <p class="font-semibold text-sm" style="color:var(--text-dark)">
                                    {{ $user->name }}
                                    @if($user->id === auth()->id())
                                    <span class="badge badge-gold text-[9px] ml-1">Anda</span>
                                    @endif
                                </p>
                                <p class="text-xs" style="color:var(--text-soft)">{{ $user->email }}</p>
                            </div>
                        </a>
                    </td>

                    {{-- Role ← ganti: pakai $user->role (kolom langsung) --}}
                    <td>
                        <span class="badge text-[11px] px-3 py-1
                            {{ match($user->role) {
                                'super_admin' => 'badge-gold',
                                'admin_toko'  => 'badge-blue',
                                'sales'       => 'badge-green',
                                default       => 'badge-gray'
                            } }}">
                            {{ ucfirst(str_replace('_', ' ', $user->role ?? '-')) }}
                        </span>
                    </td>

                    {{-- Cabang ← ganti: pakai $user->role bukan $roleName --}}
                    <td>
                        @if($user->branch)
                            <p class="text-sm" style="color:var(--text-dark)">{{ $user->branch->name }}</p>
                            <p class="text-xs" style="color:var(--text-soft)">{{ $user->branch->code }}</p>
                        @elseif($user->role === 'super_admin')
                            <span class="text-xs" style="color:var(--primary)">Semua Cabang</span>
                        @else
                            {{-- FIX: perjelas jadi badge agar admin tidak melewatkan akun
                                 yang tidak akan bisa login/load dashboard --}}
                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-1 rounded-full"
                                  style="background:#FFF1F0;color:#C0392B;border:1px solid #FECACA"
                                  title="Akun ini akan diblokir saat login karena belum punya cabang">
                                <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                                Belum dikaitkan cabang
                            </span>
                        @endif
                    </td>

                    {{-- Kontak --}}
                    <td class="text-sm" style="color:var(--text-soft)">{{ $user->phone ?? '-' }}</td>

                    {{-- Status --}}
                    <td class="text-center">
                        <span class="badge {{ $user->is_active ? 'badge-green' : 'badge-red' }}">
                            {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>

                    {{-- Aksi --}}
                    <td>
                        <div class="flex items-center justify-center gap-1">
                            <a href="{{ route('users.show', $user) }}"
                               class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors" title="Lihat Detail"
                               style="color:var(--text-soft)">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                            </a>
                            <a href="{{ route('users.edit', $user) }}"
                               class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors" title="Edit"
                               style="color:var(--text-soft)">
                                <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                            </a>
                            @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('users.toggle', $user) }}">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors"
                                        title="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"
                                        style="color:{{ $user->is_active ? '#C0392B' : '#10B981' }}">
                                    <i data-lucide="{{ $user->is_active ? 'user-x' : 'user-check' }}" class="w-3.5 h-3.5"></i>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('users.destroy', $user) }}"
                                  onsubmit="return confirm('Hapus user {{ $user->name }}?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="p-1.5 rounded-lg hover:bg-red-50 transition-colors"
                                        style="color:#C0392B">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-12 text-center">
                        <i data-lucide="users" class="w-8 h-8 mx-auto mb-2" style="color:var(--border)"></i>
                        <p class="text-sm" style="color:var(--text-soft)">Belum ada pengguna</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($users->hasPages())
        <div class="px-6 py-4 border-t" style="border-color:var(--border)">
            {{ $users->links('components.pagination') }}
        </div>
        @endif
    </div>
</div>
@push('scripts')
<script>lucide.createIcons();</script>
@endpush
@endsection