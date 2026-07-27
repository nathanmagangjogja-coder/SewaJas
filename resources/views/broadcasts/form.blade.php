@extends('layouts.app')

@section('title', isset($broadcast) && $broadcast->exists ? 'Edit Jadwal Broadcast' : 'Tambah Jadwal Broadcast')
@section('page-title', isset($broadcast) && $broadcast->exists ? 'Edit Jadwal Broadcast' : 'Tambah Jadwal Broadcast')
@section('subtitle', isset($broadcast) && $broadcast->exists ? $broadcast->name : 'Buat jadwal pengiriman pesan WhatsApp otomatis')

@section('content')
<div class="max-w-5xl mx-auto">
    <form method="POST" action="{{ $broadcast->exists ? route('broadcasts.update', $broadcast) : route('broadcasts.store') }}" id="broadcastForm">
        @csrf
        @if($broadcast->exists) @method('PATCH') @endif

        <div class="card p-6 space-y-6">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium mb-2" style="color: var(--text-dark)">Nama Jadwal</label>
                    <input type="text" name="name" value="{{ old('name', $broadcast->name) }}" class="form-input" required>
                    @error('name')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2" style="color: var(--text-dark)">Target Audience</label>
                    <select name="target_audience" class="form-input" required>
                        <option value="all" {{ old('target_audience', $broadcast->target_audience) === 'all' ? 'selected' : '' }}>Semua Pelanggan</option>
                        <option value="active_renters" {{ old('target_audience', $broadcast->target_audience) === 'active_renters' ? 'selected' : '' }}>Penyewa Aktif / Overdue</option>
                        <option value="overdue" {{ old('target_audience', $broadcast->target_audience) === 'overdue' ? 'selected' : '' }}>Overdue</option>
                        <option value="returning_soon" {{ old('target_audience', $broadcast->target_audience) === 'returning_soon' ? 'selected' : '' }}>Mendekati Pengembalian</option>
                    </select>
                    @error('target_audience')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium mb-2" style="color: var(--text-dark)">Periode Cooldown (Jam)</label>
                    <input type="number" name="cooldown_hours" value="{{ old('cooldown_hours', $broadcast->cooldown_hours ?? 24) }}" class="form-input" min="1" required>
                    <p class="text-xs mt-1" style="color: var(--text-soft)">Jeda waktu sebelum mengirim template yang sama ke pelanggan yang sama</p>
                    @error('cooldown_hours')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-start gap-3">
                    <div class="flex-1">
                        <label class="block text-sm font-medium mb-2" style="color: var(--text-dark)">Status</label>
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $broadcast->is_active) ? 'checked' : '' }} class="form-input" style="width:auto; padding:0.5rem;">
                            <span style="color: var(--text-dark)">Aktif</span>
                        </label>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2" style="color: var(--text-dark)">Deskripsi</label>
                <textarea name="description" rows="2" class="form-input">{{ old('description', $broadcast->description) }}</textarea>
                @error('description')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <div class="flex items-center justify-between mb-3">
                    <label class="block text-sm font-medium" style="color: var(--text-dark)">Waktu Pengiriman (Opsional)</label>
                    <button type="button" onclick="addTimeSlot()" class="text-sm btn-secondary px-3 py-1">
                        <i data-lucide="plus" class="w-4 h-4 inline"></i> Tambah Waktu
                    </button>
                </div>
                <div id="timeSlotsContainer" class="grid gap-3">
                    @php
                        $oldTimes = old('send_at_times', $broadcast->send_at_times ?? ['08:00', '12:00', '16:00', '20:00']);
                    @endphp
                    @foreach($oldTimes as $index => $time)
                    <div class="flex items-center gap-3 time-slot">
                        <span class="w-10 text-sm" style="color: var(--text-dark)">Slot {{ $index + 1 }}</span>
                        <input type="time" name="send_at_times[]" value="{{ $time }}" class="form-input flex-1">
                        @if(count($oldTimes) > 0)
                        <button type="button" onclick="removeTimeSlot(this)" class="btn-secondary px-2 py-1 text-sm">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                        @endif
                    </div>
                    @endforeach
                </div>
                <p class="text-xs mt-2" style="color: var(--text-soft)">Kosongkan semua slot waktu untuk menonaktifkan pengiriman otomatis</p>
                @error('send_at_times')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-base" style="color: var(--text-dark)">Template Pesan</h3>
                        <p class="text-sm" style="color: var(--text-soft)">Gunakan placeholder {name}, {product}, {return_date}, {days_left}, {total_rentals}, {greeting}</p>
                    </div>
                    <button type="button" onclick="addTemplate()" class="btn-secondary px-3 py-2">
                        <i data-lucide="plus" class="w-4 h-4 inline"></i> Tambah Template
                    </button>
                </div>
                <div id="templatesContainer" class="space-y-4">
                    @php
                        $oldTemplates = old('message_templates', $broadcast->message_templates ?? [
                            '{greeting} {name}! Jas {product} Anda harus dikembalikan {days_left} hari lagi. Ada pertanyaan? Hubungi kami 😊',
                            '{greeting} {name}! Sudah {total_rentals}x sewa di SewaJas. Terima kasih kepercayaannya! 🙏',
                        ]);
                    @endphp
                    @foreach($oldTemplates as $index => $template)
                    <div class="template-item border rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium" style="color: var(--text-dark)">Template {{ $index + 1 }}</label>
                            @if(count($oldTemplates) > 1)
                            <button type="button" onclick="removeTemplate(this)" class="text-red-500 hover:text-red-700">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                            @endif
                        </div>
                        <textarea name="message_templates[]" rows="3" class="form-input" required>{{ $template }}</textarea>
                    </div>
                    @endforeach
                </div>
                @error('message_templates')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center">
                <a href="{{ route('broadcasts.index') }}" class="btn-secondary inline-flex items-center gap-2">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    Kembali
                </a>

                <div class="flex flex-wrap gap-2">
                    @if($broadcast->exists)
                    <form method="POST" action="{{ route('broadcasts.send-now', $broadcast) }}" class="inline-flex">
                        @csrf
                        <button type="submit" class="btn-secondary inline-flex items-center gap-2">
                            <i data-lucide="send" class="w-4 h-4"></i>
                            Kirim Sekarang
                        </button>
                    </form>
                    @endif

                    <button type="submit" class="btn-primary inline-flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                        {{ $broadcast->exists ? 'Simpan Perubahan' : 'Buat Jadwal' }}
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
let templateCount = {{ count($oldTemplates) }};
let timeSlotCount = {{ count($oldTimes) }};

function addTemplate() {
    templateCount++;
    const container = document.getElementById('templatesContainer');
    const templateHtml = `
        <div class="template-item border rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-medium" style="color: var(--text-dark)">Template ${templateCount}</label>
                <button type="button" onclick="removeTemplate(this)" class="text-red-500 hover:text-red-700">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </div>
            <textarea name="message_templates[]" rows="3" class="form-input" required></textarea>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', templateHtml);
    if (window.lucide) {
        lucide.createIcons();
    }
}

function removeTemplate(button) {
    const container = document.getElementById('templatesContainer');
    const items = container.querySelectorAll('.template-item');
    if (items.length > 1) {
        button.closest('.template-item').remove();
        reorderTemplates();
    } else {
        alert('Minimal harus ada 1 template');
    }
}

function reorderTemplates() {
    const container = document.getElementById('templatesContainer');
    const items = container.querySelectorAll('.template-item');
    items.forEach((item, index) => {
        const label = item.querySelector('label');
        label.textContent = `Template ${index + 1}`;
    });
    templateCount = items.length;
}

function addTimeSlot() {
    timeSlotCount++;
    const container = document.getElementById('timeSlotsContainer');
    const timeHtml = `
        <div class="flex items-center gap-3 time-slot">
            <span class="w-10 text-sm" style="color: var(--text-dark)">Slot ${timeSlotCount}</span>
            <input type="time" name="send_at_times[]" class="form-input flex-1">
            <button type="button" onclick="removeTimeSlot(this)" class="btn-secondary px-2 py-1 text-sm">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
            </button>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', timeHtml);
    if (window.lucide) {
        lucide.createIcons();
    }
}

function removeTimeSlot(button) {
    const container = document.getElementById('timeSlotsContainer');
    button.closest('.time-slot').remove();
    reorderTimeSlots();
}

function reorderTimeSlots() {
    const container = document.getElementById('timeSlotsContainer');
    const items = container.querySelectorAll('.time-slot');
    items.forEach((item, index) => {
        const span = item.querySelector('span');
        span.textContent = `Slot ${index + 1}`;
    });
    timeSlotCount = items.length;
}
</script>
@endsection