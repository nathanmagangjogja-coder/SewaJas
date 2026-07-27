@extends('Layouts.app')

@section('title', 'Kategori')

@section('content')
<div class="space-y-6">

    {{-- ── HEADER ──────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-playfair text-2xl font-bold" style="color: var(--text-dark)">Kategori</h1>
            <p class="text-sm mt-0.5" style="color: var(--text-soft)">Kelola kategori produk jas</p>
        </div>
        <a href="{{ route('categories.create') }}" class="btn-primary">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Tambah Kategori
        </a>
    </div>

    {{-- ── TABEL ────────────────────────────────────────────── --}}
    <div class="card overflow-hidden">
        @if($categories->isEmpty())
        <div class="flex flex-col items-center justify-center py-20 text-center">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4"
                 style="background: var(--secondary)">
                <i data-lucide="tag" class="w-8 h-8" style="color:var(--primary)"></i>
            </div>
            <p class="font-semibold text-lg" style="color:var(--text-dark)">Belum ada kategori</p>
            <p class="text-sm mt-1 mb-4" style="color:var(--text-soft)">Tambahkan kategori produk pertama</p>
            <a href="{{ route('categories.create') }}" class="btn-primary">
                <i data-lucide="plus" class="w-4 h-4"></i> Tambah Kategori
            </a>
        </div>
        @else
        <table class="elegant-table w-full">
            <thead>
                <tr>
                    <th class="text-left">Kategori</th>
                    <th class="text-left">Slug</th>
                    <th class="text-center">Icon</th>
                    <th class="text-center">Urutan</th>
                    <th class="text-center">Jumlah Produk</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $category)
                <tr>
                    <td>
                        <p class="font-semibold text-sm" style="color:var(--text-dark)">{{ $category->name }}</p>
                    </td>
                    <td>
                        <span class="text-xs font-mono px-2 py-1 rounded-lg" style="background:var(--secondary); color:var(--text-soft)">
                            {{ $category->slug }}
                        </span>
                    </td>
                    <td class="text-center">
                        @if($category->icon)
                        <div class="flex justify-center">
                            <i data-lucide="{{ $category->icon }}" class="w-4 h-4" style="color:var(--primary)"></i>
                        </div>
                        @else
                        <span class="text-xs" style="color:var(--text-soft)">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="text-sm" style="color:var(--text-soft)">{{ $category->sort_order }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-gold">{{ $category->products_count }} produk</span>
                    </td>
                    <td class="text-center">
                        @if($category->is_active)
                            <span class="badge badge-green">Aktif</span>
                        @else
                            <span class="badge badge-gray">Nonaktif</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-1">
                            <a href="{{ route('categories.edit', $category) }}"
                               class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors" title="Edit">
                                <i data-lucide="pencil" class="w-4 h-4" style="color:var(--primary)"></i>
                            </a>
                            <form method="POST" action="{{ route('categories.destroy', $category) }}"
                                  onsubmit="return confirm('Hapus kategori {{ $category->name }}?')" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="p-1.5 rounded-lg hover:bg-red-50 transition-colors" title="Hapus">
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
