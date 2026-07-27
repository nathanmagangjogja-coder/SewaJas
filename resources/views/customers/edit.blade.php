@extends('layouts.app')

@section('title', isset($customer) ? 'Edit Customer' : 'Tambah Customer')
@section('page-title', isset($customer) ? 'Edit Customer' : 'Tambah Customer Baru')
@section('subtitle', isset($customer) ? $customer->name : 'Input data pelanggan baru')

@section('content')
<div class="max-w-5xl mx-auto" x-data="customerFormState()" @submit.capture="isLoading = true">

    <div class="grid lg:grid-cols-3 gap-5 items-start">

        {{-- ============================================================
             FORM UTAMA
             ============================================================ --}}
        <form method="POST" action="{{ isset($customer) ? route('customers.update', $customer) : route('customers.store') }}"
              enctype="multipart/form-data" class="lg:col-span-2 space-y-5">
            @csrf
            @if(isset($customer)) @method('PATCH') @endif

            {{-- Data Pribadi --}}
            <div class="card p-6">
                <h3 class="font-playfair font-semibold text-base mb-5 flex items-center" style="color: var(--text-dark)">
                    <span class="w-8 h-8 rounded-lg flex items-center justify-center mr-2.5" style="background: var(--secondary)">
                        <i data-lucide="user" class="w-4 h-4" style="color: var(--primary)"></i>
                    </span>
                    Data Pribadi
                </h3>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">Nama Lengkap <span class="text-red-400">*</span></label>
                        <input type="text" name="name" x-model="name" @input="checkDuplicate"
                               value="{{ old('name', $customer->name ?? '') }}"
                               class="form-input" placeholder="Nama lengkap customer" required>
                        @error('name')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">Nomor WhatsApp <span class="text-red-400">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-medium" style="color: var(--text-soft)">+62</span>
                            <input type="text" name="phone" x-model="phone"
                                   @input="phone = phone.replace(/\D/g, ''); checkDuplicate()"
                                   value="{{ old('phone', $customer->phone ?? '') }}"
                                   inputmode="numeric" pattern="[0-9]*"
                                   class="form-input pl-12" placeholder="8123456789" required>
                        </div>
                        <p class="text-xs mt-1" style="color: var(--text-soft)">Hanya angka, tanpa spasi atau simbol.</p>
                        @error('phone')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                        <template x-if="duplicate">
                            <div class="mt-3 p-3 rounded-xl flex items-start gap-3"
                                 style="background: var(--surface-amber); border: 1px solid rgba(251,191,36,0.3)">
                                <i data-lucide="alert-triangle" class="w-4 h-4 mt-0.5 flex-shrink-0"
                                   style="color: var(--color-amber)"></i>
                                <div>
                                    <p class="text-sm font-semibold" style="color: var(--color-amber)">Duplikat Terdeteksi</p>
                                    <p class="text-xs mt-0.5" style="color: var(--text-soft)">
                                        Customer dengan nama atau nomor HP ini sudah terdaftar:
                                        <strong x-text="duplicate.name"></strong>
                                        (<span x-text="duplicate.phone"></span>)
                                    </p>
                                    <a :href="duplicate.url" target="_blank"
                                       class="text-xs font-semibold mt-1 inline-flex items-center gap-1"
                                       style="color: var(--color-amber)">
                                        Lihat Data Existing →
                                    </a>
                                </div>
                            </div>
                        </template>
                    </div>

                                    </div>
            </div>

            {{-- Submit --}}
            <div class="flex items-center justify-between">
                <a href="{{ isset($customer) ? route('customers.show', $customer) : route('customers.index') }}" class="btn-secondary">
                    <i data-lucide="x" class="w-4 h-4"></i>
                    Batal
                </a>
                <button type="submit" class="btn-primary px-8"
                        data-no-loading
                        :disabled="isLoading"
                        :class="isLoading ? 'btn-loading' : ''">
                    <template x-if="isLoading">
                        <span class="btn-spinner"></span>
                    </template>
                    <template x-if="!isLoading">
                        <i data-lucide="{{ isset($customer) ? 'save' : 'user-plus' }}" class="w-4 h-4"></i>
                    </template>
                    <span x-text="isLoading ? '\u00A0Memproses...' : '{{ isset($customer) ? 'Simpan Perubahan' : 'Tambah Customer' }}'"></span>
                </button>
            </div>
        </form>

        {{-- ============================================================
             PREVIEW SIDEBAR (additional menu — live preview, sticky)
             ============================================================ --}}
        <div class="lg:col-span-1 lg:sticky lg:top-5 space-y-4">
            <div class="card p-6 text-center">
                <p class="text-xs font-semibold uppercase tracking-wider mb-4" style="color: var(--text-soft)">Preview Profil</p>
                <img :src="'https://ui-avatars.com/api/?name=' + encodeURIComponent(name || 'Customer') + '&background=E8DED1&color=2B2B2B&size=256&bold=true'"
                     class="w-20 h-20 rounded-2xl object-cover mx-auto ring-4 ring-amber-100 mb-3">
                <p class="font-playfair font-bold" style="color: var(--text-dark)" x-text="name || 'Nama Customer'"></p>
                <p class="text-xs mt-1" style="color: var(--text-soft)" x-text="phone ? ('+62 ' + phone) : 'Nomor WhatsApp'"></p>
            </div>
            <div class="card p-5">
                <p class="text-xs font-semibold uppercase tracking-wider mb-2" style="color: var(--text-soft)">Tips</p>
                <ul class="text-xs space-y-2" style="color: var(--text-soft)">
                    <li class="flex gap-2"><i data-lucide="check" class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" style="color: var(--primary)"></i>Pastikan nomor WhatsApp aktif untuk konfirmasi sewa.</li>
                    <li class="flex gap-2"><i data-lucide="check" class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" style="color: var(--primary)"></i>Foto KTP akan diminta saat customer ini membuat penyewaan pertama.</li>
                </ul>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
function customerFormState() {
    return {
        isLoading: false,

        // --- duplicate checker & live preview ---
        name: '{{ old('name', $customer->name ?? '') }}',
        phone: '{{ old('phone', $customer->phone ?? '') }}',
        duplicate: null,
        checking: false,
        timer: null,

        async checkDuplicate() {
            clearTimeout(this.timer);

            if (this.name.length < 3 && this.phone.length < 8) {
                this.duplicate = null;
                return;
            }

            this.timer = setTimeout(async () => {
                this.checking = true;
                const params = new URLSearchParams({
                    name: this.name,
                    phone: this.phone,
                    exclude_id: '{{ $customer->id ?? '' }}'
                });
                try {
                    const res = await fetch('/customers/check-duplicate?' + params.toString());
                    const data = await res.json();
                    this.duplicate = data.found ? data : null;
                } catch (error) {
                    console.error(error);
                    this.duplicate = null;
                } finally {
                    this.checking = false;
                }
            }, 500);
        }
    }
}
</script>
@endpush