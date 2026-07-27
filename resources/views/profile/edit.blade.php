@extends('layouts.app')

@section('title', 'Edit Profil — Pengaturan')

@push('styles')
<style>
  /* ═══════════════════════════════════════════════
     Halaman ini TIDAK mendefinisikan warna sendiri.
     Semua warna diambil dari CSS variables global yang
     sudah didefinisikan di layouts/app.blade.php
     (--card, --border, --text-*, --primary, --warning,
     --success, --danger, dst) sehingga otomatis mengikuti
     light/dark mode yang di-toggle lewat Alpine (x-data
     darkMode) — tidak perlu palet cream/stone/copper lokal.
  ═══════════════════════════════════════════════ */

  .tab-panel { display: none; animation: panelIn .22s ease; }
  .tab-panel.active { display: block; }
  @keyframes panelIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

  .nav-item { transition: background .15s, color .15s; border-radius: var(--radius-md); color: var(--text-mid); }
  .nav-item:hover:not(.active):not(.danger) { background: var(--bg-soft); }
  .nav-item.active:not(.danger) { background: var(--surf-warning); color: var(--warning); }
  .nav-item.active:not(.danger) .nav-icon { background: rgba(245,158,11,0.18); color: var(--warning); }
  .nav-item .nav-icon { background: var(--bg-soft); color: var(--text-soft); }
  .nav-item.danger { color: var(--danger); }
  .nav-item.danger:hover, .nav-item.danger.active { background: var(--surf-danger); }

  .field-input { transition: border-color .15s, box-shadow .15s; }
  .field-input.is-invalid { border-color: var(--danger) !important; }
  .field-input.is-invalid:focus { box-shadow: 0 0 0 3px var(--surf-danger) !important; }

  .pwd-toggle { color: var(--text-muted); transition: color .15s; }
  .pwd-toggle:hover { color: var(--warning); }

  .mobile-nav-scroll { overflow-x: auto; scrollbar-width: none; -webkit-overflow-scrolling: touch; }
  .mobile-nav-scroll::-webkit-scrollbar { display: none; }

  .avatar-ring { background: var(--sidebar-logo-grad); padding: 2.5px; border-radius: 50%; }

  .strength-bar { height: 4px; border-radius: 99px; background: var(--border); transition: background .3s; }
  .strength-bar.lvl-weak   { background: var(--danger); }
  .strength-bar.lvl-fair   { background: var(--warning); }
  .strength-bar.lvl-good   { background: var(--color-emerald); }
  .strength-bar.lvl-strong { background: var(--success); }

  .invalid-msg { font-size: 12px; color: var(--danger); margin-top: 4px; }

  .tip-box { background: var(--surf-warning); border: 1px solid rgba(245,158,11,0.30); }
  .tip-num { background: rgba(245,158,11,0.28); color: var(--warning); }

  .danger-card-header { background: var(--surf-danger); }
  .danger-inner { background: var(--surf-danger); border: 2px solid rgba(239,68,68,0.20); }

  #deleteModal { background: rgba(0,0,0,.5); backdrop-filter: blur(6px); }
</style>
@endpush

@section('content')

{{-- ══ PAGE HEADING ══ --}}
<div class="mb-7">
    <div class="flex items-center gap-1.5 mb-1.5">
        <span class="text-xs font-medium" style="color:var(--text-muted)">Dashboard</span>
        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" style="color:var(--text-disabled)">
            <path d="M4 2l4 4-4 4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span class="text-xs font-medium" style="color:var(--text-soft)">Profil</span>
    </div>
    <h1 class="font-playfair text-2xl lg:text-[28px] font-semibold leading-tight" style="color:var(--text-dark)">Pengaturan Profil</h1>
    <p class="text-sm mt-1" style="color:var(--text-muted)">Kelola informasi akun, keamanan, dan preferensi Anda.</p>
</div>

{{-- ══ SUCCESS TOAST ══ --}}
@if (session('status'))
<div id="successToast" class="mb-5 flex items-start gap-3 px-4 py-3 rounded-xl border text-sm"
     style="background:var(--surf-success); border-color:rgba(52,211,153,.35); color:var(--success);">
    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" class="flex-shrink-0 mt-0.5">
        <circle cx="8" cy="8" r="7" fill="currentColor"/>
        <path d="M5 8l2 2 4-4" stroke="var(--card)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <span class="flex-1 font-medium">{{ session('status') }}</span>
    <button onclick="this.closest('#successToast').classList.add('hidden')" class="leading-none opacity-70 hover:opacity-100">
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
            <path d="M1 1l12 12M13 1L1 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
    </button>
</div>
@endif

{{-- ══ MAIN GRID ══ --}}
<div class="flex flex-col lg:flex-row gap-5 lg:gap-6 items-start">

    {{-- ══ SIDEBAR ══ --}}
    <aside class="w-full lg:w-[276px] xl:w-[296px] flex-shrink-0">
        <div class="card overflow-hidden">

            {{-- Profile header --}}
            <div class="pt-6 pb-5 px-5 text-center" style="background:var(--bg-soft)">
                <div class="avatar-ring w-fit mx-auto mb-3.5">
                    <div class="w-[68px] h-[68px] rounded-full flex items-center justify-center" style="background:var(--sidebar-logo-grad)">
                        <span class="text-white font-bold text-[22px] font-playfair leading-none">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </span>
                    </div>
                </div>
                <h2 class="font-playfair text-[17px] font-semibold leading-tight mb-0.5" style="color:var(--text-dark)">
                    {{ $user->name }}
                </h2>
                <p class="text-[13px] mb-3.5" style="color:var(--text-muted)">{{ $user->email }}</p>
                @if ($user->branch)
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[12px] font-medium border"
                      style="background:var(--surf-warning); border-color:rgba(245,158,11,.35); color:var(--warning);">
                    <svg width="11" height="11" viewBox="0 0 16 16" fill="none">
                        <rect x="2" y="7" width="12" height="8" rx="1" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M5 7V5a3 3 0 0 1 6 0v2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    {{ $user->branch->name }}
                </span>
                @endif
            </div>

            <div class="border-t mx-5" style="border-color:var(--divider)"></div>

            {{-- Meta info --}}
            <div class="px-5 py-4 space-y-3">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:var(--surf-success); color:var(--success);">
                        <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                            <rect x="2" y="2" width="12" height="13" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
                            <path d="M5 1v3M11 1v3M2 7h12" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                            <circle cx="6" cy="10.5" r="1" fill="currentColor"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-widest font-semibold" style="color:var(--text-muted)">Bergabung sejak</div>
                        <div class="text-[13px] font-semibold mt-0.5" style="color:var(--text-mid)">
                            {{ $user->created_at->format('d M Y') }}
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:var(--surf-warning); color:var(--warning);">
                        <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                            <path d="M8 2l1.5 4.5H14l-3.7 2.7 1.4 4.3L8 11.2l-3.7 2.3 1.4-4.3L2 6.5h4.5z"
                                  stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-widest font-semibold" style="color:var(--text-muted)">Status Akun</div>
                        @if($user->is_active ?? true)
                            <div class="text-[13px] font-semibold mt-0.5" style="color:var(--success);">● Aktif</div>
                        @else
                            <div class="text-[13px] font-semibold mt-0.5" style="color:var(--danger);">● Nonaktif</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="border-t mx-5" style="border-color:var(--divider)"></div>

            {{-- DESKTOP NAVIGATION (vertikal) --}}
            <nav class="p-3 hidden lg:block" id="desktopNav">
                <button class="nav-item active w-full flex items-center gap-3 px-3 py-3 text-left font-medium" data-tab="info">
                    <span class="nav-icon w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors">
                        <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                            <circle cx="8" cy="5" r="2.5" stroke="currentColor" stroke-width="1.4"/>
                            <path d="M3 13c0-2.76 2.24-5 5-5s5 2.24 5 5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <div>
                        <div class="text-[13.5px] font-semibold leading-tight">Informasi Akun</div>
                        <div class="text-[11px] mt-0.5" style="color:var(--text-muted)">Nama &amp; alamat email</div>
                    </div>
                </button>
                <button class="nav-item w-full flex items-center gap-3 px-3 py-3 text-left font-medium" data-tab="password">
                    <span class="nav-icon w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors">
                        <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                            <rect x="3" y="7" width="10" height="7" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
                            <path d="M5 7V5a3 3 0 0 1 6 0v2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                            <circle cx="8" cy="10.5" r="1" fill="currentColor"/>
                        </svg>
                    </span>
                    <div>
                        <div class="text-[13.5px] font-semibold leading-tight">Ubah Password</div>
                        <div class="text-[11px] mt-0.5" style="color:var(--text-muted)">Keamanan akun Anda</div>
                    </div>
                </button>
                <button class="nav-item danger w-full flex items-center gap-3 px-3 py-3 text-left font-medium" data-tab="danger">
                    <span class="nav-icon w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:var(--surf-danger); color:var(--danger);">
                        <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                            <path d="M8 6v3.5M8 11.5v.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            <path d="M7.13 2.5L1.3 12.5a1 1 0 0 0 .87 1.5h11.66a1 1 0 0 0 .87-1.5L8.87 2.5a1 1 0 0 0-1.74 0z"
                                  stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <div>
                        <div class="text-[13.5px] font-semibold leading-tight">Zona Berbahaya</div>
                        <div class="text-[11px] mt-0.5" style="color:var(--danger); opacity:.75;">Hapus akun permanen</div>
                    </div>
                </button>
            </nav>

            {{-- MOBILE NAVIGATION (horizontal scroll) --}}
            <div class="p-3 lg:hidden">
                <div class="mobile-nav-scroll flex gap-2 pb-0.5" id="mobileNav">
                    <button class="nav-item active flex-shrink-0 flex items-center gap-2 px-4 py-2.5 border text-[13px] font-semibold"
                            style="background:var(--surf-warning); border-color:rgba(245,158,11,.35); color:var(--warning);" data-tab="info">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                            <circle cx="8" cy="5" r="2.5" stroke="currentColor" stroke-width="1.4"/>
                            <path d="M3 13c0-2.76 2.24-5 5-5s5 2.24 5 5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                        </svg>
                        Informasi
                    </button>
                    <button class="nav-item flex-shrink-0 flex items-center gap-2 px-4 py-2.5 border text-[13px] font-medium"
                            style="border-color:var(--border); color:var(--text-mid);" data-tab="password">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                            <rect x="3" y="7" width="10" height="7" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
                            <path d="M5 7V5a3 3 0 0 1 6 0v2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                        </svg>
                        Password
                    </button>
                    <button class="nav-item danger flex-shrink-0 flex items-center gap-2 px-4 py-2.5 border text-[13px] font-medium"
                            style="border-color:rgba(239,68,68,.35); color:var(--danger);" data-tab="danger">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                            <path d="M8 6v3.5M8 11.5v.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            <path d="M7.13 2.5L1.3 12.5a1 1 0 0 0 .87 1.5h11.66a1 1 0 0 0 .87-1.5L8.87 2.5a1 1 0 0 0-1.74 0z"
                                  stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
                        </svg>
                        Bahaya
                    </button>
                </div>
            </div>

        </div>
    </aside>

    {{-- ══ MAIN CONTENT ══ --}}
    <div class="flex-1 min-w-0 space-y-5">

        {{-- ─── TAB 1: Informasi Akun ─── --}}
        <div class="tab-panel {{ $errors->has('name') || $errors->has('email') ? 'active' : (session('_tab') === 'info' || !session('_tab') ? 'active' : '') }}" id="panel-info">
            <div class="card overflow-hidden">

                <div class="px-5 sm:px-6 py-4 border-b flex items-center gap-3" style="border-color:var(--divider)">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background:var(--surf-warning); color:var(--warning);">
                        <svg width="17" height="17" viewBox="0 0 16 16" fill="none">
                            <path d="M11 2H5a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1z" stroke="currentColor" stroke-width="1.4"/>
                            <path d="M7 5.5h2M6 8h4M6 10.5h3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-[15px] leading-tight" style="color:var(--text-dark)">Edit Informasi Akun</h3>
                        <p class="text-[12px] mt-0.5" style="color:var(--text-muted)">Perbarui nama lengkap dan alamat email Anda</p>
                    </div>
                </div>

                <div class="px-5 sm:px-6 py-6">
                    <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
                        @csrf
                        @method('PATCH')

                        {{-- Nama --}}
                        <div>
                            <label for="name" class="block text-[13px] font-semibold mb-2" style="color:var(--text-mid)">
                                Nama Lengkap <span class="ml-0.5" style="color:var(--danger)">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" style="color:var(--text-muted)">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                        <circle cx="8" cy="5.5" r="2.5" stroke="currentColor" stroke-width="1.4"/>
                                        <path d="M2.5 13.5c0-3.04 2.46-5.5 5.5-5.5s5.5 2.46 5.5 5.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                                    </svg>
                                </span>
                                <input type="text" id="name" name="name"
                                    class="form-input field-input w-full pl-10 pr-4 @error('name') is-invalid @enderror"
                                    value="{{ old('name', $user->name) }}"
                                    placeholder="Masukkan nama lengkap Anda" required autofocus>
                            </div>
                            @error('name')
                                <div class="invalid-msg">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div>
                            <label for="email" class="block text-[13px] font-semibold mb-2" style="color:var(--text-mid)">
                                Alamat Email <span class="ml-0.5" style="color:var(--danger)">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" style="color:var(--text-muted)">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                        <rect x="2" y="4" width="12" height="9" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
                                        <path d="M2 5.5l6 4 6-4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <input type="email" id="email" name="email"
                                    class="form-input field-input w-full pl-10 pr-4 @error('email') is-invalid @enderror"
                                    value="{{ old('email', $user->email) }}"
                                    placeholder="Masukkan alamat email" required>
                            </div>
                            @error('email')
                                <div class="invalid-msg">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Info notice --}}
                        <div class="flex items-start gap-3 px-4 py-3 rounded-xl border" style="background:var(--bg-soft); border-color:var(--border);">
                            <svg width="15" height="15" viewBox="0 0 16 16" fill="none" class="flex-shrink-0 mt-0.5" style="color:var(--warning)">
                                <circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.3"/>
                                <path d="M8 7v5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                <circle cx="8" cy="4.5" r=".8" fill="currentColor"/>
                            </svg>
                            <p class="text-[12.5px] leading-relaxed" style="color:var(--text-soft)">
                                Perubahan pada alamat email akan memerlukan verifikasi ulang. Pastikan email yang Anda masukkan aktif dan dapat diakses.
                            </p>
                        </div>

                        {{-- Actions --}}
                        <div class="flex flex-col sm:flex-row justify-end gap-2.5 pt-1">
                            <a href="{{ route('profile.edit') }}" class="btn-secondary w-full sm:w-auto justify-center text-[13.5px]">
                                Reset
                            </a>
                            <button type="submit" class="btn-primary w-full sm:w-auto justify-center text-[13.5px]">
                                <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                                    <path d="M13 5L6.5 11.5 3 8" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Simpan Perubahan
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        {{-- ─── TAB 2: Ubah Password ─── --}}
        <div class="tab-panel {{ $errors->has('current_password') || $errors->has('password') ? 'active' : '' }}" id="panel-password">
            <div class="card overflow-hidden">

                <div class="px-5 sm:px-6 py-4 border-b flex items-center gap-3" style="border-color:var(--divider)">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background:var(--surf-warning); color:var(--warning);">
                        <svg width="17" height="17" viewBox="0 0 16 16" fill="none">
                            <rect x="3" y="7" width="10" height="7.5" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
                            <path d="M5.5 7V5A2.5 2.5 0 0 1 10.5 5v2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                            <circle cx="8" cy="10.5" r="1.2" fill="currentColor"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-[15px] leading-tight" style="color:var(--text-dark)">Ubah Password</h3>
                        <p class="text-[12px] mt-0.5" style="color:var(--text-muted)">Pastikan password baru minimal 8 karakter</p>
                    </div>
                </div>

                <div class="px-5 sm:px-6 py-6">
                    <form method="POST" action="{{ route('profile.password') }}" class="space-y-5">
                        @csrf
                        @method('PUT')

                        {{-- Password saat ini --}}
                        <div>
                            <label for="current_password" class="block text-[13px] font-semibold mb-2" style="color:var(--text-mid)">
                                Password Saat Ini <span class="ml-0.5" style="color:var(--danger)">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" style="color:var(--text-muted)">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                        <rect x="3" y="7" width="10" height="7.5" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
                                        <path d="M5.5 7V5A2.5 2.5 0 0 1 10.5 5v2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                                    </svg>
                                </span>
                                <input type="password" id="current_password" name="current_password"
                                    class="form-input field-input w-full pl-10 pr-11 @error('current_password') is-invalid @enderror"
                                    placeholder="Masukkan password saat ini" required>
                                <button type="button" class="pwd-toggle absolute right-3.5 top-1/2 -translate-y-1/2"
                                    data-target="current_password" aria-label="Tampilkan/sembunyikan password">
                                    <svg class="eye-icon" width="17" height="17" viewBox="0 0 16 16" fill="none">
                                        <ellipse cx="8" cy="8" rx="6" ry="4" stroke="currentColor" stroke-width="1.4"/>
                                        <circle cx="8" cy="8" r="1.8" stroke="currentColor" stroke-width="1.3"/>
                                    </svg>
                                </button>
                            </div>
                            @error('current_password')
                                <div class="invalid-msg">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Password baru + konfirmasi --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="password" class="block text-[13px] font-semibold mb-2" style="color:var(--text-mid)">
                                    Password Baru <span class="ml-0.5" style="color:var(--danger)">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" style="color:var(--text-muted)">
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                            <path d="M10.5 2.5l3 3-7.5 7.5H3v-3l7.5-7.5z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                    <input type="password" id="password" name="password"
                                        class="form-input field-input w-full pl-10 pr-11 @error('password') is-invalid @enderror"
                                        placeholder="Password baru" oninput="checkStrength(this.value)" required>
                                    <button type="button" class="pwd-toggle absolute right-3.5 top-1/2 -translate-y-1/2"
                                        data-target="password" aria-label="Tampilkan/sembunyikan password">
                                        <svg class="eye-icon" width="17" height="17" viewBox="0 0 16 16" fill="none">
                                            <ellipse cx="8" cy="8" rx="6" ry="4" stroke="currentColor" stroke-width="1.4"/>
                                            <circle cx="8" cy="8" r="1.8" stroke="currentColor" stroke-width="1.3"/>
                                        </svg>
                                    </button>
                                </div>
                                {{-- Strength bar --}}
                                <div class="mt-2 flex gap-1">
                                    <div class="strength-bar flex-1" id="bar1"></div>
                                    <div class="strength-bar flex-1" id="bar2"></div>
                                    <div class="strength-bar flex-1" id="bar3"></div>
                                    <div class="strength-bar flex-1" id="bar4"></div>
                                </div>
                                <div class="text-[11px] mt-1" id="strengthLabel" style="color:var(--text-muted)">Masukkan password baru</div>
                                @error('password')
                                    <div class="invalid-msg">{{ $message }}</div>
                                @enderror
                            </div>
                            <div>
                                <label for="password_confirmation" class="block text-[13px] font-semibold mb-2" style="color:var(--text-mid)">
                                    Konfirmasi Password <span class="ml-0.5" style="color:var(--danger)">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" style="color:var(--text-muted)">
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                            <path d="M13 5L6.5 11.5 3 8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                                            <rect x="1" y="1" width="14" height="14" rx="2" stroke="currentColor" stroke-width="1.3"/>
                                        </svg>
                                    </span>
                                    <input type="password" id="password_confirmation" name="password_confirmation"
                                        class="form-input field-input w-full pl-10 pr-11"
                                        placeholder="Ulangi password baru" required>
                                    <button type="button" class="pwd-toggle absolute right-3.5 top-1/2 -translate-y-1/2"
                                        data-target="password_confirmation" aria-label="Tampilkan/sembunyikan password">
                                        <svg class="eye-icon" width="17" height="17" viewBox="0 0 16 16" fill="none">
                                            <ellipse cx="8" cy="8" rx="6" ry="4" stroke="currentColor" stroke-width="1.4"/>
                                            <circle cx="8" cy="8" r="1.8" stroke="currentColor" stroke-width="1.3"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Tips --}}
                        <div class="tip-box p-4 rounded-xl">
                            <div class="flex items-center gap-2 mb-2.5">
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="none" style="color:var(--warning)">
                                    <circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.3"/>
                                    <path d="M8 7v5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                    <circle cx="8" cy="4.5" r=".8" fill="currentColor"/>
                                </svg>
                                <span class="text-[12px] font-bold" style="color:var(--warning)">Tips Keamanan Password</span>
                            </div>
                            <ul class="space-y-1.5">
                                <li class="flex items-center gap-2 text-[12px]" style="color:var(--warning)">
                                    <span class="tip-num w-4 h-4 rounded-full flex items-center justify-center flex-shrink-0 text-[9px] font-bold">1</span>
                                    Minimal 8 karakter
                                </li>
                                <li class="flex items-center gap-2 text-[12px]" style="color:var(--warning)">
                                    <span class="tip-num w-4 h-4 rounded-full flex items-center justify-center flex-shrink-0 text-[9px] font-bold">2</span>
                                    Kombinasi huruf besar, kecil, dan angka
                                </li>
                                <li class="flex items-center gap-2 text-[12px]" style="color:var(--warning)">
                                    <span class="tip-num w-4 h-4 rounded-full flex items-center justify-center flex-shrink-0 text-[9px] font-bold">3</span>
                                    Tambahkan karakter khusus (!@#$%) untuk keamanan ekstra
                                </li>
                            </ul>
                        </div>

                        <div class="flex flex-col sm:flex-row justify-end pt-1">
                            <button type="submit" class="btn-primary w-full sm:w-auto justify-center text-[13.5px]">
                                <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                                    <rect x="3" y="7" width="10" height="7.5" rx="1.5" stroke="white" stroke-width="1.4"/>
                                    <path d="M5.5 7V5A2.5 2.5 0 0 1 10.5 5v2" stroke="white" stroke-width="1.4" stroke-linecap="round"/>
                                    <circle cx="8" cy="10.5" r="1.2" fill="white"/>
                                </svg>
                                Update Password
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        {{-- ─── TAB 3: Zona Berbahaya ─── --}}
        <div class="tab-panel" id="panel-danger">
            <div class="card overflow-hidden">

                <div class="danger-card-header px-5 sm:px-6 py-4 border-b flex items-center gap-3" style="border-color:rgba(239,68,68,.20)">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(239,68,68,.20); color:var(--danger);">
                        <svg width="17" height="17" viewBox="0 0 16 16" fill="none">
                            <path d="M8 6v3.5M8 11.5v.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            <path d="M7.13 2.5L1.3 12.5a1 1 0 0 0 .87 1.5h11.66a1 1 0 0 0 .87-1.5L8.87 2.5a1 1 0 0 0-1.74 0z"
                                  stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-[15px] leading-tight" style="color:var(--danger)">Zona Berbahaya</h3>
                        <p class="text-[12px] mt-0.5" style="color:var(--danger); opacity:.7;">Tindakan di sini bersifat permanen dan tidak dapat diurungkan</p>
                    </div>
                </div>

                <div class="px-5 sm:px-6 py-6">
                    <div class="danger-inner rounded-xl overflow-hidden">
                        <div class="px-5 py-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-start gap-3.5">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(239,68,68,.20); color:var(--danger);">
                                    <svg width="18" height="18" viewBox="0 0 16 16" fill="none">
                                        <path d="M3 5h10M6 5V3.5A.5.5 0 0 1 6.5 3h3a.5.5 0 0 1 .5.5V5M5 5l.75 8h4.5L11 5"
                                              stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-[14px] mb-1" style="color:var(--danger)">Hapus Akun Ini</h4>
                                    <p class="text-[12.5px] leading-relaxed max-w-md" style="color:var(--text-soft)">
                                        Setelah dihapus, semua data akun Anda — termasuk riwayat, pengaturan, dan file — akan hilang secara permanen dari sistem kami.
                                    </p>
                                </div>
                            </div>
                            <button type="button" onclick="openDeleteModal()" class="btn-danger w-full sm:w-auto flex-shrink-0 justify-center text-[13.5px]">
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                                    <path d="M3 5h10M6 5V3.5A.5.5 0 0 1 6.5 3h3a.5.5 0 0 1 .5.5V5M5 5l.75 8h4.5L11 5"
                                          stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Hapus Akun
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- end main content --}}
</div>{{-- end grid --}}

{{-- ══ MODAL HAPUS AKUN ══ --}}
<div id="deleteModal"
     style="display:none; position:fixed; inset:0; z-index:50; padding:16px; align-items:center; justify-content:center;">
    <div class="card w-full max-w-md overflow-hidden" style="position:relative;">

        <div class="px-5 py-4 border-b flex items-center justify-between" style="border-color:var(--divider)">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:rgba(239,68,68,.20); color:var(--danger);">
                    <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                        <path d="M3 5h10M6 5V3.5A.5.5 0 0 1 6.5 3h3a.5.5 0 0 1 .5.5V5M5 5l.75 8h4.5L11 5"
                              stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h5 class="font-semibold text-[15px]" style="color:var(--danger)">Hapus Akun</h5>
            </div>
            <button onclick="closeDeleteModal()"
                class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
                style="color:var(--text-muted)"
                onmouseover="this.style.background='var(--bg-soft)'" onmouseout="this.style.background='transparent'">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                    <path d="M1 1l12 12M13 1L1 13" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <div class="px-5 py-5 space-y-4">
            <div class="flex items-start gap-3 p-3.5 rounded-xl border" style="background:var(--surf-danger); border-color:rgba(239,68,68,.25)">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" class="flex-shrink-0 mt-0.5" style="color:var(--danger)">
                    <path d="M8 6v3.5M8 11.5v.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    <path d="M7.13 2.5L1.3 12.5a1 1 0 0 0 .87 1.5h11.66a1 1 0 0 0 .87-1.5L8.87 2.5a1 1 0 0 0-1.74 0z"
                          stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
                </svg>
                <p class="text-[12.5px] leading-relaxed" style="color:var(--danger)">
                    Tindakan ini <strong>tidak dapat dibatalkan</strong>. Semua data akun akan dihapus secara permanen dari server kami.
                </p>
            </div>

            <form method="POST" action="{{ route('profile.destroy') }}" id="deleteAccountForm">
                @csrf
                @method('DELETE')
                <label class="block text-[13px] font-semibold mb-2" style="color:var(--text-mid)">
                    Konfirmasi dengan Password <span class="ml-0.5" style="color:var(--danger)">*</span>
                </label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" style="color:var(--text-muted)">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <rect x="3" y="7" width="10" height="7.5" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
                            <path d="M5.5 7V5A2.5 2.5 0 0 1 10.5 5v2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <input type="password" id="delete_password" name="password"
                        class="form-input field-input w-full pl-10 pr-11 @error('password', 'userDeletion') is-invalid @enderror"
                        placeholder="Masukkan password Anda" required>
                    <button type="button" class="pwd-toggle absolute right-3.5 top-1/2 -translate-y-1/2"
                        data-target="delete_password" aria-label="Tampilkan/sembunyikan">
                        <svg class="eye-icon" width="17" height="17" viewBox="0 0 16 16" fill="none">
                            <ellipse cx="8" cy="8" rx="6" ry="4" stroke="currentColor" stroke-width="1.4"/>
                            <circle cx="8" cy="8" r="1.8" stroke="currentColor" stroke-width="1.3"/>
                        </svg>
                    </button>
                </div>
                @error('password', 'userDeletion')
                    <div class="invalid-msg">{{ $message }}</div>
                @enderror
            </form>
        </div>

        <div class="px-5 py-4 border-t flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5" style="border-color:var(--divider)">
            <button onclick="closeDeleteModal()" class="btn-secondary w-full sm:w-auto justify-center text-[13.5px]">
                Batal
            </button>
            <button type="submit" form="deleteAccountForm" class="btn-danger w-full sm:w-auto justify-center text-[13.5px] font-semibold"
                style="background:linear-gradient(135deg,var(--danger),#B91C1C); color:#fff; border:none;">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                    <path d="M3 5h10M6 5V3.5A.5.5 0 0 1 6.5 3h3a.5.5 0 0 1 .5.5V5M5 5l.75 8h4.5L11 5"
                          stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Ya, Hapus Akun Saya
            </button>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
/* ── Tab switching ── */
function switchTab(tab) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel-' + tab).classList.add('active');

    document.querySelectorAll('#desktopNav .nav-item').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.tab === tab);
    });

    document.querySelectorAll('#mobileNav .nav-item').forEach(btn => {
        btn.classList.remove('active');
        btn.style.background = '';
        btn.style.borderColor = '';
        btn.style.color = '';

        if (btn.dataset.tab === tab) {
            btn.classList.add('active');
            if (tab === 'danger') {
                btn.style.background = 'var(--surf-danger)';
                btn.style.borderColor = 'rgba(239,68,68,.35)';
                btn.style.color = 'var(--danger)';
            } else {
                btn.style.background = 'var(--surf-warning)';
                btn.style.borderColor = 'rgba(245,158,11,.35)';
                btn.style.color = 'var(--warning)';
            }
        } else if (btn.classList.contains('danger')) {
            btn.style.borderColor = 'rgba(239,68,68,.35)';
            btn.style.color = 'var(--danger)';
        } else {
            btn.style.borderColor = 'var(--border)';
            btn.style.color = 'var(--text-mid)';
        }
    });
}

document.querySelectorAll('.nav-item[data-tab]').forEach(btn => {
    btn.addEventListener('click', () => switchTab(btn.dataset.tab));
});

/* ── Auto-open tab berdasarkan error validasi ── */
@if ($errors->has('current_password') || ($errors->has('password') && old('_method') !== 'DELETE'))
    switchTab('password');
@elseif ($errors->has('name') || $errors->has('email'))
    switchTab('info');
@endif

/* ── Auto-buka modal jika ada error delete ── */
@if ($errors->hasAny(['password']) && old('_method') === 'DELETE')
    switchTab('danger');
    document.addEventListener('DOMContentLoaded', function () {
        openDeleteModal();
    });
@endif

/* ── Password toggle ── */
document.querySelectorAll('.pwd-toggle').forEach(btn => {
    btn.addEventListener('click', function () {
        const input = document.getElementById(this.dataset.target);
        const isText = input.type === 'text';
        input.type = isText ? 'password' : 'text';
        const svg = this.querySelector('svg');
        svg.innerHTML = isText
            ? '<ellipse cx="8" cy="8" rx="6" ry="4" stroke="currentColor" stroke-width="1.4"/><circle cx="8" cy="8" r="1.8" stroke="currentColor" stroke-width="1.3"/>'
            : '<ellipse cx="8" cy="8" rx="6" ry="4" stroke="currentColor" stroke-width="1.4"/><line x1="2" y1="2" x2="14" y2="14" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>';
    });
});

/* ── Password strength ── */
function checkStrength(val) {
    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = ['', 'lvl-weak', 'lvl-fair', 'lvl-good', 'lvl-strong'];
    const labels = ['Masukkan password baru','Lemah','Cukup','Kuat','Sangat kuat'];

    for (let i = 1; i <= 4; i++) {
        const bar = document.getElementById('bar' + i);
        bar.className = 'strength-bar flex-1' + (i <= score ? ' ' + levels[score] : '');
    }
    const lbl = document.getElementById('strengthLabel');
    lbl.textContent = val.length === 0 ? labels[0] : labels[score];
    lbl.style.color = val.length === 0 ? 'var(--text-muted)' :
        (score <= 1 ? 'var(--danger)' : score === 2 ? 'var(--warning)' : 'var(--success)');
}

/* ── Modal ── */
function openDeleteModal() {
    const m = document.getElementById('deleteModal');
    m.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    document.body.style.overflow = '';
}
document.getElementById('deleteModal').addEventListener('click', function (e) {
    if (e.target === this) closeDeleteModal();
});
</script>
@endpush