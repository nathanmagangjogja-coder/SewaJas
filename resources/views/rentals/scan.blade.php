@extends('layouts.app')

@section('title', 'Scan QR Code')
@section('page-title', 'Scan QR Code')
@section('subtitle', 'Scan QR untuk melihat atau memproses pengembalian')

@section('content')
    <div class="max-w-2xl mx-auto space-y-5" x-data="qrScanner()">

        <!-- Scanner Card -->
        <div class="card p-6">
            <div class="text-center mb-6">
                <div class="inline-flex w-16 h-16 rounded-2xl items-center justify-center mb-3"
                    style="background: linear-gradient(135deg, #D6B98C20, #D6B98C40)">
                    <i data-lucide="scan-qr-code" class="w-8 h-8" style="color: #D6B98C"></i>
                </div>
                <h2 class="font-playfair text-xl font-semibold" style="color: var(--text-dark)">Scan QR Transaksi</h2>
                <p class="text-sm mt-1" style="color: var(--text-soft)">Gunakan kamera atau unggah foto QR code</p>
            </div>

            {{-- Tab switcher: Kamera / Upload Foto --}}
            <div class="flex gap-2 p-1 rounded-xl mb-4" style="background: var(--secondary)">
                <button @click="mode = 'camera'; stopCamera()"
                    class="flex-1 flex items-center justify-center gap-2 py-2 rounded-lg text-sm font-medium transition-all"
                    :style="mode === 'camera'
                        ? 'background: var(--card); color: var(--text-dark); box-shadow: 0 1px 4px rgba(0,0,0,0.08)'
                        : 'color: var(--text-soft)'">
                    <i data-lucide="camera" class="w-4 h-4"></i> Kamera
                </button>
                <button @click="mode = 'upload'; stopCamera()"
                    class="flex-1 flex items-center justify-center gap-2 py-2 rounded-lg text-sm font-medium transition-all"
                    :style="mode === 'upload'
                        ? 'background: var(--card); color: var(--text-dark); box-shadow: 0 1px 4px rgba(0,0,0,0.08)'
                        : 'color: var(--text-soft)'">
                    <i data-lucide="image-up" class="w-4 h-4"></i> Unggah Foto
                </button>
            </div>

            {{-- ── MODE KAMERA ── --}}
            <div x-show="mode === 'camera'">
                <div class="relative rounded-2xl overflow-hidden mb-4" style="background: #1A1612;">
                    <video id="qr-video" class="w-full" style="max-height: 300px; object-fit: cover;" autoplay
                        playsinline></video>

                    <div class="absolute inset-0 flex items-center justify-center" x-show="!scanning">
                        <div class="text-center">
                            <i data-lucide="camera-off" class="w-10 h-10 mx-auto mb-3" style="color: rgba(214,185,140,0.5)"></i>
                            <p class="text-sm" style="color: rgba(255,255,255,0.6)">Kamera belum aktif</p>
                        </div>
                    </div>

                    <div x-show="scanning" class="absolute inset-0 flex items-center justify-center">
                        <div class="relative w-48 h-48">
                            <div class="absolute top-0 left-0 w-8 h-8 rounded-tl-lg"
                                style="border-color: #D6B98C; border-width: 3px 0 0 3px; border-style: solid;"></div>
                            <div class="absolute top-0 right-0 w-8 h-8 rounded-tr-lg"
                                style="border-color: #D6B98C; border-width: 3px 3px 0 0; border-style: solid;"></div>
                            <div class="absolute bottom-0 left-0 w-8 h-8 rounded-bl-lg"
                                style="border-color: #D6B98C; border-width: 0 0 3px 3px; border-style: solid;"></div>
                            <div class="absolute bottom-0 right-0 w-8 h-8 rounded-br-lg"
                                style="border-color: #D6B98C; border-width: 0 3px 3px 0; border-style: solid;"></div>
                            <div class="absolute left-1 right-1 h-0.5 scan-line"
                                style="background: linear-gradient(90deg, transparent, #D6B98C, transparent);"></div>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button x-show="!scanning" @click="startCamera()" class="btn-primary flex-1 justify-center">
                        <i data-lucide="camera" class="w-4 h-4"></i>
                        Aktifkan Kamera
                    </button>
                    <button x-show="scanning" @click="stopCamera()" class="btn-secondary flex-1 justify-center">
                        <i data-lucide="camera-off" class="w-4 h-4"></i>
                        Matikan Kamera
                    </button>
                </div>
            </div>

            {{-- ── MODE UPLOAD FOTO ── --}}
            <div x-show="mode === 'upload'">
                <div class="relative rounded-2xl overflow-hidden mb-4 flex items-center justify-center"
                     style="background: var(--secondary); min-height: 220px; border: 2px dashed var(--border)"
                     @dragover.prevent="dragging = true"
                     @dragleave.prevent="dragging = false"
                     @drop.prevent="handleDrop($event)"
                     :style="dragging ? 'border-color:#D6B98C; background: rgba(214,185,140,0.08)' : ''">

                    <template x-if="!previewImage">
                        <div class="flex flex-col items-center gap-3 p-8 text-center cursor-pointer"
                             @click="$refs.fileInput.click()">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center"
                                style="background: rgba(214,185,140,0.15)">
                                <i data-lucide="image-up" class="w-7 h-7" style="color:#D6B98C"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium" style="color:var(--text-dark)">Klik untuk pilih foto</p>
                                <p class="text-xs mt-1" style="color:var(--text-soft)">atau seret & lepas gambar QR di sini</p>
                            </div>
                        </div>
                    </template>

                    <template x-if="previewImage">
                        <div class="relative w-full p-4">
                            <img :src="previewImage" class="w-full max-h-60 object-contain rounded-xl mx-auto">
                            <button @click="clearPreview()"
                                class="absolute top-2 right-2 w-8 h-8 rounded-full flex items-center justify-center"
                                style="background: rgba(0,0,0,0.6)">
                                <i data-lucide="x" class="w-4 h-4 text-white"></i>
                            </button>
                        </div>
                    </template>
                </div>

                <input type="file" x-ref="fileInput" accept="image/*" class="hidden"
                    @change="handleFileSelect($event)">

                <div class="flex gap-3">
                    <button @click="$refs.fileInput.click()" class="btn-secondary flex-1 justify-center">
                        <i data-lucide="folder-open" class="w-4 h-4"></i>
                        Pilih dari Galeri
                    </button>
                    <button x-show="previewImage" @click="scanUploadedImage()" class="btn-primary flex-1 justify-center"
                        :disabled="scanningUpload">
                        <i data-lucide="scan-search" class="w-4 h-4" x-show="!scanningUpload"></i>
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin" x-show="scanningUpload"></i>
                        <span x-text="scanningUpload ? 'Memindai...' : 'Pindai Foto'"></span>
                    </button>
                </div>
            </div>
        </div>

        <canvas id="qr-canvas" class="hidden"></canvas>

        <!-- Manual Input -->
        <div class="card p-6">
            <h3 class="font-playfair font-semibold text-sm mb-4" style="color: var(--text-dark)">Input Manual</h3>
            <p class="text-xs mb-3" style="color: var(--text-soft)">Masukkan nomor invoice secara manual jika QR tidak
                terscan</p>
            <form @submit.prevent="searchManual" class="flex gap-3">
                <input type="text" x-model="manualInvoice" placeholder="Contoh: INV20240101010001"
                    class="form-input flex-1 font-mono">
                <button type="submit" class="btn-primary" :disabled="!manualInvoice">
                    <i data-lucide="search" class="w-4 h-4"></i>
                    Cari
                </button>
            </form>
        </div>

        <!-- Result -->
        <template x-if="result">
            <div class="card p-6 animate-fade-in">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background: #F0FDF4">
                        <i data-lucide="check-circle" class="w-4 h-4" style="color: #15803D"></i>
                    </div>
                    <h3 class="font-playfair font-semibold text-base" style="color: var(--text-dark)">Data Transaksi
                        Ditemukan</h3>
                </div>
                <div id="scan-result-container" x-html="result"></div>
            </div>
        </template>

        <!-- Error -->
        <template x-if="error">
            <div class="p-4 rounded-xl flex items-center gap-3 animate-fade-in"
                style="background: #FFF1F0; border: 1px solid #FECACA">
                <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0" style="color: #C0392B"></i>
                <div>
                    <p class="font-semibold text-sm" style="color: #C0392B">Transaksi Tidak Ditemukan</p>
                    <p class="text-xs mt-0.5" style="color: #E74C3C" x-text="error"></p>
                </div>
            </div>
        </template>

    </div>
@endsection

@push('styles')
    <style>
        @keyframes scanLine {
            0% { top: 4px; }
            50% { top: calc(100% - 4px); }
            100% { top: 4px; }
        }
        .scan-line {
            animation: scanLine 2s ease-in-out infinite;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
    <script>
       function qrScanner() {
    return {
        mode: 'camera',
        scanning: false,
        scanningUpload: false,
        dragging: false,
        manualInvoice: '',
        previewImage: null,
        result: null,
        error: null,
        videoStream: null,
        scanInterval: null,
        jsQRReady: false,

        init() {
            // Pastikan jsQR benar-benar ter-load sebelum dipakai
            this.checkJsQR();
        },

        checkJsQR() {
            if (typeof jsQR === 'function') {
                this.jsQRReady = true;
                console.log('[QR Scanner] jsQR library loaded ✓');
            } else {
                console.error('[QR Scanner] jsQR belum ter-load, mencoba lagi...');
                setTimeout(() => this.checkJsQR(), 200);
            }
        },

        async startCamera() {
            try {
                this.result = null;
                this.error = null;
                const video = document.getElementById('qr-video');
                this.videoStream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'environment',
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    }
                });
                video.srcObject = this.videoStream;
                this.scanning = true;
                this.scanInterval = setInterval(() => this.scanFrame(), 300);
            } catch (err) {
                this.error = 'Tidak dapat mengakses kamera: ' + err.message;
            }
        },

        stopCamera() {
            if (this.videoStream) {
                this.videoStream.getTracks().forEach(t => t.stop());
                this.videoStream = null;
            }
            if (this.scanInterval) {
                clearInterval(this.scanInterval);
                this.scanInterval = null;
            }
            this.scanning = false;
        },

        scanFrame() {
            const video = document.getElementById('qr-video');
            const canvas = document.getElementById('qr-canvas');
            if (!video || video.readyState !== video.HAVE_ENOUGH_DATA) return;

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const code = jsQR(imageData.data, imageData.width, imageData.height, {
                inversionAttempts: 'dontInvert',
            });

            if (code) {
                this.stopCamera();
                this.processQrData(code.data);
            }
        },

        // ── Upload dari foto ──────────────────────────────────────
        handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) this.loadImageFile(file);
        },

        handleDrop(event) {
            this.dragging = false;
            const file = event.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) this.loadImageFile(file);
        },

        loadImageFile(file) {
            this.result = null;
            this.error = null;
            const reader = new FileReader();
            reader.onload = (e) => {
                this.previewImage = e.target.result;
                console.log('[QR Scanner] Foto berhasil dimuat, siap dipindai');
            };
            reader.onerror = () => {
                this.error = 'Gagal membaca file foto.';
            };
            reader.readAsDataURL(file);
        },

        clearPreview() {
            this.previewImage = null;
            this.$refs.fileInput.value = '';
            this.error = null;
        },

        scanUploadedImage() {
            if (!this.previewImage) return;

            // Cek library siap dulu
            if (typeof jsQR !== 'function') {
                this.error = 'Library pemindai belum siap, coba lagi sebentar.';
                console.error('[QR Scanner] jsQR tidak tersedia saat scanUploadedImage dipanggil');
                return;
            }

            this.scanningUpload = true;
            this.error = null;

            const img = new Image();

            img.onload = () => {
                console.log('[QR Scanner] Gambar dimensi asli:', img.width, 'x', img.height);

                try {
                    const canvas = document.getElementById('qr-canvas');

                    // Batasi ukuran maksimal supaya tidak berat & getImageData tidak gagal
                    const MAX_DIM = 1600;
                    let { width, height } = img;
                    if (width > MAX_DIM || height > MAX_DIM) {
                        const scale = MAX_DIM / Math.max(width, height);
                        width = Math.round(width * scale);
                        height = Math.round(height * scale);
                    }

                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d', { willReadFrequently: true });
                    ctx.clearRect(0, 0, width, height);
                    ctx.drawImage(img, 0, 0, width, height);

                    const imageData = ctx.getImageData(0, 0, width, height);

                    // Coba pertama: normal
                    let code = jsQR(imageData.data, imageData.width, imageData.height, {
                        inversionAttempts: 'attemptBoth',
                    });

                    // Kalau gagal, coba lagi dengan ukuran asli (kadang resize merusak pola QR kecil)
                    if (!code && (width !== img.width || height !== img.height)) {
                        console.log('[QR Scanner] Retry dengan resolusi asli...');
                        canvas.width = img.width;
                        canvas.height = img.height;
                        ctx.clearRect(0, 0, img.width, img.height);
                        ctx.drawImage(img, 0, 0, img.width, img.height);
                        const fullData = ctx.getImageData(0, 0, img.width, img.height);
                        code = jsQR(fullData.data, fullData.width, fullData.height, {
                            inversionAttempts: 'attemptBoth',
                        });
                    }

                    this.scanningUpload = false;

                    if (code) {
                        console.log('[QR Scanner] QR terdeteksi:', code.data);
                        this.processQrData(code.data);
                    } else {
                        console.warn('[QR Scanner] QR tidak terdeteksi di foto');
                        this.error = 'QR code tidak terdeteksi pada foto. Pastikan foto jelas, tidak buram, dan QR terlihat penuh (tidak terpotong).';
                    }
                } catch (err) {
                    this.scanningUpload = false;
                    console.error('[QR Scanner] Error saat memproses canvas:', err);
                    this.error = 'Gagal memproses gambar: ' + err.message;
                }
            };

            img.onerror = () => {
                this.scanningUpload = false;
                console.error('[QR Scanner] Gagal load image dari preview data URL');
                this.error = 'Gagal memuat gambar. Coba foto lain.';
            };

            img.src = this.previewImage;
        },

        async processQrData(data) {
            // ── 1. Deteksi QR produk: /products/{id} ────────────────────────
            // QR produk berisi URL seperti: http://127.0.0.1:8000/products/3
            const matchProduct = data.match(/\/products\/(\d+)/);
            if (matchProduct) {
                const productId = matchProduct[1];
                await this.fetchProduct(productId);
                return;
            }

            // ── 2. Deteksi QR invoice rental ─────────────────────────────────
            let invoice = data;

            const matchPath  = data.match(/\/rentals\/scan\/([A-Z0-9]+)/);
            const matchQuery = data.match(/\/rentals\/scan\?([A-Z0-9]+)/);
            const matchParam = data.match(/[?&]invoice=([A-Z0-9]+)/i);
            // Format invoice langsung: INV20240101010001
            const matchInv   = data.match(/^(INV[0-9]+)$/i);

            if (matchPath)  invoice = matchPath[1];
            else if (matchQuery) invoice = matchQuery[1];
            else if (matchParam) invoice = matchParam[1];
            else if (matchInv)   invoice = matchInv[1].toUpperCase();

            await this.fetchRental(invoice);
        },

        // ── Fetch info produk dari QR produk ──────────────────────────────────
        async fetchProduct(productId) {
            this.result = null;
            this.error  = null;

            try {
                const res = await fetch(`/products/${productId}/scan-info`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? ''
                    }
                });

                if (!res.ok) {
                    // Fallback: redirect langsung ke halaman produk
                    window.location.href = `/products/${productId}`;
                    return;
                }

                const data = await res.json();

                // Tampilkan card info produk inline di halaman scan
                this.result = `
                    <div class="p-4 rounded-xl border-2 space-y-3" style="border-color: var(--primary); background: #FFFDF9">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: var(--primary)">
                                <i data-lucide="shirt" class="w-4 h-4 text-white"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-sm" style="color: var(--text-dark)">${data.name}</p>
                                <p class="text-xs" style="color: var(--text-soft)">${data.code ?? ''} · ${data.size ?? ''} · ${data.color ?? ''}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-2.5 rounded-lg" style="background: var(--bg-soft)">
                                <p class="text-xs" style="color: var(--text-soft)">Stok Tersedia</p>
                                <p class="text-xl font-bold font-playfair" style="color: ${data.stock_available > 0 ? '#10B981' : '#EF4444'}">${data.stock_available}</p>
                            </div>
                            <div class="p-2.5 rounded-lg" style="background: var(--bg-soft)">
                                <p class="text-xs" style="color: var(--text-soft)">Status</p>
                                <p class="text-sm font-semibold mt-1" style="color: ${data.status === 'available' ? '#10B981' : '#F59E0B'}">
                                    ${data.status === 'available' ? 'Tersedia' : data.status === 'rented' ? 'Sedang Disewa' : data.status}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between pt-1 border-t" style="border-color: var(--border)">
                            <p class="text-sm font-bold" style="color: var(--primary)">Rp ${new Intl.NumberFormat('id-ID').format(data.rental_price)}<span class="font-normal text-xs">/paket</span></p>
                            <a href="/products/${productId}" class="text-xs px-3 py-1.5 rounded-lg font-semibold"
                               style="background: var(--primary); color: #1E1A16">
                               Lihat Detail →
                            </a>
                        </div>
                    </div>`;

                // Re-init lucide icons untuk icon baru
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });

            } catch (err) {
                this.error = 'Gagal memuat info produk: ' + err.message;
            }
        },

        async searchManual() {
            if (!this.manualInvoice.trim()) return;
            await this.fetchRental(this.manualInvoice.trim().toUpperCase());
        },

        async fetchRental(invoice) {
            this.result = null;
            this.error = null;

            try {
                const res = await fetch(`/rentals/scan/${invoice}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    }
                });

                if (!res.ok) {
                    if (res.status === 404) {
                        this.error = `Invoice "${invoice}" tidak ditemukan dalam sistem.`;
                    } else {
                        this.error = `Terjadi kesalahan server (${res.status}). Coba buka langsung di browser.`;
                    }
                    return;
                }

                const parser = new DOMParser();
                const doc = parser.parseFromString(await res.text(), 'text/html');
                const content = doc.querySelector('#scan-result-content');
                this.result = content ? content.innerHTML :
                    `<p class="text-sm">Data ditemukan. <a href="/rentals/scan/${invoice}" class="text-amber-600 font-semibold">Lihat detail →</a></p>`;

                window.location.href = `/rentals/scan/${invoice}`;

            } catch (err) {
                this.error = 'Gagal memproses: ' + err.message;
            }
        }
    }
}
    </script>
@endpush