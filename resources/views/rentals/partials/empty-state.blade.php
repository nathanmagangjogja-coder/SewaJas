{{--
    resources/views/rentals/partials/empty-state.blade.php

    Ditampilkan saat daftar penyewaan kosong (baik tabel desktop maupun
    kartu mobile). Sebelumnya file ini direferensikan lewat @include tapi
    belum pernah dibuat, sehingga setiap kali hasil filter kosong (mis.
    ?status=today) langsung menyebabkan error 500 "View not found".
--}}
<div class="flex flex-col items-center justify-center gap-3">
    <div class="w-14 h-14 rounded-full flex items-center justify-center" style="background: var(--bg-main)">
        <i data-lucide="inbox" class="w-6 h-6" style="color: var(--text-soft)"></i>
    </div>
    <div>
        <p class="font-semibold text-sm" style="color: var(--text-dark)">Tidak ada penyewaan ditemukan</p>
        <p class="text-xs mt-1" style="color: var(--text-soft)">
            @if(request()->anyFilled(['status', 'payment_status', 'search', 'date_from', 'date_to']))
                Tidak ada data yang cocok dengan filter yang sedang digunakan.
            @else
                Belum ada data penyewaan sama sekali.
            @endif
        </p>
    </div>
    @if(request()->anyFilled(['status', 'payment_status', 'search', 'date_from', 'date_to']))
    <a href="{{ route('rentals.index') }}" class="btn-secondary text-xs mt-1">
        <i data-lucide="x" class="w-3.5 h-3.5"></i>
        Reset Filter
    </a>
    @else
    <a href="{{ route('rentals.create') }}" class="btn-primary text-xs mt-1">
        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
        Buat Penyewaan Baru
    </a>
    @endif
</div>