@extends('layouts.app')

@section('title', $customer->name)
@section('page-title', 'Detail Customer')
@section('subtitle', $customer->name)

@section('content')
<div class="space-y-5" x-data="{ showBlacklist: false, activeTab: 'ringkasan' }">

    {{-- Actions --}}
    <div class="flex items-center gap-2">
        <a href="{{ route('customers.index') }}" class="btn-secondary">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span class="hidden sm:inline">Kembali</span>
        </a>
        <a href="{{ route('rentals.create', ['customer_id' => $customer->id]) }}" class="btn-primary flex-1 sm:flex-none justify-center">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Buat Penyewaan Baru
        </a>
    </div>

    <div class="flex items-center gap-2 overflow-x-auto -mx-4 px-4 pb-1" style="scrollbar-width: none;">
        <a href="{{ route('customers.edit', $customer) }}" class="btn-secondary text-sm flex-shrink-0">
            <i data-lucide="edit-2" class="w-4 h-4"></i>
            Edit
        </a>
        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', str_starts_with($customer->phone, '0') ? '62'.substr($customer->phone,1) : $customer->phone) }}"
           target="_blank" class="btn-secondary text-sm flex-shrink-0" style="color: #25D366; border-color: #25D366">
            <i data-lucide="message-circle" class="w-4 h-4"></i>
            WhatsApp
        </a>
        @can('update', $customer)
        <button @click="showBlacklist = true"
                class="btn-secondary text-sm flex-shrink-0 {{ $customer->is_blacklisted ? 'border-green-400 text-green-600' : 'border-red-300 text-red-500' }}">
            <i data-lucide="{{ $customer->is_blacklisted ? 'user-check' : 'user-x' }}" class="w-4 h-4"></i>
            {{ $customer->is_blacklisted ? 'Hapus Blacklist' : 'Blacklist' }}
        </button>
        @endcan
        @if(auth()->user()->isSuperAdmin() || (auth()->user()->isAdminToko() && $customer->branch_id === auth()->user()->branch_id))
        <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Yakin ingin menghapus customer ini?');" class="flex-shrink-0">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-secondary text-sm" style="border-color: rgba(220,38,38,0.12); color: #C0392B">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                Hapus
            </button>
        </form>
        @endif
        @if(auth()->user()->isSuperAdmin())
        <div class="flex-shrink-0">
            <button type="button" class="btn-secondary text-sm" style="background:#FFF5F5; color:#9B1C1C; border-color: rgba(220,38,38,0.08)"
                    onclick="handleForceDeletePrompt({{ $customer->id }});">
                <i data-lucide="trash" class="w-4 h-4"></i>
                Hapus Permanen
            </button>
            <form id="force-delete-form-{{ $customer->id }}-show" method="POST" action="{{ route('customers.destroy.force', $customer) }}" style="display:none">
                @csrf
                @method('DELETE')
                <input type="hidden" name="reason" id="force-delete-reason-{{ $customer->id }}-show" value="">
            </form>
        </div>
        @endif
    </div>

    {{-- Blacklist Alert --}}
    @if($customer->is_blacklisted)
    <div class="flex items-center gap-3 p-4 rounded-xl" style="background: #FFF1F0; border: 1px solid #FECACA">
        <i data-lucide="shield-off" class="w-5 h-5 flex-shrink-0" style="color: #C0392B"></i>
        <div>
            <p class="font-semibold" style="color: #C0392B">Customer ini di-Blacklist</p>
            @if($customer->blacklist_reason)<p class="text-sm" style="color: #E74C3C">Alasan: {{ $customer->blacklist_reason }}</p>@endif
        </div>
    </div>
    @endif

    <div class="grid lg:grid-cols-3 gap-5">

        {{-- ============================================================
             PROFILE CARD (sidebar kiri, tetap ringkas — TANPA ukuran badan)
             ============================================================ --}}
        <div class="lg:col-span-1 space-y-5">
            <div class="card p-6 text-center">
                <div class="relative inline-block mb-4">
                    <img src="{{ $customer->photo_url }}"
                        alt="{{ $customer->name }}"
                        class="w-28 h-28 rounded-2xl object-cover mx-auto ring-4 ring-amber-100">
                    @if($customer->is_blacklisted)
                    <div class="absolute -top-2 -right-2 w-7 h-7 rounded-full flex items-center justify-center" style="background: #C0392B">
                        <i data-lucide="ban" class="w-3.5 h-3.5 text-white"></i>
                    </div>
                    @endif
                </div>
                <h2 class="font-playfair font-bold text-xl" style="color: var(--text-dark)">{{ $customer->name }}</h2>
                <p class="text-sm mt-1" style="color: var(--text-soft)">{{ $customer->phone }}</p>
                                <p class="text-[11px] mt-3" style="color: var(--text-soft)">
                    <i data-lucide="calendar" class="w-3 h-3 inline mr-1"></i>
                    Bergabung sejak {{ $customer->created_at->format('d M Y') }}
                </p>

                {{-- Stats --}}
                <div class="grid grid-cols-2 gap-3 mt-5">
                    <div class="p-3 rounded-xl" style="background: var(--bg-main)">
                        <p class="text-xl font-bold font-playfair" style="color: var(--primary)">{{ $customer->total_rentals }}</p>
                        <p class="text-xs mt-0.5" style="color: var(--text-soft)">Total Sewa</p>
                    </div>
                    <div class="p-3 rounded-xl" style="background: var(--bg-main)">
                        <p class="text-xl font-bold font-playfair" style="color: #10B981">
                            {{ $customer->rentals()->where('rental_status', 'returned')->count() }}
                        </p>
                        <p class="text-xs mt-0.5" style="color: var(--text-soft)">Selesai</p>
                    </div>
                </div>
            </div>

            {{-- Quick Menu (additional menu — tab navigation) --}}
            <div class="card p-2">
                <button @click="activeTab = 'ringkasan'"
                        class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors"
                        :class="activeTab === 'ringkasan' ? '' : 'hover:bg-gray-50'"
                        :style="activeTab === 'ringkasan' ? 'background: var(--secondary); color: var(--primary)' : 'color: var(--text-soft)'">
                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                    Ringkasan
                </button>
                <button @click="activeTab = 'riwayat'"
                        class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors"
                        :class="activeTab === 'riwayat' ? '' : 'hover:bg-gray-50'"
                        :style="activeTab === 'riwayat' ? 'background: var(--secondary); color: var(--primary)' : 'color: var(--text-soft)'">
                    <i data-lucide="history" class="w-4 h-4"></i>
                    Riwayat Transaksi
                    <span class="ml-auto text-xs font-bold px-1.5 py-0.5 rounded-md" style="background: var(--bg-main); color: var(--text-soft)">{{ $customer->total_rentals }}</span>
                </button>
            </div>
        </div>

        {{-- ============================================================
             KONTEN TAB (kanan — bisa panjang, tapi terbagi per menu)
             ============================================================ --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- TAB: Ringkasan --}}
            <div x-show="activeTab === 'ringkasan'" x-cloak class="space-y-5">
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="card p-5">
                        <div class="flex items-center gap-3 mb-1">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background: var(--secondary)">
                                <i data-lucide="wallet" class="w-4 h-4" style="color: var(--primary)"></i>
                            </div>
                            <p class="text-sm font-medium" style="color: var(--text-dark)">Total Transaksi Lunas</p>
                        </div>
                        <p class="text-2xl font-bold font-playfair mt-2" style="color: var(--text-dark)">
                            Rp {{ number_format($customer->rentals->where('payment_status', 'paid')->sum('total_amount'), 0, ',', '.') }}
                        </p>
                        <p class="text-xs mt-1" style="color: var(--text-soft)">dari transaksi yang tersinkron di halaman ini</p>
                    </div>
                    <div class="card p-5">
                        <div class="flex items-center gap-3 mb-1">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background: #EFF6FF">
                                <i data-lucide="clock" class="w-4 h-4" style="color: #1D4ED8"></i>
                            </div>
                            <p class="text-sm font-medium" style="color: var(--text-dark)">Sewa Aktif Saat Ini</p>
                        </div>
                        <p class="text-2xl font-bold font-playfair mt-2" style="color: var(--text-dark)">
                            {{ $customer->active_rentals->count() }}
                        </p>
                        <p class="text-xs mt-1" style="color: var(--text-soft)">termasuk yang overdue</p>
                    </div>
                </div>

                @if($customer->rentals->isNotEmpty())
                <div class="card p-5">
                    <h3 class="font-playfair font-semibold text-sm mb-4" style="color: var(--text-dark)">Transaksi Terbaru</h3>
                    <div class="space-y-3">
                        @foreach($customer->rentals->take(3) as $rental)
                        <a href="{{ route('rentals.show', $rental) }}" class="flex items-center gap-3 group">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                                 style="background: {{ match($rental->rental_status) { 'returned' => '#F0FDF4', 'overdue' => '#FFF1F0', 'active' => '#EFF6FF', default => '#FFF8E7' } }}">
                                <i data-lucide="{{ match($rental->rental_status) { 'returned' => 'check-circle', 'overdue' => 'alert-circle', 'active' => 'clock', default => 'hourglass' } }}" class="w-3.5 h-3.5"
                                   style="color: {{ match($rental->rental_status) { 'returned' => '#15803D', 'overdue' => '#C0392B', 'active' => '#1D4ED8', default => '#B7791F' } }}"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium font-mono group-hover:underline truncate" style="color: var(--text-dark)">{{ $rental->invoice_number }}</p>
                                <p class="text-xs" style="color: var(--text-soft)">{{ $rental->rental_date->format('d M Y') }}</p>
                            </div>
                            <span class="badge badge-{{ $rental->status_badge_color }} text-[10px] flex-shrink-0">{{ $rental->status_label }}</span>
                        </a>
                        @endforeach
                    </div>
                    <button @click="activeTab = 'riwayat'" class="text-xs font-semibold mt-4" style="color: var(--primary)">
                        Lihat semua riwayat →
                    </button>
                </div>
                @endif
            </div>

            {{-- TAB: Riwayat Transaksi --}}
            <div x-show="activeTab === 'riwayat'" x-cloak class="card overflow-hidden">
                <div class="p-5 border-b" style="border-color: var(--border)">
                    <div class="flex items-center justify-between">
                        <h3 class="font-playfair font-semibold text-base" style="color: var(--text-dark)">Riwayat Penyewaan</h3>
                        <a href="{{ route('rentals.index', ['customer_id' => $customer->id]) }}" class="text-xs" style="color: var(--primary)">Lihat Semua →</a>
                    </div>
                </div>
                @forelse($customer->rentals as $rental)
                <div class="flex items-center gap-4 p-4 border-b transition-colors hover:bg-amber-50/30"
                     style="border-color: var(--border)">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                         style="background: {{ match($rental->rental_status) { 'returned' => '#F0FDF4', 'overdue' => '#FFF1F0', 'active' => '#EFF6FF', default => '#FFF8E7' } }}">
                        <i data-lucide="{{ match($rental->rental_status) { 'returned' => 'check-circle', 'overdue' => 'alert-circle', 'active' => 'clock', default => 'hourglass' } }}" class="w-5 h-5"
                           style="color: {{ match($rental->rental_status) { 'returned' => '#15803D', 'overdue' => '#C0392B', 'active' => '#1D4ED8', default => '#B7791F' } }}"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('rentals.show', $rental) }}" class="font-mono font-semibold text-sm hover:underline" style="color: var(--primary)">{{ $rental->invoice_number }}</a>
                            <span class="badge badge-{{ $rental->status_badge_color }} text-[10px]">{{ $rental->status_label }}</span>
                        </div>
                        <p class="text-xs mt-0.5" style="color: var(--text-soft)">
                            {{ $rental->rental_date->format('d M Y') }} → {{ $rental->return_due_date->format('d M Y') }}
                            ({{ $rental->duration_days }} hari)
                        </p>
                        <div class="flex flex-wrap gap-1 mt-1">
                            @foreach($rental->items->take(3) as $item)
                            <span class="text-[10px] px-1.5 py-0.5 rounded" style="background: var(--secondary); color: var(--text-soft)">{{ $item->product_name }}</span>
                            @endforeach
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="font-bold text-sm" style="color: var(--text-dark)">Rp {{ number_format($rental->total_amount, 0, ',', '.') }}</p>
                        <span class="badge {{ match($rental->payment_status) { 'paid' => 'badge-green', 'partial' => 'badge-yellow', default => 'badge-red' } }} text-[10px] mt-0.5">
                            {{ $rental->payment_status_label }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="py-12 text-center">
                    <i data-lucide="shirt" class="w-8 h-8 mx-auto mb-2" style="color: var(--border)"></i>
                    <p class="text-sm" style="color: var(--text-soft)">Belum ada riwayat penyewaan</p>
                </div>
                @endforelse
            </div>

        </div>
    </div>

    {{-- Blacklist Modal --}}
    <div x-show="showBlacklist" x-cloak class="fixed inset-0 z-50 flex items-center justify-center modal-overlay">
        <div class="modal-box w-full max-w-sm mx-4 p-6">
            <h3 class="font-playfair font-semibold text-base mb-4" style="color: var(--text-dark)">
                {{ $customer->is_blacklisted ? 'Hapus dari Blacklist' : 'Blacklist Customer' }}
            </h3>
            <form method="POST" action="{{ route('customers.blacklist', $customer) }}">
                @csrf
                @method('PATCH')
                @if(!$customer->is_blacklisted)
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">Alasan Blacklist</label>
                    <textarea name="reason" rows="3" class="form-input" placeholder="Tulis alasan blacklist..." required></textarea>
                </div>
                @endif
                <div class="flex gap-3">
                    <button type="button" @click="showBlacklist = false" class="btn-secondary flex-1 justify-center">Batal</button>
                    <button type="submit" class="btn-primary flex-1 justify-center {{ $customer->is_blacklisted ? '' : 'bg-red-500' }}"
                            style="{{ !$customer->is_blacklisted ? 'background: linear-gradient(135deg, #EF4444, #DC2626); box-shadow: 0 2px 8px rgba(239,68,68,0.3)' : '' }}">
                        <i data-lucide="{{ $customer->is_blacklisted ? 'user-check' : 'user-x' }}" class="w-4 h-4"></i>
                        {{ $customer->is_blacklisted ? 'Hapus Blacklist' : 'Blacklist Customer' }}
                    </button>
                </div>
            </form>
        </div>
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

    var form = document.getElementById('force-delete-form-' + id + '-show');
    if (!form) { alert('Form penghapusan permanen tidak ditemukan.'); return; }
    var input = form.querySelector('input[name="reason"]');
    if (input) input.value = reason;
    form.submit();
}
</script>
@endpush