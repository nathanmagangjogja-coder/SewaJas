<?php

namespace App\Http\Controllers;

use App\Models\Laundry;
use App\Models\Rental;
use App\Models\StatusHistory;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LaundryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('role:super_admin,admin_toko'),
        ];
    }

    // ─── Dashboard Laundry (index semua status) ───────────────────────────────

    public function index(Request $request)
    {
        $this->cleanupDeletedProducts();

        $status = $request->get('status', 'semua');

        $query = Laundry::with(['transaksi.customer', 'produk', 'diprosesByUser'])
            ->orderBy('created_at', 'asc');

        if ($status !== 'semua') {
            $query->where('status', $status);
        }

        $laundries = $query->paginate(15)->withQueryString();

        $stats = [
            'menunggu_laundry' => Laundry::menungguLaundry()->count(),
            'dalam_laundry'    => Laundry::dalamLaundry()->count(),
            'siap_disewakan'   => Laundry::siapDisewakan()->count(),
        ];

        return view('laundry.index', compact('laundries', 'stats', 'status'));
    }

    // ─── Daftar: Menunggu Laundry ─────────────────────────────────────────────

    public function menungguLaundry(Request $request)
    {   
        $this->cleanupDeletedProducts();
        $laundries = Laundry::menungguLaundry()
            ->with(['transaksi.customer', 'produk'])
            ->orderBy('dikembalikan_at', 'asc')
            ->paginate(15);

        $stats = $this->getStats();

        return view('laundry.menunggu', compact('laundries', 'stats'));
    }

    // ─── Daftar: Dalam Laundry ────────────────────────────────────────────────

    public function dalamLaundry(Request $request)
    {
        $this->cleanupDeletedProducts();
        $laundries = Laundry::dalamLaundry()
            ->with(['transaksi.customer', 'produk', 'diprosesByUser'])
            ->orderBy('mulai_laundry_at', 'asc')
            ->paginate(15);

        $stats = $this->getStats();

        return view('laundry.dalam', compact('laundries', 'stats'));
    }

    // ─── Daftar: Siap Disewakan ───────────────────────────────────────────────

    public function siapDisewakan(Request $request)
    {
        $this->cleanupDeletedProducts();
        $laundries = Laundry::siapDisewakan()
            ->with(['transaksi.customer', 'produk', 'diprosesByUser'])
            ->orderBy('selesai_laundry_at', 'desc')
            ->paginate(15);

        $stats = $this->getStats();

        return view('laundry.siap', compact('laundries', 'stats'));
    }

    // ─── Detail Laundry ───────────────────────────────────────────────────────

    public function show(Laundry $laundry)
    {
        $laundry->load(['transaksi.customer', 'produk', 'diprosesByUser', 'statusHistories.user']);

        $transaksiHistories = StatusHistory::with('user')
            ->where('model_type', Laundry::class)
            ->where('model_id', $laundry->id)
            ->orderBy('changed_at', 'desc')
            ->get();

        return view('laundry.show', compact('laundry', 'transaksiHistories'));
    }

    // ─── Mulai Laundry (menunggu → dalam) ────────────────────────────────────

    public function mulaiLaundry(Request $request, Laundry $laundry)
    {
        $request->validate([
            'catatan' => 'nullable|string|max:500',
        ]);

        if ($laundry->status !== Laundry::STATUS_MENUNGGU_LAUNDRY) {
            return back()->with('error', 'Status laundry tidak valid untuk operasi ini.');
        }

        DB::beginTransaction();
        try {
            $laundry->mulaiLaundry(Auth::user(), $request->catatan);

            // FIX #2: rental_status harus 'dalam_laundry', bukan 'menunggu_laundry'
            $laundry->transaksi->update(['rental_status' => Rental::STATUS_DALAM_LAUNDRY]);

            DB::commit();

            return redirect()
                ->route('laundry.dalam')
                ->with('success', "Laundry untuk {$laundry->produk->name} telah dimulai.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal mulai laundry: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan. Silakan coba lagi.');
        }
    }

    // ─── Batch Mulai Laundry ──────────────────────────────────────────────────

    public function batchMulaiLaundry(Request $request)
    {
        $request->validate([
            'ids'     => 'required|array',
            'ids.*'   => 'exists:laundries,id',
            'catatan' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $count = 0;
            $laundries = Laundry::whereIn('id', $request->ids)
                ->where('status', Laundry::STATUS_MENUNGGU_LAUNDRY)
                ->get();

            foreach ($laundries as $laundry) {
                $laundry->mulaiLaundry(Auth::user(), $request->catatan);

                // FIX #2: rental_status harus 'dalam_laundry'
                $laundry->transaksi->update(['rental_status' => Rental::STATUS_DALAM_LAUNDRY]);

                $count++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "$count item berhasil dipindah ke Dalam Laundry.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─── Selesai Laundry (dalam → siap disewakan) ────────────────────────────

    public function selesaiLaundry(Request $request, Laundry $laundry)
    {
        $request->validate([
            'catatan' => 'nullable|string|max:500',
        ]);

        if ($laundry->status !== Laundry::STATUS_DALAM_LAUNDRY) {
            return back()->with('error', 'Status laundry tidak valid untuk operasi ini.');
        }

        DB::beginTransaction();
        try {
            // Model::selesaiLaundry() sudah handle: increment stock + update product status
            $laundry->selesaiLaundry(Auth::user(), $request->catatan);

            // FIX #3: rental_status harus 'siap_disewakan', bukan 'returned'
            $laundry->transaksi->update(['rental_status' => Rental::STATUS_SIAP_DISEWAKAN]);

            DB::commit();

            return redirect()
                ->route('laundry.siap')
                ->with('success', "Jas {$laundry->produk->name} selesai laundry dan siap disewakan.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal selesai laundry: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan. Silakan coba lagi.');
        }
    }

    // ─── Batch Selesai Laundry ────────────────────────────────────────────────

    public function batchSelesaiLaundry(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'exists:laundries,id',
        ]);

        DB::beginTransaction();
        try {
            $count = 0;
            $laundries = Laundry::whereIn('id', $request->ids)
                ->where('status', Laundry::STATUS_DALAM_LAUNDRY)
                ->get();

            foreach ($laundries as $laundry) {
                // Model::selesaiLaundry() sudah handle: increment stock + update product status
                $laundry->selesaiLaundry(Auth::user());

                // FIX #3: rental_status harus 'siap_disewakan', bukan 'returned'
                $laundry->transaksi->update(['rental_status' => Rental::STATUS_SIAP_DISEWAKAN]);

                $count++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "$count item berhasil selesai laundry dan stok telah diperbarui.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─── Riwayat Status ───────────────────────────────────────────────────────

    public function riwayat(Request $request)
    {
        $histories = StatusHistory::with(['user'])
            ->where('model_type', Laundry::class)
            ->orderBy('changed_at', 'desc')
            ->paginate(20);

        return view('laundry.riwayat', compact('histories'));
    }

    // ─── Helper ───────────────────────────────────────────────

    private function cleanupDeletedProducts(): void
    {
        Laundry::doesntHave('produk')->each(function ($laundry) {

            StatusHistory::where('model_type', Laundry::class)
                ->where('model_id', $laundry->id)
                ->delete();

            $laundry->delete();
        });
    }

    private function getStats(): array
    {
        return [
            'menunggu_laundry' => Laundry::menungguLaundry()->count(),
            'dalam_laundry'    => Laundry::dalamLaundry()->count(),
            'siap_disewakan'   => Laundry::siapDisewakan()->count(),
        ];
    }
}