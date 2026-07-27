@extends('Layouts.app')

@section('title', 'Kelola Cabang')

@section('content')
<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-playfair text-2xl font-bold" style="color: var(--text-dark)">Kelola Cabang</h1>
            <p class="text-sm mt-0.5" style="color: var(--text-soft)">Manajemen cabang toko JasRental</p>
        </div>
        <a href="{{ route('branches.create') }}" class="btn-primary">
            <i data-lucide="plus" class="w-4 h-4"></i> Tambah Cabang
        </a>
    </div>

    {{-- STATS --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="stat-card">
            <p class="text-xs font-medium uppercase tracking-wide" style="color:var(--text-soft)">Total Cabang</p>
            <p class="text-2xl font-bold mt-1" style="color:var(--text-dark)">{{ $branches->count() }}</p>
        </div>
        <div class="stat-card">
            <p class="text-xs font-medium uppercase tracking-wide" style="color:var(--text-soft)">Cabang Aktif</p>
            <p class="text-2xl font-bold mt-1 text-green-600">{{ $branches->where('is_active', true)->count() }}</p>
        </div>
        <div class="stat-card">
            <p class="text-xs font-medium uppercase tracking-wide" style="color:var(--text-soft)">Cabang Nonaktif</p>
            <p class="text-2xl font-bold mt-1 text-red-500">{{ $branches->where('is_active', false)->count() }}</p>
        </div>
    </div>

    {{-- TABEL --}}
    <div class="card overflow-hidden">
        @if($branches->isEmpty())
        <div class="flex flex-col items-center justify-center py-20 text-center">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4" style="background: var(--secondary)">
                <i data-lucide="store" class="w-8 h-8" style="color:var(--primary)"></i>
            </div>
            <p class="font-semibold text-lg" style="color:var(--text-dark)">Belum ada cabang</p>
            <p class="text-sm mt-1 mb-4" style="color:var(--text-soft)">Tambahkan cabang pertama</p>
            <a href="{{ route('branches.create') }}" class="btn-primary">
                <i data-lucide="plus" class="w-4 h-4"></i> Tambah Cabang
            </a>
        </div>
        @else
        <table class="elegant-table w-full">
            <thead>
                <tr>
                    <th class="text-left">Cabang</th>
                    <th class="text-left">Kode</th>
                    <th class="text-left">Kota</th>
                    <th class="text-left">Kontak</th>
                    <th class="text-center">Pengguna</th>
                    <th class="text-center">Produk</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($branches as $branch)
                <tr>
                    <td>
                        <p class="font-semibold text-sm" style="color:var(--text-dark)">{{ $branch->name }}</p>
                        @if($branch->address)
                        <p class="text-xs mt-0.5 line-clamp-1" style="color:var(--text-soft)">{{ $branch->address }}</p>
                        @endif
                    </td>
                    <td>
                        <span class="text-xs font-mono px-2 py-1 rounded-lg font-bold" style="background:var(--secondary); color:var(--primary)">
                            {{ $branch->code }}
                        </span>
                    </td>
                    <td>
                        <p class="text-sm" style="color:var(--text-dark)">{{ $branch->city ?? '-' }}</p>
                        <p class="text-xs" style="color:var(--text-soft)">{{ $branch->province ?? '' }}</p>
                    </td>
                    <td>
                        @if($branch->phone)
                        <p class="text-sm" style="color:var(--text-dark)">{{ $branch->phone }}</p>
                        @endif
                        @if($branch->email)
                        <p class="text-xs" style="color:var(--text-soft)">{{ $branch->email }}</p>
                        @endif
                        @if(!$branch->phone && !$branch->email)
                        <span class="text-xs" style="color:var(--text-soft)">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge badge-blue">{{ $branch->users_count }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-gold">{{ $branch->products_count }}</span>
                    </td>
                    <td class="text-center">
                        @if($branch->is_active)
                            <span class="badge badge-green">Aktif</span>
                        @else
                            <span class="badge badge-gray">Nonaktif</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-1">
                            <a href="{{ route('branches.show', $branch) }}"
                               class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors" title="Detail">
                                <i data-lucide="eye" class="w-4 h-4" style="color:var(--text-soft)"></i>
                            </a>
                            <a href="{{ route('branches.edit', $branch) }}"
                               class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors" title="Edit">
                                <i data-lucide="pencil" class="w-4 h-4" style="color:var(--primary)"></i>
                            </a>
                            <form method="POST" action="{{ route('branches.destroy', $branch) }}"
                                  onsubmit="return confirm('Hapus cabang {{ $branch->name }}?')" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-1.5 rounded-lg hover:bg-red-50 transition-colors" title="Hapus">
                                    <i data-lucide="trash-2" class="w-4 h-4 text-red-400"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

@push('scripts')
<script>lucide.createIcons();</script>
@endpush
@endsection
