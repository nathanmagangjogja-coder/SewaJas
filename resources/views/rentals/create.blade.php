@extends('layouts.app')

@section('title', 'Buat Penyewaan Baru')
@section('page-title', 'Penyewaan Baru')
@section('subtitle', 'Buat transaksi penyewaan jas baru')

@section('content')
@php
    $productsData = $products->map(fn($p) => [
        'id' => $p->id,
        'name' => $p->name,
        'size' => $p->size,
        'color' => $p->color,
        'price' => $p->rental_price,
        'stock' => $p->stock_available,
    ])->values();
@endphp

<div x-data="rentalForm()" class="space-y-4">

    {{-- ===== STEP INDICATOR ===== --}}
    <div class="card p-4">
        <div class="flex items-center justify-between">
            @foreach(['Pilih Customer', 'Pilih Barang', 'Jaminan & Bayar', 'Konfirmasi'] as $i => $step)
            <div class="flex items-center {{ $loop->last ? '' : 'flex-1' }}">
                <div class="flex items-center gap-1.5 sm:gap-2">
                    <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full flex-shrink-0 flex items-center justify-center text-xs sm:text-sm font-semibold transition-all"
                         :class="currentStep > {{ $i + 1 }} ? 'bg-green-500 text-white' : ''"
                         :style="currentStep === {{ $i + 1 }} ? 'background: linear-gradient(135deg, #D6B98C, #C4A478); color: #1E1A16' : currentStep > {{ $i + 1 }} ? '' : 'background: var(--secondary); color: var(--text-soft)'">
                        <template x-if="currentStep > {{ $i + 1 }}">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        </template>
                        <template x-if="currentStep <= {{ $i + 1 }}">
                            <span>{{ $i + 1 }}</span>
                        </template>
                    </div>
                    <span class="hidden sm:block text-[10px] md:text-xs font-medium leading-tight"
                          :style="currentStep === {{ $i + 1 }} ? 'color: var(--primary)' : 'color: var(--text-soft)'">
                        <span class="hidden md:inline">{{ $step }}</span>
                        <span class="md:hidden">{{ ['Customer','Barang','Jaminan','Review'][$i] }}</span>
                    </span>
                </div>
                @if(!$loop->last)
                <div class="flex-1 h-px mx-1.5 sm:mx-3"
                     :style="currentStep > {{ $i + 1 }} ? 'background: #D6B98C' : 'background: var(--border)'"></div>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    <form method="POST" action="{{ route('rentals.store') }}" enctype="multipart/form-data" @submit.prevent="submitForm">
        @csrf

        {{-- ===== STEP 1: CUSTOMER + PILIH PAKET ===== --}}
        <div x-show="currentStep === 1" class="space-y-4">

            {{-- Customer Picker --}}
            <div class="card p-4 sm:p-6">
                <h3 class="font-playfair font-semibold text-base mb-4" style="color: var(--text-dark)">Pilih Customer</h3>

                <div class="relative mb-4">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" style="color: var(--text-soft)"></i>
                    <input type="text" placeholder="Cari nama atau nomor HP customer..."
                           class="form-input pl-10 w-full"
                           x-model="customerSearch"
                           @input.debounce.300="searchCustomers"
                           @keydown.escape="showCustomerResults = false">

                    <div x-show="showCustomerResults && customerResults.length > 0"
                         @click.outside="showCustomerResults = false"
                         class="absolute top-full left-0 right-0 mt-1 rounded-xl shadow-xl border z-20 overflow-hidden"
                         style="background: var(--bg-dropdown); border-color: var(--border);">
                        <template x-for="c in customerResults" :key="c.id">
                            <div @click="selectCustomer(c)"
                                 class="flex items-center gap-3 p-3 cursor-pointer hover:bg-[var(--bg-soft)] transition-colors">
                                <img :src="c.photo || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(c.name) + '&background=E8DED1&color=2B2B2B&size=64'"
                                     class="w-9 h-9 rounded-full object-cover flex-shrink-0">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium truncate" style="color: var(--text-dark)" x-text="c.name"></p>
                                    <p class="text-xs" style="color: var(--text-soft)" x-text="c.phone"></p>
                                </div>
                                <div class="flex-shrink-0">
                                    <template x-if="c.id_photo">
                                        <span class="text-xs px-2 py-0.5 rounded-full" style="background: #F0FDF4; color: #166534;">Foto Jaminan ✓</span>
                                    </template>
                                    <template x-if="!c.id_photo">
                                        <span class="text-xs px-2 py-0.5 rounded-full" style="background: #FFF8F0; color: #B45309;">Belum ada foto jaminan</span>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <template x-if="selectedCustomer">
                    <div class="flex items-center gap-3 sm:gap-4 p-3 sm:p-4 rounded-xl border-2" style="border-color: var(--primary); background: var(--primary-tint);">
                        <img :src="selectedCustomer.photo || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(selectedCustomer.name) + '&background=E8DED1&color=2B2B2B&size=128'"
                             class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl object-cover flex-shrink-0">
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold truncate" style="color: var(--text-dark)" x-text="selectedCustomer.name"></p>
                            <p class="text-sm" style="color: var(--text-soft)" x-text="selectedCustomer.phone"></p>
                        </div>
                        <button type="button" @click="clearCustomer()"
                                class="p-2 rounded-lg hover:bg-gray-100 transition-colors flex-shrink-0" style="color: var(--text-soft)">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                        <input type="hidden" name="customer_id" :value="selectedCustomer.id">
                    </div>
                </template>

                <div class="flex items-center gap-3 mt-3">
                    <div class="h-px flex-1" style="background: var(--border)"></div>
                    <span class="text-xs" style="color: var(--text-soft)">atau</span>
                    <div class="h-px flex-1" style="background: var(--border)"></div>
                </div>
                <div class="mt-3 text-center">
                    <a href="{{ route('customers.create') }}?redirect={{ route('rentals.create') }}" class="btn-secondary text-sm">
                        <i data-lucide="user-plus" class="w-4 h-4"></i>
                        Tambah Customer Baru
                    </a>
                </div>
            </div>

            
            {{-- ===== PILIH PAKET ===== --}}
            <div class="card p-4 sm:p-6">
                <h3 class="font-playfair font-semibold text-base mb-1" style="color: var(--text-dark)">
                    Pilih Paket Sewa
                </h3>
                <p class="text-xs mb-4" style="color: var(--text-soft)">
                    Setiap paket memiliki durasi dan persentase denda keterlambatan masing-masing
                </p>

                {{-- Hidden inputs untuk form submission --}}
                <input type="hidden" name="package_id"       :value="selectedPackageId">
                <input type="hidden" name="duration_days"    :value="activeDurationDays">
                <input type="hidden" name="custom_duration_days" :value="customDurationDays">

                {{-- Card pilih paket --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3 mb-4">
                    @foreach($packages as $pkg)
                    <button type="button"
                            @click="selectPackage({{ $pkg->id }}, {{ $pkg->duration_days }}, {{ $pkg->is_custom ? 'true' : 'false' }}, '{{ addslashes($pkg->name) }}', {{ $pkg->penalty_percent }})"
                            :class="selectedPackageId === {{ $pkg->id }}
                                ? 'border-amber-400 ring-2 ring-amber-300'
                                : 'border-transparent hover:border-amber-200'"
                            class="relative flex flex-col items-center justify-center p-3 rounded-xl border-2 text-center transition-all"
                            style="background: var(--bg-main)">

                        {{-- Ikon per tipe paket --}}
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center mb-2 transition-all"
                             :style="selectedPackageId === {{ $pkg->id }}
                                ? 'background: linear-gradient(135deg, #D6B98C, #C4A478)'
                                : 'background: var(--secondary)'">
                            @if($pkg->is_custom)
                                <i data-lucide="settings-2" class="w-4 h-4"
                                   :style="selectedPackageId === {{ $pkg->id }} ? 'color:#1E1A16' : 'color:var(--primary)'"></i>
                            @elseif($pkg->duration_days === 1)
                                <i data-lucide="sun" class="w-4 h-4"
                                   :style="selectedPackageId === {{ $pkg->id }} ? 'color:#1E1A16' : 'color:var(--primary)'"></i>
                            @elseif($pkg->duration_days <= 3)
                                <i data-lucide="calendar" class="w-4 h-4"
                                   :style="selectedPackageId === {{ $pkg->id }} ? 'color:#1E1A16' : 'color:var(--primary)'"></i>
                            @else
                                <i data-lucide="calendar-range" class="w-4 h-4"
                                   :style="selectedPackageId === {{ $pkg->id }} ? 'color:#1E1A16' : 'color:var(--primary)'"></i>
                            @endif
                        </div>

                        <p class="text-xs font-bold leading-tight" style="color: var(--text-dark)">
                            {{ $pkg->name }}
                        </p>
                        <p class="text-[10px] mt-0.5" style="color: var(--text-soft)">
                            {{ $pkg->is_custom ? 'Durasi bebas' : $pkg->duration_days . ' hari' }}
                        </p>
                        <p class="text-[10px] font-semibold mt-1" style="color: #D97706">
                            denda {{ number_format($pkg->penalty_percent, 0) }}%/hari
                        </p>

                        {{-- Checkmark terpilih --}}
                        <div x-show="selectedPackageId === {{ $pkg->id }}"
                             class="absolute top-1.5 right-1.5 w-4 h-4 rounded-full flex items-center justify-center"
                             style="background: #10B981">
                            <i data-lucide="check" class="w-2.5 h-2.5 text-white"></i>
                        </div>
                    </button>
                    @endforeach
                </div>

                {{-- Custom duration input — hanya muncul saat paket Custom --}}
                <div x-show="isCustomPackage" x-transition class="mb-4">
                    <label class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">
                        Durasi Sewa (hari) <span class="text-red-400">*</span>
                    </label>
                    <div class="flex items-center gap-3">
                        <input type="number" x-model.number="customDurationDays"
                               class="form-input w-28 text-center text-lg font-bold"
                               min="1" max="365"
                               :required="isCustomPackage">
                        <span class="text-sm" style="color: var(--text-soft)">hari</span>
                    </div>
                </div>

                {{-- Tanggal sewa & jatuh tempo --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">
                            Tanggal Mulai <span class="text-red-400">*</span>
                        </label>
                        <input type="date" name="rental_date" x-model="rentalDate"
                               :min="today" class="form-input w-full" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5" style="color: var(--text-soft)">
                            Tanggal Jatuh Tempo
                        </label>
                        <div class="form-input flex items-center gap-2" style="background: var(--secondary); cursor: default;">
                            <i data-lucide="calendar" class="w-4 h-4 flex-shrink-0" style="color: var(--primary)"></i>
                            <span class="text-sm font-medium truncate" style="color: var(--text-dark)"
                                  x-text="returnDate"></span>
                        </div>
                    </div>
                </div>

                {{-- Info paket terpilih --}}
                <div class="mt-3 p-3 rounded-xl flex items-start gap-2"
                     style="background: #FFFDF0; border: 1px solid #F6E4B0">
                    <i data-lucide="info" class="w-4 h-4 flex-shrink-0 mt-0.5" style="color: #B45309"></i>
                    <p class="text-xs leading-relaxed" style="color: #92400E">
                        Paket terpilih: <strong x-text="selectedPackageName"></strong>.
                        Durasi: <strong x-text="isCustomPackage ? customDurationDays + ' hari (custom)' : activeDurationDays + ' hari'"></strong>.
                        Denda keterlambatan: <strong x-text="selectedPenaltyPct + '% dari subtotal per hari'"></strong>.
                    </p>
                </div>
            </div>

            <div class="flex justify-end sticky bottom-0 z-20 -mx-4 lg:-mx-6 px-4 lg:px-6 py-3 border-t"
                 style="background: var(--bg-main); border-color: var(--border);">
                <button type="button" @click="nextStep"
                        :disabled="!selectedCustomer || !rentalDate || !selectedPackageId"
                        class="btn-primary w-full sm:w-auto justify-center"
                        :class="(!selectedCustomer || !rentalDate || !selectedPackageId) ? 'opacity-50 cursor-not-allowed' : ''">
                    Lanjut Pilih Barang
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        {{-- ===== STEP 2: PRODUCTS ===== --}}
        <div x-show="currentStep === 2" class="space-y-4">
            <div class="card p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <h3 class="font-playfair font-semibold text-base" style="color: var(--text-dark)">Pilih Barang</h3>
                    <div class="overflow-x-auto -mx-4 px-4 sm:mx-0 sm:px-0">
                        <div class="flex gap-2 w-max sm:w-auto flex-wrap sm:flex-nowrap">
                            <button type="button" @click="filterCategory = null"
                                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all whitespace-nowrap flex-shrink-0"
                                    :style="!filterCategory ? 'background: var(--primary); color: #1E1A16' : 'background: var(--secondary); color: var(--text-soft)'">
                                Semua
                            </button>
                            @foreach($categories as $cat)
                            <button type="button" @click="filterCategory = {{ $cat->id }}"
                                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all whitespace-nowrap flex-shrink-0"
                                    :style="filterCategory === {{ $cat->id }} ? 'background: var(--primary); color: #1E1A16' : 'background: var(--secondary); color: var(--text-soft)'">
                                {{ $cat->name }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="relative mb-4">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" style="color: var(--text-soft)"></i>
                    <input type="text" placeholder="Cari nama, kode, ukuran..."
                           class="form-input pl-10 w-full" x-model="productSearch">
                </div>

                                @error('items')<p class="text-xs text-red-400 mb-3">{{ $message }}</p>@enderror
                @error('items.*.product_id')<p class="text-xs text-red-400 mb-3">{{ $message }}</p>@enderror
                @error('items.*.quantity')<p class="text-xs text-red-400 mb-3">{{ $message }}</p>@enderror

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2 sm:gap-3 max-h-96 overflow-y-auto pr-1">
                    @foreach($products as $product)
                    <div x-show="(!filterCategory || filterCategory === {{ $product->category_id }}) &&
                                 (!productSearch || '{{ strtolower($product->name . ' ' . $product->code . ' ' . $product->size) }}'.includes(productSearch.toLowerCase()))"
                         @click="toggleProduct({{ $product->id }}, '{{ addslashes($product->name) }}', '{{ $product->size }}', '{{ $product->color }}', {{ $product->rental_price }}, {{ $product->stock_available }})"
                         class="relative p-2 sm:p-3 rounded-xl border-2 cursor-pointer transition-all select-none"
                         :class="isSelected({{ $product->id }}) ? 'border-amber-400' : 'border-transparent'"
                         :style="isSelected({{ $product->id }}) ? 'background: var(--primary-tint);' : 'background: var(--bg-main);'">

                        <div class="absolute top-2 right-2 w-4 h-4 sm:w-5 sm:h-5 rounded-full border-2 flex items-center justify-center transition-all"
                             :class="isSelected({{ $product->id }}) ? '' : 'border-gray-300'"
                             :style="isSelected({{ $product->id }}) ? 'background: linear-gradient(135deg, #D6B98C, #C4A478); border-color: #D6B98C' : ''">
                            <i data-lucide="check" class="w-2.5 h-2.5 text-white" x-show="isSelected({{ $product->id }})"></i>
                        </div>

                        @if($product->photo)
                        <img src="{{ asset('storage/' . $product->photo) }}" alt="{{ $product->name }}"
                             class="w-full h-20 sm:h-28 object-cover rounded-lg mb-2">
                        @else
                        <div class="w-full h-20 sm:h-28 rounded-lg mb-2 flex items-center justify-center" style="background: var(--secondary)">
                            <i data-lucide="shirt" class="w-8 h-8 sm:w-10 sm:h-10" style="color: var(--primary)"></i>
                        </div>
                        @endif

                        <p class="text-xs font-semibold truncate" style="color: var(--text-dark)">{{ $product->name }}</p>
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-[10px] sm:text-xs truncate" style="color: var(--text-soft)">{{ $product->size }}</span>
                            <span class="text-[10px] sm:text-xs flex-shrink-0" style="color: #10B981">{{ $product->stock_available }} stok</span>
                        </div>
                        <p class="text-[10px] sm:text-xs font-bold mt-1" style="color: var(--primary)">
                            Rp {{ number_format($product->rental_price, 0, ',', '.') }}/paket
                        </p>
                    </div>
                    @endforeach
                </div>
            </div>

            <template x-if="selectedItems.length > 0">
                <div class="card p-4">
                    <h4 class="font-semibold text-sm mb-3" style="color: var(--text-dark)">
                        Barang Dipilih (<span x-text="selectedItems.length"></span>)
                    </h4>
                    <div class="space-y-2">
                        <template x-for="item in selectedItems" :key="item.id">
                            <div class="flex items-center gap-2 sm:gap-3 p-2.5 rounded-lg" style="background: var(--bg-main)">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium truncate" style="color: var(--text-dark)" x-text="item.name"></p>
                                    <p class="text-xs" style="color: var(--text-soft)"
                                       x-text="item.size + (item.color ? ' · ' + item.color : '') + ' · ' + selectedPackageName"></p>
                                </div>
                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                    <button type="button" @click="decreaseQty(item.id)"
                                            class="w-6 h-6 rounded-full flex items-center justify-center transition-colors hover:bg-gray-200"
                                            style="background: var(--secondary)">
                                        <i data-lucide="minus" class="w-3 h-3" style="color: var(--text-dark)"></i>
                                    </button>
                                    <span class="w-5 text-center text-sm font-bold" style="color: var(--text-dark)" x-text="item.qty"></span>
                                    <button type="button" @click="increaseQty(item.id)"
                                            class="w-6 h-6 rounded-full flex items-center justify-center transition-colors hover:bg-gray-200"
                                            style="background: var(--secondary)">
                                        <i data-lucide="plus" class="w-3 h-3" style="color: var(--text-dark)"></i>
                                    </button>
                                </div>
                                <p class="text-sm font-semibold hidden sm:block w-24 text-right flex-shrink-0"
                                   style="color: var(--text-dark)"
                                   x-text="'Rp ' + formatNumber(item.price * item.qty)"></p>
                                <button type="button" @click="removeItem(item.id)"
                                        class="p-1 rounded hover:bg-red-50 transition-colors flex-shrink-0"
                                        style="color: #E74C3C">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        </template>
                    </div>

                    <div class="mt-3 pt-3 border-t flex justify-between items-center" style="border-color: var(--border)">
                        <span class="text-sm font-semibold" style="color: var(--text-soft)">Subtotal</span>
                        <span class="text-lg font-bold" style="color: var(--text-dark)" x-text="'Rp ' + formatNumber(subtotal)"></span>
                    </div>

                    <template x-for="(item, idx) in selectedItems" :key="'hidden-' + item.id">
                        <div>
                            <input type="hidden" :name="'items[' + idx + '][product_id]'" :value="item.id">
                            <input type="hidden" :name="'items[' + idx + '][quantity]'"   :value="item.qty">
                        </div>
                    </template>
                </div>
            </template>

            <div class="flex justify-between gap-2 sticky bottom-0 z-20 -mx-4 lg:-mx-6 px-4 lg:px-6 py-3 border-t"
                 style="background: var(--bg-main); border-color: var(--border);">
                <button type="button" @click="prevStep" class="btn-secondary flex-1 sm:flex-none justify-center">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span>Kembali</span>
                </button>
                <button type="button" @click="nextStep" :disabled="selectedItems.length === 0"
                        class="btn-primary flex-1 sm:flex-none justify-center"
                        :class="selectedItems.length === 0 ? 'opacity-50 cursor-not-allowed' : ''">
                    <span class="hidden sm:inline">Lanjut ke Jaminan</span>
                    <span class="sm:hidden">Lanjut</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        {{-- ===== STEP 3: GUARANTEE ===== --}}
        <div x-show="currentStep === 3" class="space-y-4">
            <div class="card p-4 sm:p-6">
                <h3 class="font-playfair font-semibold text-base mb-4" style="color: var(--text-dark)">Data Jaminan</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">
                            Jenis Jaminan <span class="text-red-400">*</span>
                        </label>
                        <select name="guarantee[type]" x-model="guaranteeType" class="form-input w-full" required>
                            <option value="">Pilih Jaminan</option>
                            <option value="ktp">KTP</option>
                            <option value="sim">SIM</option>
                            <option value="deposit">Deposit Uang</option>
                            <option value="custom">Jaminan Custom</option>
                        </select>
                                                @error('guarantee.type')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <template x-if="guaranteeType === 'deposit'">
                        <div>
                            <label class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">Nominal Deposit</label>
                            <input type="number" name="guarantee[deposit_amount]" class="form-input w-full" placeholder="0">
                        </div>
                    </template>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">
                            Nama pada Identitas
                            <span class="ml-1.5 text-xs font-normal px-1.5 py-0.5 rounded" style="background: var(--secondary); color: var(--text-soft);">Otomatis</span>
                        </label>
                        <div class="relative">
                            <input type="text" name="guarantee[id_name]" :value="selectedCustomer?.name || ''"
                                   class="form-input pr-10 w-full" readonly
                                   style="background: var(--secondary); color: var(--text-dark); cursor: default;">
                            <i data-lucide="lock" class="absolute right-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5" style="color: var(--text-soft);"></i>
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">Catatan</label>
                        <input type="text" name="guarantee[description]" class="form-input w-full" placeholder="Keterangan tambahan">
                    </div>
                </div>
            </div>

                        <template x-if="selectedCustomer">
                <div class="card p-4 sm:p-6">
                    <h3 class="font-playfair font-semibold text-base mb-1 flex items-center gap-2" style="color: var(--text-dark)">
                        <i data-lucide="camera" class="w-4 h-4" style="color: var(--primary)"></i>
                        <span x-text="guaranteePhotoTitle"></span>
                    </h3>
                    <p class="text-xs mb-4" style="color: var(--text-soft)">
                        <template x-if="selectedCustomer.id_photo && !idPhotoPreview && guaranteePhotoRequired">
                            <span><span x-text="guaranteePhotoLabel"></span> <strong>wajib</strong> ada untuk jenis jaminan ini. Customer ini sudah memiliki foto tersimpan — klik foto untuk memeriksa detail (zoom/geser). Ganti fotonya kalau sudah tidak jelas atau kadaluarsa.</span>
                        </template>
                        <template x-if="!selectedCustomer.id_photo && !idPhotoPreview && guaranteePhotoRequired && !selectedCustomer.rental_count">
                            <span>Customer baru — belum pernah menyewa sebelumnya. <span x-text="guaranteePhotoLabel"></span> <strong>wajib</strong> diunggah untuk transaksi pertama ini.</span>
                        </template>
                        <template x-if="!selectedCustomer.id_photo && !idPhotoPreview && guaranteePhotoRequired && selectedCustomer.rental_count > 0">
                            <span>Customer ini sudah pernah bertransaksi, tapi belum punya foto jaminan tersimpan. <span x-text="guaranteePhotoLabel"></span> <strong>wajib</strong> diunggah sekarang sebelum transaksi ini bisa disimpan.</span>
                        </template>
                        <template x-if="selectedCustomer.id_photo && !idPhotoPreview && !guaranteePhotoRequired">
                            <span>Customer ini sudah punya foto jaminan tersimpan. Klik foto untuk melihat detail (zoom/geser), atau ganti dengan foto baru bila perlu.</span>
                        </template>
                        <template x-if="!selectedCustomer.id_photo && !idPhotoPreview && !guaranteePhotoRequired">
                            <span>Jenis jaminan ini tidak mewajibkan foto KTP/SIM, tapi tetap disarankan mengunggah bukti serah-terima (mis. foto barang jaminan atau bukti deposit).</span>
                        </template>
                        <template x-if="idPhotoPreview">
                            <span>Foto jaminan baru siap diunggah.</span>
                        </template>
                    </p>

                    <div class="grid gap-4">
                        {{-- Foto Jaminan --}}
                        <div>
                            <label class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">
                                <span x-text="guaranteePhotoLabel"></span>
                                <span class="text-red-400" x-show="guaranteePhotoRequired">*</span>
                                <span class="text-xs font-normal" style="color: var(--text-soft)" x-show="!guaranteePhotoRequired">(opsional)</span>
                            </label>

                            <template x-if="!idPhotoPreview && !selectedCustomer.id_photo">
                                <div class="border-2 border-dashed rounded-xl p-6 text-center transition-all"
                                     style="border-color: var(--border)">
                                    <i data-lucide="credit-card" class="w-7 h-7 mx-auto mb-2" style="color: var(--primary)"></i>
                                    <p class="text-sm mb-3" style="color: var(--text-soft)" x-text="'Upload ' + guaranteePhotoLabel.toLowerCase()"></p>
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="button" @click="$refs.rentalIdPhotoInput.click()"
                                                class="btn-secondary text-xs px-3 py-1.5">
                                            <i data-lucide="image" class="w-3.5 h-3.5"></i> Galeri
                                        </button>
                                        <button type="button" @click="$refs.rentalIdPhotoCameraInput.click()"
                                                class="btn-secondary text-xs px-3 py-1.5">
                                            <i data-lucide="camera" class="w-3.5 h-3.5"></i> Kamera
                                        </button>
                                    </div>
                                    <p class="text-xs mt-2" style="color: var(--border)">JPG/PNG/WEBP — otomatis dikompres</p>
                                </div>
                            </template>

                            <template x-if="idPhotoPreview || selectedCustomer.id_photo">
                                <div>
                                    {{-- Kotak foto — perbesar/perkecil & geser LANGSUNG di dalam kotak ini --}}
                                    <div class="relative w-full h-48 rounded-xl overflow-hidden select-none"
                                         style="background: var(--secondary)"
                                         @wheel.prevent="zoomKtp($event.deltaY < 0 ? 0.15 : -0.15)"
                                         @mousedown="ktpZoom.dragging = true; ktpZoom.startX = $event.clientX; ktpZoom.startY = $event.clientY"
                                         @mousemove="if (ktpZoom.dragging) { ktpZoom.panX += ($event.clientX - ktpZoom.startX); ktpZoom.panY += ($event.clientY - ktpZoom.startY); ktpZoom.startX = $event.clientX; ktpZoom.startY = $event.clientY; }"
                                         @mouseup="ktpZoom.dragging = false"
                                         @mouseleave="ktpZoom.dragging = false"
                                         @touchstart="ktpZoom.dragging = true; ktpZoom.startX = $event.touches[0].clientX; ktpZoom.startY = $event.touches[0].clientY"
                                         @touchmove.prevent="if (ktpZoom.dragging) { ktpZoom.panX += ($event.touches[0].clientX - ktpZoom.startX); ktpZoom.panY += ($event.touches[0].clientY - ktpZoom.startY); ktpZoom.startX = $event.touches[0].clientX; ktpZoom.startY = $event.touches[0].clientY; }"
                                         @touchend="ktpZoom.dragging = false">

                                        <img :src="idPhotoPreview || selectedCustomer.id_photo"
                                             class="w-full h-full object-contain transition-transform"
                                             :class="idPhotoProcessing ? 'opacity-50' : (ktpZoom.dragging ? 'cursor-grabbing' : 'cursor-grab')"
                                             :style="'transform: translate(' + ktpZoom.panX + 'px,' + ktpZoom.panY + 'px) scale(' + ktpZoom.scale + '); transform-origin: center;'"
                                             draggable="false">

                                        <template x-if="idPhotoProcessing">
                                            <div class="absolute inset-0 flex items-center justify-center">
                                                <i data-lucide="loader-2" class="w-6 h-6 animate-spin text-white"></i>
                                            </div>
                                        </template>

                                        {{-- Kontrol perbesar/perkecil — bekerja langsung di kotak, tidak buka gambar mengambang --}}
                                        <div class="absolute bottom-2 left-2 flex items-center gap-0.5 px-1 py-1 rounded-lg"
                                             style="background: rgba(0,0,0,0.6)"
                                             @mousedown.stop @touchstart.stop>
                                            <button type="button" @click.stop="zoomKtp(-0.25)"
                                                    class="w-6 h-6 rounded-full flex items-center justify-center hover:bg-white/20">
                                                <i data-lucide="zoom-out" class="w-3.5 h-3.5 text-white"></i>
                                            </button>
                                            <span class="text-[10px] text-white w-9 text-center" x-text="Math.round(ktpZoom.scale * 100) + '%'"></span>
                                            <button type="button" @click.stop="zoomKtp(0.25)"
                                                    class="w-6 h-6 rounded-full flex items-center justify-center hover:bg-white/20">
                                                <i data-lucide="zoom-in" class="w-3.5 h-3.5 text-white"></i>
                                            </button>
                                            <button type="button" @click.stop="resetKtpZoom()"
                                                    class="w-6 h-6 rounded-full flex items-center justify-center hover:bg-white/20" title="Reset">
                                                <i data-lucide="maximize" class="w-3.5 h-3.5 text-white"></i>
                                            </button>
                                        </div>

                                        <div class="absolute top-2 right-2 flex items-center gap-1.5"
                                             @mousedown.stop @touchstart.stop>
                                            {{-- Putar (hanya untuk foto baru yang belum di-submit) --}}
                                            <template x-if="idPhotoPreview">
                                                <button type="button" @click.stop="rotateIdPhoto(90)" :disabled="idPhotoProcessing"
                                                        class="w-7 h-7 rounded-full flex items-center justify-center"
                                                        style="background: rgba(0,0,0,0.5)" title="Putar 90°">
                                                    <i data-lucide="rotate-cw" class="w-3.5 h-3.5 text-white"></i>
                                                </button>
                                            </template>
                                            <button type="button" @click.stop="$refs.rentalIdPhotoInput.click()" :disabled="idPhotoProcessing"
                                                    class="w-7 h-7 rounded-full flex items-center justify-center"
                                                    style="background: rgba(0,0,0,0.5)" title="Ganti foto">
                                                <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-white"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <p class="text-[11px] mt-1 text-center" style="color: var(--text-soft)">Scroll / cubit untuk zoom, geser untuk memindahkan gambar</p>
                                </div>
                                <div class="flex items-center gap-2 mt-2" x-show="idPhotoPreview">
                                    <button type="button" @click="$refs.rentalIdPhotoInput.click()" class="text-xs" style="color: var(--primary)">
                                        <i data-lucide="image" class="w-3 h-3 inline"></i> Galeri
                                    </button>
                                    <span style="color: var(--border)">•</span>
                                    <button type="button" @click="$refs.rentalIdPhotoCameraInput.click()" class="text-xs" style="color: var(--primary)">
                                        <i data-lucide="camera" class="w-3 h-3 inline"></i> Kamera
                                    </button>
                                </div>
                            </template>

                            {{-- Input galeri --}}
                            <input type="file" x-ref="rentalIdPhotoInput" name="id_photo" accept="image/jpeg,image/png,image/webp" class="hidden"
                                   @change="previewIdPhoto($event)">
                            {{-- Input kamera (mobile) — hasilnya disalin ke input di atas, tidak dikirim langsung --}}
                            <input type="file" x-ref="rentalIdPhotoCameraInput" accept="image/*" capture="environment" class="hidden"
                                   @change="previewIdPhoto($event)">

                                                        <template x-if="ktpNeedsReupload && !idPhotoPreview">
                                <p class="text-xs mt-1 font-medium" style="color: #B45309">
                                    <span x-text="'⚠ Percobaan sebelumnya gagal tersimpan. Foto tidak bisa dipulihkan otomatis oleh browser — silakan pilih ulang ' + guaranteePhotoLabel.toLowerCase() + '.'"></span>
                                </p>
                            </template>
                            <p class="text-xs text-red-400 mt-1" x-show="idPhotoError" x-text="idPhotoError"></p>
                            @error('id_photo')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                        </div>

                                                @error('customer_id')
                        <div>
                            <p class="text-xs text-red-400">{{ $message }}</p>
                        </div>
                        @enderror

                                                <div>
                            <label class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">Catatan Internal Customer (opsional)</label>
                            <textarea name="customer_notes" x-model="customerNotes" rows="2" class="form-input w-full"
                                      placeholder="Catatan tambahan tentang customer ini..."></textarea>
                        </div>
                    </div>
                </div>
            </template>

            <div class="card p-4 sm:p-6">
                <h3 class="font-playfair font-semibold text-base mb-4" style="color: var(--text-dark)">Diskon & Catatan</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">Diskon (Rp)</label>
                        <input type="number" name="discount" x-model="discount" min="0" class="form-input w-full" placeholder="0">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">Catatan</label>
                        <input type="text" name="notes" class="form-input w-full" placeholder="Catatan tambahan...">
                    </div>
                </div>

                <div class="mt-4 p-4 rounded-xl" style="background: var(--bg-main)">
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span style="color: var(--text-soft)">Subtotal</span>
                            <span style="color: var(--text-dark)" x-text="'Rp ' + formatNumber(subtotal)"></span>
                        </div>
                        <div class="flex justify-between text-sm" x-show="discount > 0">
                            <span style="color: var(--text-soft)">Diskon</span>
                            <span style="color: #E74C3C" x-text="'-Rp ' + formatNumber(discount)"></span>
                        </div>
                        <div class="pt-2 border-t flex justify-between" style="border-color: var(--border)">
                            <span class="font-bold" style="color: var(--text-dark)">Total</span>
                            <span class="text-xl font-bold" style="color: var(--primary)" x-text="'Rp ' + formatNumber(total)"></span>
                        </div>
                    </div>
                </div>

                {{-- Info denda --}}
                <div class="mt-3 p-3 rounded-xl flex items-start gap-2"
                     style="background: #FFFBEB; border: 1px solid #FDE68A">
                    <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0 mt-0.5" style="color: #D97706"></i>
                    <p class="text-xs leading-relaxed" style="color: #92400E">
                        Denda keterlambatan: <strong x-text="selectedPenaltyPct + '% dari subtotal per hari'"></strong>.
                        Contoh: terlambat 3 hari → denda
                        <strong x-text="'Rp ' + formatNumber(subtotal * selectedPenaltyPct / 100 * 3)"></strong>.
                    </p>
                </div>
            </div>

            <div class="flex justify-between gap-2 sticky bottom-0 z-20 -mx-4 lg:-mx-6 px-4 lg:px-6 py-3 border-t"
                 style="background: var(--bg-main); border-color: var(--border);">
                <button type="button" @click="prevStep" class="btn-secondary flex-1 sm:flex-none justify-center">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span>Kembali</span>
                </button>
                <button type="button" @click="nextStep" :disabled="!guaranteeType || !hasValidIdVerification"
                        class="btn-primary flex-1 sm:flex-none justify-center" :class="(!guaranteeType || !hasValidIdVerification) ? 'opacity-50 cursor-not-allowed' : ''">
                    <span class="hidden sm:inline">Review & Konfirmasi</span>
                    <span class="sm:hidden">Review</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        {{-- ===== STEP 4: CONFIRMATION ===== --}}
        <div x-show="currentStep === 4" class="space-y-4">
            <div class="card p-4 sm:p-6">
                <h3 class="font-playfair font-semibold text-base mb-4" style="color: var(--text-dark)">Konfirmasi Penyewaan</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 sm:gap-6">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider mb-2" style="color: var(--text-soft)">Customer</p>
                        <template x-if="selectedCustomer">
                            <div class="flex items-center gap-3">
                                <img :src="selectedCustomer.photo || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(selectedCustomer.name) + '&background=E8DED1&color=2B2B2B&size=64'"
                                     class="w-10 h-10 rounded-full flex-shrink-0">
                                <div class="min-w-0">
                                    <p class="font-semibold truncate" style="color: var(--text-dark)" x-text="selectedCustomer.name"></p>
                                    <p class="text-sm" style="color: var(--text-soft)" x-text="selectedCustomer.phone"></p>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider mb-2" style="color: var(--text-soft)">Jadwal & Paket</p>
                        <p class="text-sm font-medium" style="color: var(--text-dark)">
                            <span x-text="rentalDate"></span>
                            <span class="mx-1" style="color: var(--text-soft)">→</span>
                            <span x-text="returnDate"></span>
                        </p>
                        <div class="mt-1 flex items-center gap-2 flex-wrap">
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                  style="background: linear-gradient(135deg, #D6B98C22, #C4A47822); color: var(--primary); border: 1px solid #D6B98C55"
                                  x-text="selectedPackageName"></span>
                            <span class="text-xs" style="color: var(--text-soft)"
                                  x-text="activeDurationDays + ' hari · denda ' + selectedPenaltyPct + '%/hari'"></span>
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wider mb-2" style="color: var(--text-soft)">Barang Disewa</p>
                        <div class="space-y-1.5">
                            <template x-for="item in selectedItems" :key="'conf-' + item.id">
                                <div class="flex items-start sm:items-center justify-between gap-2 p-2.5 rounded-lg" style="background: var(--bg-main)">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium truncate" style="color: var(--text-dark)"
                                           x-text="item.name + ' (' + item.size + ')'"></p>
                                        <p class="text-xs" style="color: var(--text-soft)"
                                           x-text="item.qty + ' × Rp ' + formatNumber(item.price) + ' (' + selectedPackageName + ')'"></p>
                                    </div>
                                    <p class="font-semibold text-sm flex-shrink-0" style="color: var(--text-dark)"
                                       x-text="'Rp ' + formatNumber(item.price * item.qty)"></p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="sm:col-span-2 pt-4 border-t" style="border-color: var(--border)">
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-lg" style="color: var(--text-dark)">Total Bayar</span>
                            <span class="text-xl sm:text-2xl font-bold font-playfair" style="color: var(--primary)"
                                  x-text="'Rp ' + formatNumber(total)"></span>
                        </div>
                        <p class="text-xs mt-1 text-right" style="color: var(--text-soft)">
                            Belum termasuk denda jika terlambat dikembalikan
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex justify-between gap-2 sticky bottom-0 z-20 -mx-4 lg:-mx-6 px-4 lg:px-6 py-3 border-t"
                 style="background: var(--bg-main); border-color: var(--border);">
                <button type="button" @click="prevStep" class="btn-secondary flex-1 sm:flex-none justify-center" :disabled="isSubmitting">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span>Kembali</span>
                </button>
                <button type="submit" class="btn-primary flex-1 sm:flex-none justify-center sm:px-8"
                        :class="isSubmitting ? 'opacity-75 cursor-not-allowed' : ''"
                        :disabled="isSubmitting">
                    <template x-if="isSubmitting">
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                    </template>
                    <template x-if="!isSubmitting">
                        <i data-lucide="check" class="w-4 h-4"></i>
                    </template>
                    <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan Penyewaan'"></span>
                </button>
            </div>
        </div>

    </form>
</div>
@endsection

@push('scripts')
<script>
function rentalForm() {
    return {
        currentStep: 1,

        // ── Customer ──────────────────────────────────────────────────
        selectedCustomer:    null,
        customerSearch:      '',
        idPhotoPreview:    null,
        idPhotoBlob:       null,
        idPhotoError:      '',
        idPhotoProcessing: false,
        idNumber:       '',
        customerNotes:  '',
        ktpZoom: { scale: 1, panX: 0, panY: 0, dragging: false, startX: 0, startY: 0 },
        customerResults:     [],
        showCustomerResults: false,

        customersData: @json($customers),
        productsData:  @json($productsData),
        ktpNeedsReupload: false,

        // ── Paket (diambil dari PHP) ───────────────────────────────────
        packages:            @json($packages),
        selectedPackageId:   {{ $defaultPkg?->id ?? 'null' }},
        selectedPackageName: '{{ addslashes($defaultPkg?->name ?? '') }}',
        selectedPenaltyPct:  {{ $defaultPkg?->penalty_percent ?? 10 }},
        isCustomPackage:     {{ $defaultPkg?->is_custom ? 'true' : 'false' }},
        customDurationDays:  3,

        // ── Tanggal ───────────────────────────────────────────────────
        rentalDate: new Date().toISOString().split('T')[0],
        today:      new Date().toISOString().split('T')[0],

        // ── Produk ────────────────────────────────────────────────────
        filterCategory: null,
        productSearch:  '',
        selectedItems:  [],

        // ── Lain-lain ────────────────────────────────────────────────
        guaranteeType: '',
        discount:      0,
        isSubmitting:  false,

        init() {
            this.$watch('selectedItems', () => {
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            });
            this.restoreOldInput();
        },

        restoreOldInput() {
            @if ($errors->any())
                @if (old('customer_id'))
                    const oldCustomer = this.customersData.find(c => c.id === {{ (int) old('customer_id') }});
                    if (oldCustomer) {
                        this.selectedCustomer = oldCustomer;
                        this.customerSearch   = oldCustomer.name;
                    }
                @endif
                this.idNumber       = {!! \Illuminate\Support\Js::from(old('id_number', '')) !!};
                this.customerNotes  = {!! \Illuminate\Support\Js::from(old('customer_notes', '')) !!};
                this.guaranteeType  = {!! \Illuminate\Support\Js::from(old('guarantee.type', '')) !!};
                this.discount       = {!! \Illuminate\Support\Js::from(old('discount', 0)) !!};
                @if (old('rental_date'))
                    this.rentalDate = {!! \Illuminate\Support\Js::from(old('rental_date')) !!};
                @endif

                @if (old('package_id'))
                    this.selectedPackageId = {{ (int) old('package_id') }};
                    const oldPkg = this.packages.find(p => p.id === this.selectedPackageId);
                    if (oldPkg) {
                        this.selectedPackageName = oldPkg.name;
                        this.selectedPenaltyPct  = oldPkg.penalty_percent;
                        this.isCustomPackage     = !!oldPkg.is_custom;
                    }
                @endif
                @if (old('custom_duration_days'))
                    this.customDurationDays = {{ (int) old('custom_duration_days') }};
                @endif

                @if (old('items'))
                    const oldItems = {!! \Illuminate\Support\Js::from(old('items')) !!};
                    this.selectedItems = oldItems
                        .map(oi => {
                            const p = this.productsData.find(pp => pp.id == oi.product_id);
                            return p ? { id: p.id, name: p.name, size: p.size, color: p.color, price: p.price, qty: parseInt(oi.quantity) || 1, stock: p.stock } : null;
                        })
                        .filter(Boolean);
                @endif

                // Foto KTP tidak pernah bisa dipulihkan lewat redirect (batasan browser),
                // jadi kalau errornya soal KTP, kasih tahu user secara eksplisit.
                @if ($errors->has('id_photo') || $errors->has('id_number'))
                    this.currentStep      = 3;
                    this.ktpNeedsReupload = true;
                @elseif ($errors->has('customer_id'))
                    this.currentStep = 1;
                @elseif ($errors->has('items') || $errors->has('items.*.product_id') || $errors->has('items.*.quantity'))
                    this.currentStep = 2;
                @elseif ($errors->has('guarantee.type') || $errors->has('rental_date'))
                    this.currentStep = 3;
                @endif
            @endif
        },

        // ── Durasi aktif: paket normal ambil duration_days, custom ambil input user ──
        get activeDurationDays() {
            if (this.isCustomPackage) return parseInt(this.customDurationDays) || 1;
            const pkg = this.packages.find(p => p.id === this.selectedPackageId);
            return pkg ? pkg.duration_days : 3;
        },

        // ── Tanggal jatuh tempo ───────────────────────────────────────
        get returnDate() {
            if (!this.rentalDate) return '-';
            const d = new Date(this.rentalDate);
            d.setDate(d.getDate() + this.activeDurationDays);
            return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
        },

        // ── Subtotal: harga × qty (tidak dikali hari) ─────────────────
        get subtotal() {
            return this.selectedItems.reduce((sum, i) => sum + (i.price * i.qty), 0);
        },

        get total() {
            return Math.max(0, this.subtotal - parseFloat(this.discount || 0));
        },

        // ── Pilih paket ───────────────────────────────────────────────
        selectPackage(id, durationDays, isCustom, name, penaltyPct) {
            this.selectedPackageId   = id;
            this.isCustomPackage     = isCustom;
            this.selectedPackageName = name;
            this.selectedPenaltyPct  = penaltyPct;
            if (!isCustom) {
                this.customDurationDays = durationDays;
            }
        },

        formatNumber(n) {
            return new Intl.NumberFormat('id-ID').format(Math.round(n));
        },

        // ── Customer search ───────────────────────────────────────────
        async searchCustomers() {
            if (this.customerSearch.length < 2) { this.customerResults = []; return; }
            const res = await fetch(`/customers/search?q=${encodeURIComponent(this.customerSearch)}`);
            this.customerResults = await res.json();
            this.showCustomerResults = true;
        },

        selectCustomer(c) {
            this.selectedCustomer    = c;
            this.customerSearch      = c.name;
            this.showCustomerResults = false;
            this.guaranteeType       = '';
            // reset verifikasi KTP mengikuti data customer yang dipilih
            this.idPhotoPreview = null;
            this.idPhotoBlob    = null;
            this.idPhotoError   = '';
            this.idNumber       = c.nik || '';
            this.customerNotes  = c.notes || '';
            this.resetKtpZoom();
            if (this.$refs.rentalIdPhotoInput) this.$refs.rentalIdPhotoInput.value = '';
        },

        clearCustomer() {
            this.selectedCustomer = null;
            this.customerSearch   = '';
            this.guaranteeType    = '';
            this.idPhotoPreview   = null;
            this.idPhotoBlob      = null;
            this.idPhotoError     = '';
            this.idNumber         = '';
            this.customerNotes    = '';
            this.resetKtpZoom();
        },

        // ── Foto Jaminan ────────────────────────────────────────────────
        async previewIdPhoto(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.idPhotoError = '';

            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                this.idPhotoError = 'Format file harus JPG, PNG, atau WEBP.';
                event.target.value = '';
                return;
            }
            if (file.size > 15 * 1024 * 1024) {
                this.idPhotoError = 'Ukuran file terlalu besar (maks 15MB).';
                event.target.value = '';
                return;
            }

            this.idPhotoProcessing = true;
            try {
                const blob = await this.compressImageFile(file, 1600, 1900);
                this.idPhotoBlob    = blob;
                this.idPhotoPreview = URL.createObjectURL(blob);
                this.syncIdPhotoInput(blob);
                this.resetKtpZoom();
            } catch (e) {
                console.error(e);
                this.idPhotoError = 'Gagal memproses foto. Coba foto lain.';
            } finally {
                this.idPhotoProcessing = false;
                if (event.target !== this.$refs.rentalIdPhotoInput) {
                    event.target.value = '';
                }
            }
        },

        async rotateIdPhoto(degrees) {
            if (!this.idPhotoBlob) return;
            this.idPhotoProcessing = true;
            try {
                const rotated = await this.rotateImageBlob(this.idPhotoBlob, degrees);
                this.idPhotoBlob    = rotated;
                this.idPhotoPreview = URL.createObjectURL(rotated);
                this.syncIdPhotoInput(rotated);
            } catch (e) {
                console.error(e);
                this.idPhotoError = 'Gagal memutar foto.';
            } finally {
                this.idPhotoProcessing = false;
            }
        },

        // Sinkronkan blob hasil kompresi/rotasi ke <input type=file name="id_photo">
        // supaya ikut terkirim saat form di-submit.
        syncIdPhotoInput(blob) {
            const file = new File([blob], 'ktp.jpg', { type: 'image/jpeg' });
            const dt = new DataTransfer();
            dt.items.add(file);
            this.$refs.rentalIdPhotoInput.files = dt.files;
        },

        // Kompres gambar di browser: resize ke maxDim, turunkan quality bertahap
        // sampai di bawah targetKB (biar tidak ketolak limit 2MB di server).
        compressImageFile(file, maxDim, targetKB) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                const url = URL.createObjectURL(file);
                img.onload = () => {
                    const draw = (quality) => new Promise(res => {
                        let { width, height } = img;
                        if (width > maxDim || height > maxDim) {
                            if (width > height) { height = Math.round(height * maxDim / width); width = maxDim; }
                            else { width = Math.round(width * maxDim / height); height = maxDim; }
                        }
                        const canvas = document.createElement('canvas');
                        canvas.width = width; canvas.height = height;
                        canvas.getContext('2d').drawImage(img, 0, 0, width, height);
                        canvas.toBlob(res, 'image/jpeg', quality);
                    });
                    (async () => {
                        let quality = 0.85;
                        let blob = await draw(quality);
                        let tries = 0;
                        while (blob.size / 1024 > targetKB && quality > 0.4 && tries < 5) {
                            quality -= 0.15;
                            blob = await draw(quality);
                            tries++;
                        }
                        URL.revokeObjectURL(url);
                        resolve(blob);
                    })();
                };
                img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('Gagal memuat gambar')); };
                img.src = url;
            });
        },

        rotateImageBlob(blob, degrees) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                const url = URL.createObjectURL(blob);
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    const swap = Math.abs(degrees % 180) === 90;
                    canvas.width  = swap ? img.height : img.width;
                    canvas.height = swap ? img.width  : img.height;
                    const ctx = canvas.getContext('2d');
                    ctx.translate(canvas.width / 2, canvas.height / 2);
                    ctx.rotate(degrees * Math.PI / 180);
                    ctx.drawImage(img, -img.width / 2, -img.height / 2);
                    canvas.toBlob(b => { URL.revokeObjectURL(url); resolve(b); }, 'image/jpeg', 0.9);
                };
                img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('Gagal memutar gambar')); };
                img.src = url;
            });
        },

        get guaranteePhotoLabel() {
            return {
                ktp: 'Foto KTP', sim: 'Foto SIM',
                deposit: 'Foto Bukti Deposit', custom: 'Foto Jaminan',
            }[this.guaranteeType] || 'Foto Jaminan';
        },
        get guaranteePhotoTitle() {
            return this.guaranteeType ? this.guaranteePhotoLabel : 'Foto Jaminan';
        },
        get guaranteePhotoRequired() {
            return this.guaranteeType === 'ktp' || this.guaranteeType === 'sim';
        },
        get hasValidIdVerification() {
            if (!this.selectedCustomer) return false;
            if (!this.guaranteePhotoRequired) return true;
            return !!(this.idPhotoPreview || this.selectedCustomer.id_photo);
        },

        zoomKtp(delta) {
            this.ktpZoom.scale = Math.min(4, Math.max(1, this.ktpZoom.scale + delta));
            if (this.ktpZoom.scale === 1) { this.ktpZoom.panX = 0; this.ktpZoom.panY = 0; }
        },

        resetKtpZoom() {
            this.ktpZoom.scale = 1;
            this.ktpZoom.panX  = 0;
            this.ktpZoom.panY  = 0;
        },

        // ── Produk ────────────────────────────────────────────────────
        isSelected(id) {
            return this.selectedItems.some(i => i.id === id);
        },

        toggleProduct(id, name, size, color, price, stock) {
            const idx = this.selectedItems.findIndex(i => i.id === id);
            if (idx > -1) this.selectedItems.splice(idx, 1);
            else          this.selectedItems.push({ id, name, size, color, price, qty: 1, stock });
        },

        increaseQty(id) {
            const item = this.selectedItems.find(i => i.id === id);
            if (item && item.qty < item.stock) item.qty++;
        },

        decreaseQty(id) {
            const item = this.selectedItems.find(i => i.id === id);
            if (item) { if (item.qty <= 1) this.removeItem(id); else item.qty--; }
        },

        removeItem(id) {
            this.selectedItems = this.selectedItems.filter(i => i.id !== id);
        },

        nextStep() { if (this.currentStep < 4) this.currentStep++; },
        prevStep() { if (this.currentStep > 1) this.currentStep--; },

        submitForm(e) {
            this.isSubmitting = true;
            e.target.submit();
        }
    }
}
</script>
@endpush