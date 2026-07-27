<div x-data="{ showContact: false, showAbout: false, msgType: 'contact_admin' }">

    <footer class="mt-10 pt-6 pb-4 border-t" style="border-color: var(--border)">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 text-xs" style="color: var(--text-soft)">
            <div class="flex items-center gap-1.5">
                <span class="font-playfair font-semibold" style="color: var(--text-dark)">{{ ( 'MonsieurJas') }}</span>
                <span>&copy; {{ date('Y') }} — Premium Suit Rental System</span>
            </div>

            <div class="flex items-center gap-4">
                <button type="button" @click="showAbout = true" class="flex items-center gap-1 hover:underline">
                    <i data-lucide="info" class="w-3.5 h-3.5"></i>
                    Tentang Aplikasi
                </button>

                @unless(auth()->user()->isSuperAdmin())
                <button type="button" @click="showContact = true; msgType = 'bug_report'" class="flex items-center gap-1 hover:underline">
                    <i data-lucide="bug" class="w-3.5 h-3.5"></i>
                    Laporkan Bug
                </button>

                <button type="button" @click="showContact = true; msgType = 'contact_admin'" class="flex items-center gap-1 hover:underline">
                    <i data-lucide="message-circle-question" class="w-3.5 h-3.5"></i>
                    Hubungi Admin
                </button>
                @else
                @php $unreadSupportCount = \App\Models\SupportMessage::unread()->count(); @endphp
                <a href="{{ route('support-messages.index') }}" class="flex items-center gap-1 hover:underline relative">
                    <i data-lucide="inbox" class="w-3.5 h-3.5"></i>
                    Pesan Masuk
                    @if($unreadSupportCount > 0)
                    <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold text-white" style="background:#EF4444">{{ $unreadSupportCount }}</span>
                    @endif
                </a>
                @endunless
            </div>
        </div>
    </footer>

    {{-- Modal: Hubungi Admin / Laporkan Bug --}}
    <div x-show="showContact" x-cloak class="fixed inset-0 z-50 flex items-center justify-center modal-overlay" style="display:none">
        <div class="modal-box w-full max-w-md mx-4 p-6" @click.outside="showContact = false">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-playfair font-semibold text-base" style="color: var(--text-dark)">
                    <span x-show="msgType === 'bug_report'">Laporkan Bug</span>
                    <span x-show="msgType !== 'bug_report'">Hubungi Super Admin</span>
                </h3>
                <button type="button" @click="showContact = false" style="color: var(--text-soft)">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('support-messages.store') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="type" x-model="msgType">

                <div class="flex gap-2">
                    <button type="button" @click="msgType = 'contact_admin'"
                            class="flex-1 text-xs py-1.5 rounded-lg border transition-colors"
                            :style="msgType === 'contact_admin' ? 'background: var(--primary); color:#fff; border-color: var(--primary)' : 'border-color: var(--border); color: var(--text-soft)'">
                        Pertanyaan / Kontak
                    </button>
                    <button type="button" @click="msgType = 'bug_report'"
                            class="flex-1 text-xs py-1.5 rounded-lg border transition-colors"
                            :style="msgType === 'bug_report' ? 'background: #EF4444; color:#fff; border-color: #EF4444' : 'border-color: var(--border); color: var(--text-soft)'">
                        Laporan Bug
                    </button>
                </div>

                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--text-dark)">Subjek (opsional)</label>
                    <input type="text" name="subject" maxlength="150" class="form-input text-sm"
                           placeholder="Ringkasan singkat...">
                </div>

                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--text-dark)">Pesan</label>
                    <textarea name="message" rows="4" required maxlength="2000" class="form-input text-sm"
                              placeholder="Tulis pesan atau jelaskan bug yang ditemukan (halaman mana, apa yang terjadi)..."></textarea>
                </div>

                <p class="text-[11px]" style="color: var(--text-soft)">
                    Pesan ini akan dikirim langsung ke Super Admin sebagai
                    <span class="font-medium">{{ auth()->user()->name }}</span> ({{ auth()->user()->branch?->name ?? '-' }}).
                </p>

                <div class="flex gap-3 pt-1">
                    <button type="button" @click="showContact = false" class="btn-secondary flex-1 justify-center">Batal</button>
                    <button type="submit" class="btn-primary flex-1 justify-center">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        Kirim
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Tentang Aplikasi --}}
    <div x-show="showAbout" x-cloak class="fixed inset-0 z-50 flex items-center justify-center modal-overlay" style="display:none">
        <div class="modal-box w-full max-w-sm mx-4 p-6" @click.outside="showAbout = false">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-playfair font-semibold text-base" style="color: var(--text-dark)">Tentang Aplikasi</h3>
                <button type="button" @click="showAbout = false" style="color: var(--text-soft)">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="text-center py-2">
                <div class="w-14 h-14 rounded-2xl mx-auto mb-3 flex items-center justify-center"
                     style="background: linear-gradient(135deg, var(--primary), var(--primary-dark))">
                    <i data-lucide="crown" class="w-7 h-7" style="color:#fff"></i>
                </div>
                <h4 class="font-playfair font-bold text-lg" style="color: var(--text-dark)">{{ ( 'MonsieurJas') }}</h4>
                <p class="text-xs mt-1" style="color: var(--text-soft)">Premium Suit Rental System</p>
                <p class="text-xs mt-3" style="color: var(--text-soft)">Versi {{ config('app.version', '1.0.0') }}</p>
            </div>
            <div class="mt-4 pt-4 border-t text-xs space-y-1.5" style="border-color: var(--border); color: var(--text-soft)">
                <p>Sistem manajemen rental jas multi-cabang — penyewaan, laundry, customer, dan laporan dalam satu platform.</p>
                <p class="pt-2">&copy; {{ date('Y') }} {{ ( 'MonsieurJas') }}. All rights reserved.</p>
            </div>
        </div>
    </div>
</div>
