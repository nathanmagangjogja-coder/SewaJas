@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-6">

    <a href="{{ url()->previous() }}" class="inline-flex items-center gap-1 text-sm text-white/40 hover:text-white mb-5 transition">
        ← Kembali
    </a>

    <div class="bg-[#1a1d26] border border-[rgba(201,168,76,0.2)] rounded-2xl overflow-hidden">

        <div class="flex items-start gap-4 p-6 border-b border-white/[0.07]">
            <div class="text-2xl w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-white/5 border border-white/10">
                {{ $notification->icon_name ?? '🔔' }}
            </div>
            <div class="flex-1">
                @if(!$notification->is_read)
                    <span class="inline-block text-[11px] text-yellow-400 bg-yellow-400/10 border border-yellow-400/20 px-2 py-0.5 rounded-full mb-2">
                        ● Belum dibaca
                    </span>
                @endif
                <h1 class="text-[#e2c47b] font-semibold text-lg leading-snug mb-1">
                    {{ $notification->title }}
                </h1>
                <p class="text-xs text-white/40">
                    {{ $notification->created_at->diffForHumans() }}
                    · {{ $notification->created_at->translatedFormat('d F Y, H:i') }} WIB
                </p>
            </div>
        </div>

        <div class="p-6 space-y-5">

            @if(isset($notification->meta['invoice_no']))
                <span class="inline-flex items-center gap-1.5 text-xs text-[#e2c47b] bg-yellow-400/10 border border-yellow-400/20 px-2.5 py-1 rounded-md">
                    🧾 {{ $notification->meta['invoice_no'] }}
                </span>
            @endif

            <div class="text-sm text-white/65 leading-relaxed bg-[#22253a] rounded-xl p-4 border-l-2 border-yellow-500/30">
                {!! nl2br(e($notification->message)) !!}
            </div>

            @if(!empty($notification->meta))
                <div class="grid grid-cols-2 gap-2 text-sm">
                    @foreach($notification->meta as $key => $value)
                        @if($key !== 'invoice_no')
                            <div class="bg-[#22253a] rounded-lg px-3 py-2">
                                <div class="text-[11px] text-white/35 capitalize mb-0.5">{{ str_replace('_', ' ', $key) }}</div>
                                <div class="text-white/80 font-medium text-xs">{{ $value }}</div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

            <div class="flex items-center gap-2 flex-wrap pt-2 border-t border-white/[0.07]">

                @if($notification->action_url)
                    <a href="{{ $notification->action_url }}"
                       class="inline-flex items-center gap-1.5 text-sm text-[#e2c47b] bg-yellow-400/10 border border-yellow-400/25 px-4 py-2 rounded-lg hover:bg-yellow-400/20 transition">
                        Lihat Detail →
                    </a>
                @endif

                @if(!$notification->is_read)
                    <form action="{{ route('notifications.read', $notification->id) }}" method="POST">
                        @csrf
                        <button class="text-sm text-white/50 border border-white/10 px-4 py-2 rounded-lg hover:text-white hover:bg-white/5 transition">
                            Tandai dibaca
                        </button>
                    </form>
                @endif

                <form action="{{ route('notifications.destroy', $notification->id) }}" method="POST"
                      class="ml-auto" onsubmit="return confirm('Hapus notifikasi ini?')">
                    @csrf @method('DELETE')
                    <button class="text-sm text-red-400/70 border border-red-400/15 px-4 py-2 rounded-lg hover:text-red-400 hover:bg-red-400/10 transition">
                        Hapus
                    </button>
                </form>

            </div>
        </div>

        <div class="px-6 py-3 text-[11px] text-white/25 bg-black/15">
            Dikirim {{ $notification->created_at->translatedFormat('d F Y · H:i') }} WIB
        </div>

    </div>
</div>
@endsection