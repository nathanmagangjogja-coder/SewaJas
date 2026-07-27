@extends('Layouts.app')
@section('title', 'Detail Log Aktivitas')

@section('content')
<div class="max-w-3xl mx-auto space-y-5">

    <div class="flex items-center gap-3">
        <a href="{{ route('audit.index') }}" class="p-2 rounded-xl hover:bg-[var(--bg-soft)] transition-colors">
            <i data-lucide="arrow-left" class="w-5 h-5" style="color:var(--text-soft)"></i>
        </a>
        <h1 class="font-playfair text-xl font-bold" style="color:var(--text-dark)">Detail Log</h1>
    </div>

    {{-- Info Utama --}}
    <div class="card p-5 space-y-4">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-xs font-semibold mb-1" style="color:var(--text-soft)">WAKTU</p>
                <p style="color:var(--text-dark)">{{ $log->created_at->format('d/m/Y H:i:s') }}</p>
                <p class="text-xs" style="color:var(--text-soft)">{{ $log->created_at->diffForHumans() }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold mb-1" style="color:var(--text-soft)">AKSI</p>
                <span class="px-2 py-1 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700">
                    {{ $log->action_label }}
                </span>
            </div>
            <div>
                <p class="text-xs font-semibold mb-1" style="color:var(--text-soft)">STAF</p>
                <p class="font-medium" style="color:var(--text-dark)">{{ $log->user?->name ?? 'System' }}</p>
                <p class="text-xs" style="color:var(--text-soft)">{{ $log->user?->role ?? '' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold mb-1" style="color:var(--text-soft)">MODEL</p>
                <p style="color:var(--text-dark)">{{ $log->model_name }}</p>
                @if($log->model_id)
                <p class="text-xs font-mono" style="color:var(--text-soft)">#{{ $log->model_id }}</p>
                @endif
            </div>
            <div class="col-span-2">
                <p class="text-xs font-semibold mb-1" style="color:var(--text-soft)">DESKRIPSI</p>
                <p style="color:var(--text-dark)">{{ $log->description }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold mb-1" style="color:var(--text-soft)">IP ADDRESS</p>
                <p class="font-mono text-xs" style="color:var(--text-dark)">{{ $log->ip_address ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold mb-1" style="color:var(--text-soft)">CABANG</p>
                <p style="color:var(--text-dark)">{{ $log->branch?->name ?? '-' }}</p>
            </div>
        </div>
    </div>

    {{-- Perubahan Data --}}
    @if($log->has_diff)
    <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b" style="border-color:var(--border)">
            <h3 class="font-semibold text-sm" style="color:var(--text-dark)">Perubahan Data</h3>
        </div>

        @php $changes = $log->meaningful_changes; @endphp

        @if(count($changes) > 0)
        <table class="w-full text-sm">
            <thead>
                <tr style="background:var(--bg-soft)">
                    <th class="text-left px-4 py-2.5 text-xs font-semibold" style="color:var(--text-soft)">Field</th>
                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-red-500">Sebelum</th>
                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-green-600">Sesudah</th>
                </tr>
            </thead>
            <tbody class="divide-y" style="border-color:var(--border)">
                @foreach($changes as $change)
                <tr class="hover:bg-[var(--bg-soft)]">
                    <td class="px-4 py-3 font-medium" style="color:var(--text-dark)">{{ $change['field'] }}</td>
                    <td class="px-4 py-3 text-red-600 font-mono text-xs">{{ $change['old'] }}</td>
                    <td class="px-4 py-3 text-green-700 font-mono text-xs font-semibold">{{ $change['new'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        {{-- Fallback: tampilkan raw JSON jika tidak ada perubahan yang terdeteksi --}}
        <div class="p-4 grid grid-cols-2 gap-4">
            @if($log->old_values)
            <div>
                <p class="text-xs font-semibold mb-2 text-red-500">Data Lama</p>
                <pre class="text-xs p-3 rounded-lg overflow-auto" style="background:var(--bg-soft); color:var(--text-dark); max-height:200px">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
            @endif
            @if($log->new_values)
            <div>
                <p class="text-xs font-semibold mb-2 text-green-600">Data Baru</p>
                <pre class="text-xs p-3 rounded-lg overflow-auto" style="background:var(--bg-soft); color:var(--text-dark); max-height:200px">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
            @endif
        </div>
        @endif
    </div>
    @endif

</div>
@endsection
