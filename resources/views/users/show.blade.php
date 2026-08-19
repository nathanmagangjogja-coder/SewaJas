@extends('layouts.app')
@section('title','Detail Pengguna — ' . $user->name)
@section('page-title','Detail Pengguna')
@section('subtitle','Informasi lengkap akun pengguna')

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
                <h1 class="font-playfair text-2xl font-bold" style="color:var(--text-dark)">{{ $user->name }}</h1>
                <p class="text-sm" style="color:var(--text-soft)">{{ $user->email }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('users.edit', $user) }}" class="btn-primary">
                <i data-lucide="edit-2" class="w-4 h-4"></i> Edit
            </a>
            @if($user->id !== auth()->id())
            <form method="POST" action="{{ route('users.toggle', $user) }}">
                @csrf @method('PATCH')
                <button type="submit"
                        class="btn-secondary {{ $user->is_active ? 'text-red-500 hover:bg-red-50' : 'text-green-600 hover:bg-green-50' }}">
                    <i data-lucide="{{ $user->is_active ? 'user-x' : 'user-check' }}" class="w-4 h-4"></i>
                    {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                </button>
            </form>
            <form method="POST" action="{{ route('users.destroy', $user) }}"
                  onsubmit="return confirm('Hapus pengguna {{ $user->name }}? Tindakan tidak bisa dibatalkan.')">
                @csrf @method('DELETE')
                <button type="submit" class="btn-secondary text-red-500 hover:bg-red-50">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    <span class="hidden sm:inline">Hapus</span>
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- FIX: peringatan akun ini belum dikaitkan ke cabang (tidak bisa login) --}}
    @if($user->role !== 'super_admin' && !$user->branch)
    <div class="card p-4 flex items-center gap-3" style="background:#FFF1F0;border:1px solid #FECACA">
        <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0" style="color:#C0392B"></i>
        <div>
            <p class="text-sm font-semibold" style="color:#C0392B">Akun ini belum dikaitkan ke cabang manapun</p>
            <p class="text-xs" style="color:#C0392B">
                Akun dengan role {{ ucfirst(str_replace('_', ' ', $user->role ?? '-')) }} wajib memiliki cabang,
                jika tidak akan otomatis ditolak (403) saat mencoba login. Klik <strong>Edit</strong> untuk melengkapi cabangnya.
            </p>
        </div>
    </div>
    @endif

    {{-- ── MAIN ─────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ── KOLOM KIRI (2/3) ─────────────────────────────── --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Profile Hero Card --}}
            <div class="card p-6">
                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6">

                    {{-- Avatar --}}
                    <div class="relative flex-shrink-0">
                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}"
                             class="w-24 h-24 rounded-2xl object-cover">
                        <span class="absolute -bottom-2 -right-2 w-6 h-6 rounded-full border-2 border-white flex items-center justify-center
                            {{ $user->is_active ? 'bg-green-500' : 'bg-gray-400' }}">
                        </span>
                    </div>

                    {{-- Info --}}
                    <div class="flex-1 text-center sm:text-left">
                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 mb-2">
                            <h2 class="text-xl font-bold font-playfair" style="color:var(--text-dark)">{{ $user->name }}</h2>
                            @if($user->id === auth()->id())
                            <span class="badge badge-gold text-[9px]">Anda</span>
                            @endif
                        </div>
                        <p class="text-sm mb-3" style="color:var(--text-soft)">{{ $user->email }}</p>

                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                            {{-- Role badge --}}
                            <span class="badge text-xs px-3 py-1
                                {{ match($user->role) {
                                    'super_admin' => 'badge-gold',
                                    'admin_toko'  => 'badge-blue',
                                    'sales'       => 'badge-green',
                                    default       => 'badge-gray'
                                } }}">
                                <i data-lucide="{{ match($user->role) {
                                    'super_admin' => 'shield-check',
                                    'admin_toko'  => 'store',
                                    'sales'       => 'user-check',
                                    default       => 'user'
                                } }}" class="w-3 h-3 inline-block mr-1"></i>
                                {{ ucfirst(str_replace('_', ' ', $user->role ?? '-')) }}
                            </span>

                            {{-- Status badge --}}
                            <span class="badge {{ $user->is_active ? 'badge-green' : 'badge-red' }}">
                                {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>

                            {{-- Cabang --}}
                            @if($user->branch)
                            <span class="badge badge-gray text-xs">
                                <i data-lucide="map-pin" class="w-3 h-3 inline-block mr-1"></i>
                                {{ $user->branch->name }}
                            </span>
                            @elseif($user->role === 'super_admin')
                            <span class="badge badge-gray text-xs">
                                <i data-lucide="globe" class="w-3 h-3 inline-block mr-1"></i>
                                Semua Cabang
                            </span>
                            @else
                            <span class="badge text-xs" style="background:#FFF1F0;color:#C0392B;border:1px solid #FECACA">
                                <i data-lucide="alert-triangle" class="w-3 h-3 inline-block mr-1"></i>
                                Belum dikaitkan cabang
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Aktivitas / Statistik (opsional, kirim dari controller jika tersedia) --}}
            @if(isset($stats))
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <div class="stat-card">
                    <p class="text-xs font-medium uppercase tracking-wide" style="color:var(--text-soft)">Total Transaksi</p>
                    <p class="text-2xl font-bold mt-1" style="color:var(--text-dark)">{{ $stats['total_rentals'] ?? 0 }}</p>
                </div>
                <div class="stat-card">
                    <p class="text-xs font-medium uppercase tracking-wide" style="color:var(--text-soft)">Bulan Ini</p>
                    <p class="text-2xl font-bold mt-1 text-blue-600">{{ $stats['rentals_this_month'] ?? 0 }}</p>
                </div>
                <div class="stat-card col-span-2 sm:col-span-1">
                    <p class="text-xs font-medium uppercase tracking-wide" style="color:var(--text-soft)">Total Pendapatan</p>
                    <p class="text-lg font-bold mt-1" style="color:var(--primary)">
                        Rp {{ number_format($stats['total_revenue'] ?? 0, 0, ',', '.') }}
                    </p>
                </div>
            </div>
            @endif

            {{-- Riwayat Transaksi (opsional, kirim dari controller jika tersedia) --}}
            @if(isset($rentals) && $rentals->isNotEmpty())
            <div class="card overflow-hidden">
                <div class="p-5 border-b flex items-center gap-2" style="border-color:var(--border)">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:var(--secondary)">
                        <i data-lucide="history" class="w-3.5 h-3.5" style="color:var(--primary)"></i>
                    </div>
                    <h3 class="font-semibold text-sm" style="color:var(--text-dark)">Riwayat Transaksi</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="elegant-table w-full">
                        <thead>
                            <tr>
                                <th class="text-left">Invoice</th>
                                <th class="text-left">Pelanggan</th>
                                <th class="text-center">Tanggal</th>
                                <th class="text-right">Total</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rentals as $rental)
                            <tr>
                                <td class="text-xs font-mono" style="color:var(--text-soft)">{{ $rental->invoice_number }}</td>
                                <td class="text-sm" style="color:var(--text-dark)">{{ $rental->customer->name ?? '-' }}</td>
                                <td class="text-center text-xs" style="color:var(--text-soft)">
                                    {{ \Carbon\Carbon::parse($rental->created_at)->format('d M Y') }}
                                </td>
                                <td class="text-right text-sm font-semibold" style="color:var(--text-dark)">
                                    Rp {{ number_format($rental->total_price, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <span class="badge text-[10px]
                                        {{ match($rental->status) {
                                            'active'    => 'badge-blue',
                                            'returned'  => 'badge-green',
                                            'overdue'   => 'badge-red',
                                            'cancelled' => 'badge-gray',
                                            default     => 'badge-gray'
                                        } }}">
                                        {{ ucfirst($rental->status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($rentals->hasPages())
                <div class="px-6 py-4 border-t" style="border-color:var(--border)">
                    {{ $rentals->links('components.pagination') }}
                </div>
                @endif
            </div>
            @endif

        </div>

        {{-- ── KOLOM KANAN (1/3) ────────────────────────────── --}}
        <div class="space-y-6">

            {{-- Detail Info --}}
            <div class="card p-6">
                <div class="flex items-center gap-2 pb-3 mb-4 border-b" style="border-color:var(--border)">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:var(--secondary)">
                        <i data-lucide="list" class="w-3.5 h-3.5" style="color:var(--primary)"></i>
                    </div>
                    <h3 class="font-semibold text-sm" style="color:var(--text-dark)">Detail Akun</h3>
                </div>

                <div class="space-y-4">
                    @php
                        $details = [
                            ['icon' => 'user',      'label' => 'Nama',    'value' => $user->name],
                            ['icon' => 'mail',      'label' => 'Email',   'value' => $user->email],
                            ['icon' => 'phone',     'label' => 'Telepon', 'value' => $user->phone ?? '—'],
                            ['icon' => 'shield',    'label' => 'Role',    'value' => ucfirst(str_replace('_', ' ', $user->role ?? '—'))],
                            ['icon' => 'map-pin',   'label' => 'Cabang',  'value' => $user->branch?->name ?? ($user->role === 'super_admin' ? 'Semua Cabang' : 'Belum dikaitkan')],
                        ];
                    @endphp
                    @foreach($details as $d)
                    <div class="flex items-start gap-3">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5" style="background:var(--secondary)">
                            <i data-lucide="{{ $d['icon'] }}" class="w-3.5 h-3.5" style="color:var(--primary)"></i>
                        </div>
                        <div>
                            <p class="text-xs" style="color:var(--text-soft)">{{ $d['label'] }}</p>
                            <p class="text-sm font-medium" style="color:var(--text-dark)">{{ $d['value'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Timestamps --}}
            <div class="card p-5 space-y-3">
                <div class="flex items-start gap-3">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0" style="background:var(--secondary)">
                        <i data-lucide="calendar-plus" class="w-3.5 h-3.5" style="color:var(--primary)"></i>
                    </div>
                    <div>
                        <p class="text-xs" style="color:var(--text-soft)">Terdaftar</p>
                        <p class="text-sm font-medium" style="color:var(--text-dark)">{{ $user->created_at->format('d M Y, H:i') }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0" style="background:var(--secondary)">
                        <i data-lucide="clock" class="w-3.5 h-3.5" style="color:var(--primary)"></i>
                    </div>
                    <div>
                        <p class="text-xs" style="color:var(--text-soft)">Terakhir diperbarui</p>
                        <p class="text-sm font-medium" style="color:var(--text-dark)">{{ $user->updated_at->diffForHumans() }}</p>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="card p-5 space-y-3">
                <h3 class="text-xs font-semibold uppercase tracking-wide" style="color:var(--text-soft)">Aksi Cepat</h3>

                <a href="{{ route('users.edit', $user) }}" class="btn-primary w-full justify-center">
                    <i data-lucide="edit-2" class="w-4 h-4"></i> Edit Pengguna
                </a>

                @if($user->id !== auth()->id())
                <form method="POST" action="{{ route('users.toggle', $user) }}">
                    @csrf @method('PATCH')
                    <button type="submit"
                            class="btn-secondary w-full justify-center {{ $user->is_active ? 'text-orange-500 hover:bg-orange-50' : 'text-green-600 hover:bg-green-50' }}">
                        <i data-lucide="{{ $user->is_active ? 'user-x' : 'user-check' }}" class="w-4 h-4"></i>
                        {{ $user->is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}
                    </button>
                </form>

                <form method="POST" action="{{ route('users.destroy', $user) }}"
                      onsubmit="return confirm('Hapus pengguna ini? Tindakan tidak bisa dibatalkan.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-secondary w-full justify-center text-red-500 hover:bg-red-50">
                        <i data-lucide="trash-2" class="w-4 h-4"></i> Hapus Pengguna
                    </button>
                </form>
                @else
                <p class="text-xs text-center" style="color:var(--text-soft)">
                    Anda tidak dapat menonaktifkan atau menghapus akun sendiri.
                </p>
                @endif
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>lucide.createIcons();</script>
@endpush
@endsection