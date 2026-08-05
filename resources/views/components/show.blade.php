@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-6">

    <a href="{{ url()->previous() }}" class="inline-flex items-center gap-1 text-sm text-white/40 hover:text-white mb-5 transition">
        ← Kembali
    </a>

    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">

        <div class="flex items-start gap-4 p-6 border-b border-slate-200/70">
            <div class="text-2xl w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-slate-100 border border-slate-200">
                {{ $notification->icon_name ?? '🔔' }}
            </div>
            <div class="flex-1">
                @if(!$notification->is_read)
                    <span class="inline-block text-[11px] text-yellow-600 bg-yellow-100 border border-yellow-200 px-2 py-0.5 rounded-full mb-2">
                        ● Belum dibaca
                    </span>
                @endif
                <h1 class="text-slate-900 font-semibold text-lg leading-snug mb-1">
                    {{ $notification->title }}
                </h1>
                @if(!empty($notification->meta['summary']))
                    <p class="text-sm text-slate-600 mb-2">{{ $notification->meta['summary'] }}</p>
                @endif
                <p class="text-xs text-slate-500">
                    {{ $notification->created_at->diffForHumans() }}
                    · {{ $notification->created_at->translatedFormat('d F Y, H:i') }} WIB
                </p>
            </div>
        </div>

        <div class="p-6 space-y-5">

            @if(isset($notification->meta['invoice_no']))
                <span class="inline-flex items-center gap-1.5 text-xs text-slate-700 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-md">
                    🧾 {{ $notification->meta['invoice_no'] }}
                </span>
            @endif

            <div class="text-sm text-slate-700 leading-relaxed bg-slate-50 rounded-xl p-4 border-l-2 border-yellow-400/30">
                {!! nl2br(e($notification->message)) !!}
            </div>

            @if(!empty($notification->meta))
                <div class="grid grid-cols-2 gap-2 text-sm">
                    @foreach($notification->meta as $key => $value)
                        @if($key !== 'invoice_no')
                            <div class="bg-slate-50 rounded-lg px-3 py-2 border border-slate-200">
                                <div class="text-[11px] text-slate-400 capitalize mb-0.5">{{ str_replace('_', ' ', $key) }}</div>
                                <div class="text-slate-700 font-medium text-xs">{{ $value }}</div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

            <div class="flex items-center gap-2 flex-wrap pt-2 border-t border-slate-200/70">

                @if($notification->action_url)
                    <a href="{{ $notification->action_url }}"
                       class="inline-flex items-center gap-1.5 text-sm text-slate-900 bg-slate-100 border border-slate-200 px-4 py-2 rounded-lg hover:bg-slate-200 transition">
                        Lihat Detail →
                    </a>
                @endif

                @if(!$notification->is_read)
                    <form action="{{ route('notifications.read', $notification->id) }}" method="POST">
                        @csrf
                        <button class="text-sm text-slate-600 border border-slate-200 px-4 py-2 rounded-lg hover:text-slate-900 hover:bg-slate-100 transition">
                            Tandai dibaca
                        </button>
                    </form>
                @endif

                <form action="{{ route('notifications.destroy', $notification->id) }}" method="POST"
                      class="ml-auto" onsubmit="return confirm('Hapus notifikasi ini?')">
                    @csrf @method('DELETE')
                    <button class="text-sm text-red-600 border border-red-100 px-4 py-2 rounded-lg hover:text-red-700 hover:bg-red-50 transition">
                        Hapus
                    </button>
                </form>

            </div>
        </div>

        <div class="px-6 py-3 text-[11px] text-slate-500 bg-slate-50 border-t border-slate-200/70">
            Dikirim {{ $notification->created_at->translatedFormat('d F Y · H:i') }} WIB
        </div>

    </div>
</div>
@endsection