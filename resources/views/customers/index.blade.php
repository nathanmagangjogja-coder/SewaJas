@extends('layouts.app')

@section('title', 'Data Customer')
@section('page-title', 'Data Customer')
@section('subtitle', 'Kelola semua data pelanggan')

@section('content')
<div class="space-y-5">

    {{-- Header Actions --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('customers.create') }}" class="btn-primary">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                <span class="hidden sm:inline">Tambah Customer</span>
                <span class="sm:hidden">Tambah</span>
            </a>
            <a href="{{ route('customers.export') }}" class="btn-secondary">
                <i data-lucide="download" class="w-4 h-4"></i>
                <span class="hidden sm:inline">Export Excel</span>
                <span class="sm:hidden">Export</span>
            </a>
            <a href="{{ route('customers.archive') }}" class="btn-secondary">
                <i data-lucide="archive" class="w-4 h-4"></i>
                <span class="hidden sm:inline">Blacklist &amp; Terhapus</span>
                <span class="sm:hidden">Arsip</span>
            </a>
        </div>
        <span class="text-sm px-3 py-1.5 rounded-lg font-medium"
              style="background: var(--secondary); color: var(--primary)">
            {{ $customers->total() }} Customer
        </span>
    </div>

    {{-- ============================================================
         RINGKASAN CEPAT (additional menu — quick stats bar)
         ============================================================ --}}
    <div class="grid grid-cols-3 gap-3">
        <div class="card p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background: var(--secondary)">
                <i data-lucide="users" class="w-5 h-5" style="color: var(--primary)"></i>
            </div>
            <div class="min-w-0">
                <p class="text-lg font-bold font-playfair leading-none" style="color: var(--text-dark)">{{ $customers->total() }}</p>
                <p class="text-xs mt-1" style="color: var(--text-soft)">Total Customer</p>
            </div>
        </div>
        <div class="card p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background: #FFF1F0">
                <i data-lucide="shield-off" class="w-5 h-5" style="color: #C0392B"></i>
            </div>
            <div class="min-w-0">
                <p class="text-lg font-bold font-playfair leading-none" style="color: var(--text-dark)">
                    {{ \App\Models\Customer::where('is_blacklisted', true)->count() }}
                </p>
                <p class="text-xs mt-1" style="color: var(--text-soft)">Blacklist</p>
            </div>
        </div>
        <div class="card p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background: #F0FDF4">
                <i data-lucide="user-plus" class="w-5 h-5" style="color: #15803D"></i>
            </div>
            <div class="min-w-0">
                <p class="text-lg font-bold font-playfair leading-none" style="color: var(--text-dark)">
                    {{ \App\Models\Customer::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count() }}
                </p>
                <p class="text-xs mt-1" style="color: var(--text-soft)">Baru Bulan Ini</p>
            </div>
        </div>
    </div>

    {{-- Search & Filter --}}
    <div class="card p-4">
        <form method="GET" action="{{ route('customers.index') }}">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" style="color: var(--text-soft)"></i>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Cari nama, nomor HP, nomor KTP..."
                               class="form-input pl-9 w-full">
                    </div>
                </div>
                <div class="sm:w-36">
                    <select name="blacklisted" class="form-input w-full">
                        <option value="">Semua Status</option>
                        <option value="0" {{ request('blacklisted') === '0' ? 'selected' : '' }}>Normal</option>
                        <option value="1" {{ request('blacklisted') === '1' ? 'selected' : '' }}>Blacklist</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary flex-1 sm:flex-none">
                        <i data-lucide="filter" class="w-4 h-4"></i>
                        Filter
                    </button>
                    @if(request()->hasAny(['search','blacklisted']))
                    <a href="{{ route('customers.index') }}" class="btn-secondary">
                        <i data-lucide="x" class="w-4 h-4"></i>
                        <span class="hidden sm:inline">Reset</span>
                    </a>
                    @endif
                </div>
            </div>

            @if(request()->hasAny(['search','blacklisted']))
            <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t" style="border-color: var(--border)">
                <span class="text-xs" style="color: var(--text-soft)">Filter aktif:</span>
                @if(request('search'))
                <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-md"
                      style="background: var(--secondary); color: var(--primary)">
                    <i data-lucide="search" class="w-3 h-3"></i>
                    "{{ request('search') }}"
                </span>
                @endif
                @if(request('blacklisted') !== null && request('blacklisted') !== '')
                <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-md"
                      style="background: var(--secondary); color: var(--primary)">
                    <i data-lucide="shield" class="w-3 h-3"></i>
                    {{ request('blacklisted') === '1' ? 'Blacklist' : 'Normal' }}
                </span>
                @endif
            </div>
            @endif
        </form>
    </div>

    {{-- ============================================================
         MOBILE VIEW: Card Layout (tampil di bawah sm)
         ============================================================ --}}
    <div class="block sm:hidden space-y-3">
        @forelse($customers as $customer)
        <div class="card p-4">
            <div class="flex items-start gap-3">
                <img src="{{ $customer->photo_url }}" alt="{{ $customer->name }}"
                     class="w-12 h-12 rounded-xl object-cover ring-2 ring-amber-100 flex-shrink-0">

                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="font-semibold truncate" style="color: var(--text-dark)">{{ $customer->name }}</p>
                            <p class="text-xs mt-0.5" style="color: var(--text-soft)">{{ $customer->phone }}</p>
                        </div>
                        @if($customer->is_blacklisted)
                        <span class="badge badge-red flex-shrink-0">Blacklist</span>
                        @else
                        <span class="badge badge-green flex-shrink-0">Normal</span>
                        @endif
                    </div>

                    @if($customer->address)
                    <p class="text-xs mt-1 truncate" style="color: var(--text-soft)">
                        <i data-lucide="map-pin" class="w-3 h-3 inline mr-1"></i>{{ $customer->address }}
                    </p>
                    @endif
                </div>
            </div>

            {{-- Bergabung & Transaksi --}}
            <div class="mt-3 pt-3 border-t" style="border-color: var(--border)">
                <div class="flex items-center justify-between gap-2 flex-wrap">
                    <span class="text-xs" style="color: var(--text-soft)">
                        <i data-lucide="calendar" class="w-3 h-3 inline mr-1"></i>
                        Bergabung {{ $customer->created_at->format('d M Y') }}
                    </span>
                    <span class="text-xs font-medium" style="color: var(--text-soft)">
                        <span class="font-bold text-sm" style="color: var(--text-dark)">{{ $customer->rentals_count ?? $customer->total_rentals }}</span>
                        transaksi
                    </span>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="mt-3 grid grid-cols-3 gap-2">
                <a href="{{ route('customers.show', $customer) }}"
                   class="flex items-center justify-center gap-1.5 py-2 rounded-lg text-xs font-medium transition-colors"
                   style="background: var(--secondary); color: var(--primary)">
                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                    Detail
                </a>
                <a href="{{ route('customers.edit', $customer) }}"
                   class="flex items-center justify-center gap-1.5 py-2 rounded-lg text-xs font-medium transition-colors"
                   style="background: var(--secondary); color: var(--text-dark)">
                    <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                    Edit
                </a>
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', str_starts_with($customer->phone, '0') ? '62'.substr($customer->phone,1) : $customer->phone) }}"
                   target="_blank"
                   class="flex items-center justify-center gap-1.5 py-2 rounded-lg text-xs font-medium transition-colors"
                   style="background: #e8fdf0; color: #25D366">
                    <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                    WA
                </a>
                @if(auth()->user()->isSuperAdmin() || (auth()->user()->isAdminToko() && $customer->branch_id === auth()->user()->branch_id))
                <form method="POST" action="{{ route('customers.destroy', $customer) }}"
                      onsubmit="return confirm('Yakin ingin menghapus customer ini? Aksi tidak dapat dibatalkan.');"
                      class="flex items-center justify-center">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="py-2 px-3 rounded-lg text-xs font-medium transition-colors bg-red-50 text-red-600">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        Hapus
                    </button>
                </form>
                @endif
                @if(auth()->user()->isSuperAdmin())
                <div class="flex items-center justify-center">
                    <button type="button" class="py-2 px-3 rounded-lg text-xs font-medium transition-colors bg-red-600 text-white"
                        onclick="handleForceDeletePrompt({{ $customer->id }});">
                        <i data-lucide="trash" class="w-3.5 h-3.5"></i>
                        Hapus Permanen
                    </button>
                    <form id="force-delete-form-{{ $customer->id }}" method="POST" action="{{ route('customers.destroy.force', $customer) }}" style="display:none">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="reason" id="force-delete-reason-{{ $customer->id }}" value="">
                    </form>
                </div>
                @endif
            </div>
        </div>
        @empty
        <div class="card p-12 text-center">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-3" style="background: var(--secondary)">
                <i data-lucide="users" class="w-8 h-8" style="color: var(--primary)"></i>
            </div>
            <p class="font-medium mb-3" style="color: var(--text-dark)">Belum ada data customer</p>
            <a href="{{ route('customers.create') }}" class="btn-primary">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                Tambah Customer
            </a>
        </div>
        @endforelse
    </div>

    {{-- ============================================================
         DESKTOP VIEW: Table Layout (tampil di sm ke atas)
         ============================================================ --}}
    <div class="hidden sm:block card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full elegant-table">
                <thead>
                    <tr>
                        <th class="text-left w-10">#</th>
                        <th class="text-left">Customer</th>
                        <th class="text-left">Kontak</th>
                        <th class="text-left hidden lg:table-cell">Bergabung</th>
                        <th class="text-center w-24">Sewa</th>
                        <th class="text-center w-24">Status</th>
                        <th class="text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                    <tr class="group">
                        <td class="text-xs" style="color: var(--text-soft)">
                            {{ $customers->firstItem() + $loop->index }}
                        </td>
                        <td>
                            <div class="flex items-center gap-3">
                                <img src="{{ $customer->photo_url }}" alt="{{ $customer->name }}"
                                     class="w-9 h-9 rounded-xl object-cover ring-2 ring-amber-100 flex-shrink-0">
                                <div class="min-w-0">
                                    <p class="font-semibold text-sm truncate" style="color: var(--text-dark)">{{ $customer->name }}</p>
                                    @if($customer->id_number)
                                    <p class="text-[11px] font-mono truncate" style="color: var(--text-soft)">{{ $customer->id_number }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <p class="text-sm font-medium" style="color: var(--text-dark)">{{ $customer->phone }}</p>
                            @if($customer->address)
                            <p class="text-xs mt-0.5 truncate max-w-[160px]" style="color: var(--text-soft)">{{ $customer->address }}</p>
                            @endif
                        </td>
                        <td class="hidden lg:table-cell">
                            <span class="text-xs" style="color: var(--text-soft)">{{ $customer->created_at->format('d M Y') }}</span>
                        </td>
                        <td class="text-center">
                            <span class="font-bold text-sm" style="color: var(--text-dark)">
                                {{ $customer->rentals_count ?? $customer->total_rentals }}
                            </span>
                            <p class="text-[11px]" style="color: var(--text-soft)">transaksi</p>
                        </td>
                        <td class="text-center">
                            @if($customer->is_blacklisted)
                            <span class="badge badge-red">Blacklist</span>
                            @else
                            <span class="badge badge-green">Normal</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('customers.show', $customer) }}"
                                   class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors" title="Detail"
                                   style="color: var(--text-soft)">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                </a>
                                <a href="{{ route('customers.edit', $customer) }}"
                                   class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors" title="Edit"
                                   style="color: var(--text-soft)">
                                    <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                                </a>
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', str_starts_with($customer->phone, '0') ? '62'.substr($customer->phone,1) : $customer->phone) }}"
                                   target="_blank"
                                   class="p-1.5 rounded-lg hover:bg-green-50 transition-colors" title="WhatsApp"
                                   style="color: #25D366">
                                    <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                                </a>
                                @if(auth()->user()->isSuperAdmin() || (auth()->user()->isAdminToko() && $customer->branch_id === auth()->user()->branch_id))
                                <form method="POST" action="{{ route('customers.destroy', $customer) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg hover:bg-red-50 transition-colors" title="Hapus"
                                            onclick="return confirm('Yakin ingin menghapus customer #{{ $customer->id }}?');"
                                            style="color: #DC2626">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                </form>
                                @endif
                                @if(auth()->user()->isSuperAdmin())
                                <button type="button" class="p-1.5 rounded-lg hover:bg-red-50 transition-colors" title="Hapus Permanen"
                                        onclick="handleForceDeletePrompt({{ $customer->id }});" style="color: #9B1C1C">
                                    <i data-lucide="trash" class="w-3.5 h-3.5"></i>
                                </button>
                                <form id="force-delete-form-{{ $customer->id }}-desk" method="POST" action="{{ route('customers.destroy.force', $customer) }}" style="display:none">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="reason" id="force-delete-reason-{{ $customer->id }}-desk" value="">
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-16 h-16 rounded-2xl flex items-center justify-center" style="background: var(--secondary)">
                                    <i data-lucide="users" class="w-8 h-8" style="color: var(--primary)"></i>
                                </div>
                                <p class="font-medium" style="color: var(--text-dark)">Belum ada data customer</p>
                                <a href="{{ route('customers.create') }}" class="btn-primary">
                                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                                    Tambah Customer
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
        <div class="px-6 py-4 border-t" style="border-color: var(--border)">
            {{ $customers->links('components.pagination') }}
        </div>
        @endif
    </div>

    @if($customers->hasPages())
    <div class="block sm:hidden">
        {{ $customers->links('components.pagination') }}
    </div>
    @endif

</div>
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

    var ids = [
        'force-delete-form-' + id,
        'force-delete-form-' + id + '-desk',
        'force-delete-form-' + id + '-show'
    ];
    var filled = false;
    ids.forEach(function(fid) {
        var form = document.getElementById(fid);
        if (!form) return;
        var input = form.querySelector('input[name="reason"]');
        if (input) input.value = reason;
        form.submit();
        filled = true;
    });
    if (!filled) {
        alert('Form penghapusan permanen tidak ditemukan. Segera hubungi admin.');
    }
}
</script>
@endpush
@endsection