{{--
    resources/views/components/pagination.blade.php
    Custom pagination component yang dipanggil oleh banyak view:
    {{ $data->links('components.pagination') }}
--}}
@if ($paginator->hasPages())
<div class="flex items-center justify-between gap-2 text-sm">

    {{-- Info jumlah --}}
    <p class="text-xs" style="color:var(--text-soft)">
        Menampilkan
        <span class="font-semibold" style="color:var(--text-dark)">{{ $paginator->firstItem() }}</span>
        –
        <span class="font-semibold" style="color:var(--text-dark)">{{ $paginator->lastItem() }}</span>
        dari
        <span class="font-semibold" style="color:var(--text-dark)">{{ $paginator->total() }}</span>
        data
    </p>

    {{-- Tombol navigasi --}}
    <div class="flex items-center gap-1">

        {{-- Prev --}}
        @if ($paginator->onFirstPage())
        <span class="px-3 py-1.5 rounded-lg text-xs cursor-not-allowed opacity-40"
              style="background:var(--bg-soft); color:var(--text-soft)">
            ← Prev
        </span>
        @else
        <a href="{{ $paginator->previousPageUrl() }}"
           class="px-3 py-1.5 rounded-lg text-xs transition-colors hover:bg-[var(--secondary)]"
           style="background:var(--bg-soft); color:var(--text-dark)">
            ← Prev
        </a>
        @endif

        {{-- Nomor halaman --}}
        @foreach ($elements as $element)
            @if (is_string($element))
            <span class="px-2 py-1.5 text-xs" style="color:var(--text-soft)">…</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                    <span class="px-3 py-1.5 rounded-lg text-xs font-semibold"
                          style="background:var(--primary); color:#1E1A16">
                        {{ $page }}
                    </span>
                    @else
                    <a href="{{ $url }}"
                       class="px-3 py-1.5 rounded-lg text-xs transition-colors hover:bg-[var(--secondary)]"
                       style="background:var(--bg-soft); color:var(--text-dark)">
                        {{ $page }}
                    </a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}"
           class="px-3 py-1.5 rounded-lg text-xs transition-colors hover:bg-[var(--secondary)]"
           style="background:var(--bg-soft); color:var(--text-dark)">
            Next →
        </a>
        @else
        <span class="px-3 py-1.5 rounded-lg text-xs cursor-not-allowed opacity-40"
              style="background:var(--bg-soft); color:var(--text-soft)">
            Next →
        </span>
        @endif

    </div>
</div>
@endif