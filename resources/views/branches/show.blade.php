@extends('Layouts.app')

@section('title', $branch->name)

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('branches.index') }}" class="btn-secondary p-2">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div>
                <h1 class="font-playfair text-2xl font-bold" style="color: var(--text-dark)">{{ $branch->name }}</h1>
                <p class="text-sm mt-0.5" style="color: var(--text-soft)">Detail cabang</p>
            </div>
        </div>
        <a href="{{ route('branches.edit', $branch) }}" class="btn-primary">
            <i data-lucide="pencil" class="w-4 h-4"></i> Edit
        </a>
    </div>

    <div class="card p-6 space-y-4">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide mb-1" style="color:var(--text-soft)">Nama Cabang</p>
                <p class="font-semibold" style="color:var(--text-dark)">{{ $branch->name }}</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide mb-1" style="color:var(--text-soft)">Kode</p>
                <span class="badge badge-gold text-sm">{{ $branch->code }}</span>
            </div>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide mb-1" style="color:var(--text-soft)">Telepon</p>
                <p style="color:var(--text-dark)">{{ $branch->phone ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide mb-1" style="color:var(--text-soft)">Email</p>
                <p style="color:var(--text-dark)">{{ $branch->email ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide mb-1" style="color:var(--text-soft)">Kota</p>
                <p style="color:var(--text-dark)">{{ $branch->city ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide mb-1" style="color:var(--text-soft)">Provinsi</p>
                <p style="color:var(--text-dark)">{{ $branch->province ?? '-' }}</p>
            </div>
            <div class="col-span-2">
                <p class="text-xs font-medium uppercase tracking-wide mb-1" style="color:var(--text-soft)">Alamat</p>
                <p style="color:var(--text-dark)">{{ $branch->address ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide mb-1" style="color:var(--text-soft)">Status</p>
                @if($branch->is_active)
                    <span class="badge badge-green">Aktif</span>
                @else
                    <span class="badge badge-gray">Nonaktif</span>
                @endif
            </div>
        </div>

        <div class="border-t pt-4 grid grid-cols-3 gap-4" style="border-color:var(--border)">
            <div class="text-center">
                <p class="text-2xl font-bold" style="color:var(--primary)">{{ $branch->users_count }}</p>
                <p class="text-xs mt-1" style="color:var(--text-soft)">Pengguna</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold" style="color:var(--primary)">{{ $branch->products_count }}</p>
                <p class="text-xs mt-1" style="color:var(--text-soft)">Produk</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold" style="color:var(--primary)">{{ $branch->rentals_count }}</p>
                <p class="text-xs mt-1" style="color:var(--text-soft)">Total Rental</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>lucide.createIcons();</script>
@endpush
@endsection
