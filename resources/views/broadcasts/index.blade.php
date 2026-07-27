@extends('layouts.app')

@section('title', isset($broadcast) ? 'Riwayat Broadcast' : 'Jadwal Broadcast')
@section('page-title', isset($broadcast) ? 'Riwayat Broadcast' : 'Jadwal Broadcast')
@section('subtitle', isset($broadcast) ? 'Lihat log pengiriman untuk jadwal broadcast ini' : 'Kelola jadwal broadcast WhatsApp otomatis')

@section('content')
<div class="space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-playfair text-2xl font-bold" style="color: var(--text-dark)">{{ isset($broadcast) ? 'Riwayat Broadcast' : 'Jadwal Broadcast' }}</h1>
            <p class="text-sm mt-1" style="color: var(--text-soft)">{{ isset($broadcast) ? 'Semua aktivitas pengiriman untuk jadwal ' . $broadcast->name : 'Buat dan kelola jadwal broadcast otomatis untuk pelanggan aktif, overdue, atau returning soon.' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('broadcasts.create') }}" class="btn-primary">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Tambah Jadwal
            </a>
            @if(isset($broadcast))
            <a href="{{ route('broadcasts.index') }}" class="btn-secondary">
                <i data-lucide="corner-up-left" class="w-4 h-4"></i>
                Kembali
            </a>
            @endif
        </div>
    </div>

    @if(session('success'))
    <div class="card p-4 border border-green-200" style="background: #ECFDF5; color: #064E3B;">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="card p-4 border border-red-200" style="background: #FEF2F2; color: #991B1B;">
        {{ session('error') }}
    </div>
    @endif

    @isset($schedules)
    <div class="card p-4">
        <div class="flex items-center justify-between gap-4 mb-4">
            <div>
                <h2 class="font-semibold text-lg" style="color: var(--text-dark)">Daftar Jadwal</h2>
                <p class="text-sm" style="color: var(--text-soft)">Atur waktu dan pesan broadcast untuk pelanggan Anda.</p>
            </div>
            <span class="text-sm px-3 py-1.5 rounded-lg font-medium" style="background: var(--secondary); color: var(--primary)">{{ $schedules->total() }} jadwal</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full elegant-table">
                <thead>
                    <tr>
                        <th class="text-left">Nama</th>
                        <th class="text-left">Target Audien</th>
                        <th class="text-left hidden lg:table-cell">Waktu</th>
                        <th class="text-center">Cooldown</th>
                        <th class="text-center">Aktif</th>
                        <th class="text-center">Terkirim</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $schedule)
                    <tr>
                        <td>
                            <div class="font-semibold" style="color: var(--text-dark)">{{ $schedule->name }}</div>
                            <div class="text-xs" style="color: var(--text-soft)">{{ $schedule->description ?? 'Tanpa deskripsi' }}</div>
                        </td>
                        <td class="text-sm" style="color: var(--text-soft)">
                            @if($schedule->target_audience === 'all') Semua Pelanggan
                            @elseif($schedule->target_audience === 'active_renters') Penyewa Aktif
                            @elseif($schedule->target_audience === 'overdue') Overdue
                            @else Mendekati Pengembalian
                            @endif
                        </td>
                        <td class="hidden lg:table-cell text-sm" style="color: var(--text-soft)">{{ !empty($schedule->send_at_times) ? implode(', ', $schedule->send_at_times) : 'Non-aktif' }}</td>
                        <td class="text-center text-sm" style="color: var(--text-soft)">{{ $schedule->cooldown_hours ?? 24 }} jam</td>
                        <td class="text-center">
                            <span class="badge {{ $schedule->is_active ? 'badge-green' : 'badge-gray' }}">
                                {{ $schedule->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="text-center" style="color: var(--text-soft)">{{ $schedule->logs_count ?? 0 }}</td>
                        <td class="text-center space-x-1">
                            <a href="{{ route('broadcasts.edit', $schedule) }}" class="btn-secondary text-xs px-3 py-2 inline-flex items-center gap-1">
                                <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                Edit
                            </a>
                            <a href="{{ route('broadcasts.logs', $schedule) }}" class="btn-secondary text-xs px-3 py-2 inline-flex items-center gap-1">
                                <i data-lucide="list" class="w-3.5 h-3.5"></i>
                                Logs
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-8" style="color: var(--text-soft)">Belum ada jadwal broadcast.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $schedules->links() }}</div>
    </div>
    @endisset

    <div class="card p-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: var(--surf-primary); color: var(--primary);">
                    <i data-lucide="mail-open" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="font-semibold text-lg" style="color: var(--text-dark)">Kirim Manual ke Pilihan</h2>
                    <p class="text-sm" style="color: var(--text-soft)">Pilih pelanggan lalu kirim pesan sekarang tanpa menjadwalkan.</p>
                </div>
            </div>
            <span class="text-sm px-3 py-1.5 rounded-lg font-medium" style="background: var(--secondary); color: var(--text-soft);">{{ count($customers) }} pelanggan tersedia</span>
        </div>

        <form method="POST" action="{{ route('broadcasts.send-selected') }}">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="lg:col-span-2 space-y-3">
                    <div>
                        <label class="block text-sm font-medium mb-2" style="color: var(--text-soft)">
                            <i data-lucide="message-square" class="w-4 h-4 inline mb-1 mr-1"></i>
                            Pesan Broadcast
                        </label>
                        <textarea
                            name="message"
                            rows="6"
                            placeholder="Tulis pesan yang ingin Anda kirim ke pelanggan yang Anda pilih..."
                            class="w-full form-input"
                            required></textarea>
                    </div>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium mb-2" style="color: var(--text-soft)">
                            <i data-lucide="users" class="w-4 h-4 inline mb-1 mr-1"></i>
                            Pilih Pelanggan
                        </label>
                        <select
                            name="customer_ids[]"
                            multiple
                            size="12"
                            class="w-full form-input">
                            @foreach($customers as $c)
                            <option value="{{ $c->id }}">
                                {{ $c->name }} — {{ $c->phone }}
                            </option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 mt-2 text-xs p-2 rounded-lg" style="background: var(--secondary); color: var(--text-muted);">
                            <i data-lucide="info" class="w-4 h-4"></i>
                            Tekan Ctrl/Cmd + klik untuk memilih beberapa
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t" style="border-color: var(--divider);">
                <button type="submit" class="btn-primary flex items-center gap-2">
                    <i data-lucide="send" class="w-5 h-5"></i>
                    Kirim ke Pilihan Sekarang
                </button>
            </div>
        </form>
    </div>

    <div class="card p-4">
        <div class="flex items-center justify-between gap-4 mb-4">
            <div>
                <h2 class="font-semibold text-lg" style="color: var(--text-dark)">Log Pengiriman</h2>
                <p class="text-sm" style="color: var(--text-soft)">{{ isset($broadcast) ? 'Riwayat pengiriman untuk jadwal ' . $broadcast->name : '20 log terakhir dari semua jadwal broadcast yang dikirim.' }}</p>
            </div>
            @isset($broadcast)
            <div class="inline-flex gap-2 items-center">
                <form method="POST" action="{{ route('broadcasts.send-now', $broadcast) }}" class="inline-block">
                    @csrf
                    <label class="inline-flex items-center mr-2">
                        <input type="checkbox" name="distribute" value="1" class="mr-1">
                        <span class="text-xs" style="color: var(--text-soft)">Distribusikan template ke nomor berbeda (1 template → 1 pelanggan)</span>
                    </label>
                    <button type="submit" class="btn-primary text-xs px-3 py-2 inline-flex items-center gap-1">
                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        Kirim Sekarang
                    </button>
                </form>

                <form method="POST" action="{{ route('broadcasts.clear-logs', $broadcast) }}" class="inline-block" onsubmit="return confirm('Hapus semua log untuk jadwal ini? Tindakan tidak dapat dibatalkan.');">
                    @csrf
                    <button type="submit" class="btn-secondary text-xs px-3 py-2 inline-flex items-center gap-1">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        Hapus Log
                    </button>
                </form>
            </div>
            @endisset
        </div>

        <div class="overflow-x-auto">
            <table class="w-full elegant-table">
                <thead>
                    <tr>
                        <th class="text-left">Waktu</th>
                        <th class="text-left">Customer</th>
                        <th class="text-left">Template</th>
                        <th class="text-center">Status</th>
                        <th class="text-left hidden lg:table-cell">Response</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="text-sm" style="color: var(--text-soft)">{{ optional($log->sent_at)->format('d M Y H:i') ?? 'Belum' }}</td>
                        <td>
                            <div class="font-semibold" style="color: var(--text-dark)">{{ $log->customer->name ?? 'Unknown' }}</div>
                            <div class="text-xs" style="color: var(--text-soft)">{{ $log->customer->phone ?? '-' }}</div>
                        </td>
                        <td class="text-sm" style="color: var(--text-soft)">{{ $log->message_sent }}</td>
                        <td class="text-center">
                            <span class="badge {{ $log->status === 'sent' ? 'badge-green' : ($log->status === 'failed' ? 'badge-red' : 'badge-gray') }}">{{ ucfirst($log->status) }}</span>
                        </td>
                        <td class="hidden lg:table-cell text-xs" style="color: var(--text-soft)">{{ Str::limit($log->fonnte_response ?? '-', 80) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-8" style="color: var(--text-soft)">Belum ada log broadcast.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $logs->links() }}</div>
    </div>
</div>
@endsection
