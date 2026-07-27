@extends('layouts.app')

@section('title', 'Arsip Customer')
@section('page-title', 'Arsip Customer')
@section('subtitle', 'Customer yang di-blacklist atau dihapus sementara')

@section('content')
<div class="space-y-5" x-data="{ activeTab: '{{ request('tab', 'blacklist') }}' }">

    {{-- Back + Search --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('customers.index') }}" class="btn-secondary">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Kembali ke Data Customer
        </a>
        <form method="GET" action="{{ route('customers.archive') }}" class="flex-1 max-w-xs">
            <input type="hidden" name="tab" x-model="activeTab">
            <div class="relative">
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" style="color: var(--text-soft)"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari nama atau nomor HP..."
                       class="form-input pl-9 w-full">
            </div>
        </form>
    </div>

    {{-- Tab Menu --}}
    <div class="card p-2 inline-flex gap-1">
        <button @click="activeTab = 'blacklist'"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors"
                :class="activeTab === 'blacklist' ? '' : 'hover:bg-gray-50'"
                :style="activeTab === 'blacklist' ? 'background: #FFF1F0; color: #C0392B' : 'color: var(--text-soft)'">
            <i data-lucide="shield-off" class="w-4 h-4"></i>
            Diblacklist
            <span class="text-xs font-bold px-1.5 py-0.5 rounded-md" style="background: rgba(0,0,0,0.06)">{{ $blacklisted->total() }}</span>
        </button>
        <button @click="activeTab = 'trashed'"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors"
                :class="activeTab === 'trashed' ? '' : 'hover:bg-gray-50'"
                :style="activeTab === 'trashed' ? 'background: var(--secondary); color: var(--primary)' : 'color: var(--text-soft)'">
            <i data-lucide="trash-2" class="w-4 h-4"></i>
            Terhapus Sementara
            <span class="text-xs font-bold px-1.5 py-0.5 rounded-md" style="background: rgba(0,0,0,0.06)">{{ $trashed->total() }}</span>
        </button>
    </div>

    {{-- ============================================================
         TAB: DIBLACKLIST
         ============================================================ --}}
    <div x-show="activeTab === 'blacklist'" x-cloak class="card overflow-hidden">
        <div class="p-5 border-b" style="border-color: var(--border)">
            <h3 class="font-playfair font-semibold text-base" style="color: var(--text-dark)">Customer Diblacklist</h3>
            <p class="text-xs mt-0.5" style="color: var(--text-soft)">Customer ini tidak bisa membuat transaksi baru sampai blacklist dicabut.</p>
        </div>

        @forelse($blacklisted as $customer)
        <div class="flex items-center gap-3 p-4 border-b" style="border-color: var(--border)">
            <img src="{{ $customer->photo_url }}" alt="{{ $customer->name }}"
                 class="w-10 h-10 rounded-xl object-cover ring-2 ring-red-100 flex-shrink-0">
            <div class="flex-1 min-w-0">
                <a href="{{ route('customers.show', $customer) }}" class="font-semibold text-sm hover:underline" style="color: var(--text-dark)">{{ $customer->name }}</a>
                <p class="text-xs mt-0.5" style="color: var(--text-soft)">{{ $customer->phone }}</p>
                @if($customer->blacklist_reason)
                <p class="text-xs mt-1 italic" style="color: #C0392B">"{{ Str::limit($customer->blacklist_reason, 80) }}"</p>
                @endif
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <a href="{{ route('customers.show', $customer) }}" class="btn-secondary text-xs px-3 py-1.5">
                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                    Detail
                </a>
                @can('update', $customer)
                <form method="POST" action="{{ route('customers.blacklist', $customer) }}"
                      onsubmit="return confirm('Cabut blacklist untuk {{ $customer->name }}?');">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn-secondary text-xs px-3 py-1.5 border-green-300 text-green-600">
                        <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
                        Cabut Blacklist
                    </button>
                </form>
                @endcan
            </div>
        </div>
        @empty
        <div class="py-16 text-center">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-3" style="background: #F0FDF4">
                <i data-lucide="shield-check" class="w-8 h-8" style="color: #15803D"></i>
            </div>
            <p class="font-medium" style="color: var(--text-dark)">Tidak ada customer yang diblacklist</p>
        </div>
        @endforelse

        @if($blacklisted->hasPages())
        <div class="px-6 py-4 border-t" style="border-color: var(--border)">
            {{ $blacklisted->appends(['tab' => 'blacklist', 'search' => request('search')])->links('components.pagination') }}
        </div>
        @endif
    </div>

    {{-- ============================================================
         TAB: TERHAPUS SEMENTARA
         ============================================================ --}}
    <div x-show="activeTab === 'trashed'" x-cloak class="card overflow-hidden">
        <div class="p-5 border-b" style="border-color: var(--border)">
            <h3 class="font-playfair font-semibold text-base" style="color: var(--text-dark)">Customer Terhapus Sementara</h3>
            <p class="text-xs mt-0.5" style="color: var(--text-soft)">Data masih tersimpan dan bisa dipulihkan kapan saja, belum dihapus permanen.</p>
        </div>

        @forelse($trashed as $customer)
        <div class="flex items-center gap-3 p-4 border-b" style="border-color: var(--border)">
            <img src="{{ $customer->photo_url }}" alt="{{ $customer->name }}"
                 class="w-10 h-10 rounded-xl object-cover ring-2 ring-gray-200 flex-shrink-0 grayscale">
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-sm" style="color: var(--text-dark)">{{ $customer->name }}</p>
                <p class="text-xs mt-0.5" style="color: var(--text-soft)">{{ $customer->phone }}</p>
                <p class="text-[11px] mt-1" style="color: var(--text-soft)">
                    <i data-lucide="clock" class="w-3 h-3 inline mr-1"></i>
                    Dihapus {{ $customer->deleted_at->diffForHumans() }} ({{ $customer->deleted_at->format('d M Y, H:i') }})
                </p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <form method="POST" action="{{ route('customers.restore', $customer->id) }}"
                      onsubmit="return confirm('Pulihkan customer {{ $customer->name }}?');">
                    @csrf
                    <button type="submit" class="btn-secondary text-xs px-3 py-1.5 border-green-300 text-green-600">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        Pulihkan
                    </button>
                </form>
                @if(auth()->user()->isSuperAdmin())
                <button type="button" class="text-xs px-3 py-1.5 rounded-lg font-medium bg-red-600 text-white"
                        onclick="handleForceDeletePrompt({{ $customer->id }});">
                    <i data-lucide="trash" class="w-3.5 h-3.5"></i>
                    Hapus Permanen
                </button>
                <form id="force-delete-form-{{ $customer->id }}" method="POST" action="{{ route('customers.destroy.force', $customer->id) }}" style="display:none">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="reason" id="force-delete-reason-{{ $customer->id }}" value="">
                </form>
                @endif
            </div>
        </div>
        @empty
        <div class="py-16 text-center">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-3" style="background: var(--secondary)">
                <i data-lucide="trash-2" class="w-8 h-8" style="color: var(--primary)"></i>
            </div>
            <p class="font-medium" style="color: var(--text-dark)">Tidak ada customer yang terhapus sementara</p>
        </div>
        @endforelse

        @if($trashed->hasPages())
        <div class="px-6 py-4 border-t" style="border-color: var(--border)">
            {{ $trashed->appends(['tab' => 'trashed', 'search' => request('search')])->links('components.pagination') }}
        </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
function handleForceDeletePrompt(id) {
    var reason = prompt('Masukkan alasan untuk Hapus Permanen (minimal 10 karakter):');
    if (reason === null) return;
    reason = (reason || '').trim();
    if (reason.length < 10) {
        alert('Alasan terlalu pendek. Mohon isi minimal 10 karakter.');
        return;
    }
    if (!confirm('PASTIKAN: Hapus PERMANEN customer ini beserta file. Aksi TIDAK BISA DIBATALKAN. Lanjutkan?')) return;

    var form = document.getElementById('force-delete-form-' + id);
    if (!form) { alert('Form penghapusan permanen tidak ditemukan.'); return; }
    var input = form.querySelector('input[name="reason"]');
    if (input) input.value = reason;
    form.submit();
}
</script>
@endpush
