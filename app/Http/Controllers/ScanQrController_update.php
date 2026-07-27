<?php

// ============================================================
// MODIFIKASI METHOD scanQR() / processReturn() YANG SUDAH ADA
// Di controller QR/Pengembalian Anda
// ============================================================
// Tambahkan use statement:
// use App\Models\Laundry;
// use App\Models\StatusHistory;
// ============================================================

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\Laundry;
use App\Models\StatusHistory;
use App\Models\Product; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScanQrController extends Controller
{
    /**
     * ============================================================
     * GANTI method processReturn() / handleScan() yang sudah ada
     * dengan versi baru ini yang mengintegrasikan laundry flow
     * ============================================================
     */
    public function processReturn(Request $request)
        {
            $request->validate([
                'qr_code' => 'required|string',
            ]);

            // 1. CLEANING INPUT
            // Jika input adalah URL lengkap, ambil bagian paling belakang sebagai kode unik/ID
            $input = trim($request->qr_code);
            $cleanCode = $input;
            
            if (filter_var($input, FILTER_VALIDATE_URL)) {
                // Mengambil bagian setelah slash terakhir (misal: .../products/3 -> 3)
                $cleanCode = basename(parse_url($input, PHP_URL_PATH));
            }

            // 2. MENCARI PRODUK TERLEBIH DAHULU (Prioritas Barcode/ID)
            // Kita cari produk dulu karena lebih spesifik
            $produk = Product::where('code', $cleanCode)
                            ->orWhere('id', $cleanCode)
                            ->first();
                            
            if ($produk) {
                return response()->json([
                    'success' => true,
                    'type'    => 'product',
                    'message' => 'Produk ditemukan.',
                    'data'    => [
                        'nama'   => $produk->nama,
                        'status' => $produk->status,
                        'stok'   => $produk->stock_available,
                        'url'    => route('products.show', $produk->id)
                    ]
                ]);
            }

            // 3. MENCARI TRANSAKSI (Jika bukan produk)
            $transaksi = Transaksi::where('kode_qr', $cleanCode)
                ->orWhere('kode_transaksi', $cleanCode)
                ->with(['produk', 'customer'])
                ->first();

            if ($transaksi) {
                // Validasi status transaksi
                $statusBisaKembali = ['disewa', 'menunggu_kembali', Transaksi::STATUS_DISEWA, Transaksi::STATUS_MENUNGGU_KEMBALI];
                
                if (!in_array($transaksi->status, $statusBisaKembali)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Transaksi tidak dalam status aktif sewa. Status: ' . $transaksi->status,
                    ], 422);
                }

                DB::beginTransaction();
                try {
                    $laundry = $transaksi->prosesKembaliDanBuatLaundry(Auth::user());
                    DB::commit();
                    
                    return response()->json([
                        'success'    => true,
                        'type'       => 'transaction',
                        'message'    => "Pengembalian berhasil! Jas masuk antrian laundry.",
                        'transaksi'  => [
                            'id'            => $transaksi->id,
                            'kode'          => $transaksi->kode_transaksi,
                            'customer'      => $transaksi->customer->nama ?? '-',
                            'produk'        => $transaksi->produk->nama ?? '-',
                            'status_baru'   => 'menunggu_laundry',
                        ],
                        'redirect'   => route('laundry.menunggu'),
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Gagal proses QR return: ' . $e->getMessage());
                    return response()->json(['success' => false, 'message' => 'Kesalahan server.'], 500);
                }
            }

            // 4. JIKA TIDAK DITEMUKAN SAMA SEKALI
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan (Input: ' . $cleanCode . ')',
            ], 404);
        }

        DB::beginTransaction();
        try {
            // ─── Proses pengembalian + buat laundry otomatis ─────────────────
            $laundry = $transaksi->prosesKembaliDanBuatLaundry(Auth::user());

            DB::commit();

            return response()->json([
                'success'    => true,
                'message'    => "Pengembalian berhasil! Jas masuk antrian laundry.",
                'transaksi'  => [
                    'id'            => $transaksi->id,
                    'kode'          => $transaksi->kode_transaksi,
                    'customer'      => $transaksi->customer->nama ?? '-',
                    'produk'        => $transaksi->produk->nama ?? '-',
                    'status_baru'   => 'menunggu_laundry',
                    'status_label'  => 'Menunggu Laundry',
                ],
                'laundry_id' => $laundry->id,
                'redirect'   => route('laundry.menunggu'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal proses QR return: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses pengembalian.',
            ], 500);
        }
    }

    /**
     * ============================================================
     * Jika Anda menggunakan blade + redirect (bukan API JSON),
     * gunakan versi ini sebagai gantinya:
     * ============================================================
     */
    public function processReturnBlade(Request $request)
    {
        $request->validate([
            'qr_code' => 'required|string',
        ]);

        $transaksi = Transaksi::where('kode_qr', $request->qr_code)
            ->orWhere('kode_transaksi', $request->qr_code)
            ->with(['produk'])
            ->firstOrFail();

        $statusBisaKembali = ['disewa', 'menunggu_kembali'];

        if (!in_array($transaksi->status, $statusBisaKembali)) {
            return back()->with('error', 'Transaksi tidak dalam status aktif sewa.');
        }

        DB::beginTransaction();
        try {
            $laundry = $transaksi->prosesKembaliDanBuatLaundry(Auth::user());
            DB::commit();

            return redirect()
                ->route('laundry.menunggu')
                ->with('success', "Pengembalian berhasil! Jas \"{$transaksi->produk->nama}\" masuk antrian laundry.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
