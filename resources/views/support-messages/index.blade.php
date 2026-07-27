@extends('Layouts.app')
@section('title', 'Pesan Masuk')

@section('content')
<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="font-playfair text-2xl font-bold" style="color:var(--text-dark)">Pesan Masuk</h1>
            <p class="text-sm mt-0.5" style="color:var(--text-soft)">
                Laporan bug &amp; pesan dari Sales / Admin Toko ke Super Admin
            </p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 gap-4">
        <div class="card p-4">
            <p class="text-xs mb-1" style="color:var(--text-soft)">Total Pesan</p>
            <p class="text-2xl font-bold font-playfair" style="color:var(--text-dark)">{{ number_format($messages->total()) }}</p>
        </div>
        <div class="card p-4" style="border-left:3px solid #EF4444">
            <p class="text-xs mb-1" style="color:var(--text-soft)">Belum Dibaca</p>
            <p class="text-2xl font-bold font-playfair text-red-500">{{ $unreadCount }}</p>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card p-4">
        <form method="GET" action="{{ route('support-messages.index') }}"
              class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-semibold mb-1" style="color:var(--text-soft)">Status</label>
                <select name="status" class="form-input w-full text-sm">
                    <option value="">Semua</option>
                    <option value="unread" {{ request('status') === 'unread' ? 'selected' : '' }}>Belum Dibaca</option>
                    <option value="read" {{ request('status') === 'read' ? 'selected' : '' }}>Sudah Dibaca</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1" style="color:var(--text-soft)">Jenis</label>
                <select name="type" class="form-input w-full text-sm">
                    <option value="">Semua</option>
                    @foreach(\App\Models\SupportMessage::typeLabels() as $value => $label)
                    <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-primary px-4 py-2 text-sm flex items-center gap-2">
                    <i data-lucide="search" class="w-4 h-4"></i> Filter
                </button>
                <a href="{{ route('support-messages.index') }}" class="btn-secondary px-4 py-2 text-sm">Reset</a>
            </div>
        </form>
    </div>

    {{-- Daftar Pesan --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b flex items-center gap-2" style="border-color:var(--border)">
            <i data-lucide="inbox" class="w-4 h-4" style="color:var(--primary)"></i>
            <h3 class="font-semibold text-sm" style="color:var(--text-dark)">Daftar Pesan</h3>
            <span class="ml-auto text-xs" style="color:var(--text-soft)">{{ $messages->total() }} pesan</span>
        </div>

        <div class="divide-y" style="border-color:var(--border)">
            @forelse($messages as $msg)
            <div class="p-4 flex flex-col sm:flex-row sm:items-start gap-3 {{ $msg->status === 'unread' ? 'bg-[var(--bg-soft)]' : '' }}">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 text-lg"
                     style="background:{{ $msg->type === 'bug_report' ? '#FEE2E2' : '#DBEAFE' }}">
                    {{ $msg->type === 'bug_report' ? '🐛' : '💬' }}
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-2 py-0.5 rounded-lg text-xs font-semibold
                            {{ $msg->type === 'bug_report' ? 'bg-red-50 text-red-700' : 'bg-blue-50 text-blue-700' }}">
                            {{ $msg->type_label }}
                        </span>
                        @if($msg->status === 'unread')
                        <span class="px-2 py-0.5 rounded-lg text-xs font-semibold bg-amber-50 text-amber-700">
                            Belum Dibaca
                        </span>
                        @endif
                        <span class="text-xs" style="color:var(--text-soft)">
                            {{ $msg->created_at->diffForHumans() }}
                        </span>
                    </div>

                    <p class="text-sm font-semibold mt-1" style="color:var(--text-dark)">
                        {{ $msg->user->name ?? 'User dihapus' }}
                        @if($msg->branch)
                        <span class="font-normal" style="color:var(--text-soft)"> — {{ $msg->branch->name }}</span>
                        @endif
                    </p>

                    @if($msg->subject)
                    <p class="text-sm font-medium mt-1" style="color:var(--text-dark)">{{ $msg->subject }}</p>
                    @endif
                    <p class="text-sm mt-1 whitespace-pre-line" style="color:var(--text-soft)">{{ $msg->message }}</p>

                    @if($msg->status === 'read' && $msg->readBy)
                    <p class="text-xs mt-2" style="color:var(--text-soft)">
                        Dibaca oleh {{ $msg->readBy->name }} · {{ $msg->read_at?->diffForHumans() }}
                    </p>
                    @endif
                </div>

                @if($msg->status === 'unread')
                <form method="POST" action="{{ route('support-messages.read', $msg) }}" class="flex-shrink-0">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border hover:bg-[var(--bg-soft)] transition-colors"
                            style="color:var(--primary); border-color:var(--primary)">
                        Tandai Dibaca
                    </button>
                </form>
                @endif
            </div>
            @empty
            <div class="py-12 text-center text-sm" style="color:var(--text-soft)">
                <i data-lucide="inbox" class="w-10 h-10 mx-auto mb-3 opacity-20"></i>
                <p>Belum ada pesan masuk.</p>
            </div>
            @endforelse
        </div>

        @if($messages->hasPages())
        <div class="px-4 py-3 border-t" style="border-color:var(--border)">{{ $messages->links() }}</div>
        @endif
    </div>
</div>
@endsection