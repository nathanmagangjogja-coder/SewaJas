<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Rental;
use App\Models\RentalPackage;
use App\Models\Product;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function resolvedBranchId(Request $request): ?int
    {
        $user = Auth::user();
        if ($user->isSuperAdmin()) {
            $id = $request->input('branch_id');
            if ($id) {
                abort_unless(Branch::where('id', $id)->exists(), 404);
                return (int) $id;
            }
            return null;
        }
        return (int) $user->branch_id;
    }

    // REFACTOR: filter branch & rentang tanggal sebelumnya ditulis ulang
    // manual di hampir setiap method laporan (8+ kali). Sekarang delegasi ke
    // scope forBranch()/rentalDateBetween() di model Rental — perilaku sama
    // persis, hanya sumber logikanya dipusatkan di satu tempat.
    private function baseRentalQuery(Request $request, ?int $branchId)
    {
        return Rental::with(['branch', 'customer', 'createdBy', 'items', 'payments', 'package'])
            ->forBranch($branchId)
            ->rentalDateBetween($request->start_date, $request->end_date);
    }

    private function sharedViewData(Request $request): array
    {
        $user             = Auth::user();
        $isSuperAdmin     = $user->isSuperAdmin();
        $branches         = $isSuperAdmin ? Branch::orderBy('name')->get() : collect();
        $selectedBranchId = $this->resolvedBranchId($request);
        return compact('isSuperAdmin', 'branches', 'selectedBranchId');
    }

    // ─── 1. Laporan Pendapatan ────────────────────────────────────────────────

    public function revenue(Request $request)
    {
        $branchId = $this->resolvedBranchId($request);
        $shared   = $this->sharedViewData($request);

        $query = Rental::selectRaw('
                DATE(rental_date)  AS date,
                SUM(subtotal)      AS total_subtotal,
                SUM(discount)      AS total_discount,
                SUM(late_fee)      AS total_late_fee,
                SUM(total_amount)  AS total_revenue,
                COUNT(*)           AS total_rentals,
                SUM(paid_amount)   AS total_paid,
                SUM(total_amount - paid_amount) AS total_outstanding
            ')
            ->where('payment_status', Rental::PAYMENT_PAID)
            ->forBranch($branchId)
            ->rentalDateBetween($request->start_date, $request->end_date);

        $revenueData = $query->groupByRaw('DATE(rental_date)')->orderBy('date', 'desc')->get();

        // Pendapatan per paket
        $byPackage = Rental::selectRaw('
                package_id,
                COUNT(*) as count,
                SUM(total_amount) as revenue,
                SUM(late_fee) as late_fees
            ')
            ->where('payment_status', Rental::PAYMENT_PAID)
            ->forBranch($branchId)
            ->rentalDateBetween($request->start_date, $request->end_date)
            ->groupBy('package_id')
            ->with('package')
            ->get();

        return view('reports.revenue', array_merge($shared, [
            'revenueData'    => $revenueData,
            'totalRevenue'   => $revenueData->sum('total_revenue'),
            'totalLateFee'   => $revenueData->sum('total_late_fee'),
            'totalDiscount'  => $revenueData->sum('total_discount'),
            'totalRentals'   => $revenueData->sum('total_rentals'),
            'byPackage'      => $byPackage,
        ]));
    }

    // ─── 2. Laporan Transaksi ────────────────────────────────────────────────

    public function transactions(Request $request)
    {
        $branchId = $this->resolvedBranchId($request);
        $shared   = $this->sharedViewData($request);

        $search   = $request->search;
        $status   = $request->status;
        $dateFrom = $request->date_from;
        $dateTo   = $request->date_to;
        $pkgId    = $request->package_id;

        $query = Rental::with(['customer', 'branch', 'createdBy', 'items', 'payments', 'package'])
            ->forBranch($branchId)
            ->rentalDateBetween($dateFrom, $dateTo);
        if ($search) {
            $query->where(fn($q) => $q
                ->where('invoice_number', 'like', "%{$search}%")
                ->orWhereHas('customer', fn($c) => $c
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")));
        }
        if ($status) $query->where('rental_status', $status);
        if ($pkgId)  $query->where('package_id', $pkgId);

        $rentals = $query->latest('rental_date')->paginate(25)->withQueryString();

        // Summary
        $baseQuery = Rental::query()
            ->forBranch($branchId)
            ->rentalDateBetween($dateFrom, $dateTo);

        $summary = [
            'total'       => (clone $baseQuery)->count(),
            'pending'     => (clone $baseQuery)->where('rental_status', 'waiting')->count(),
            'active'      => (clone $baseQuery)->whereIn('rental_status', ['active', 'overdue'])->count(),
            'completed'   => (clone $baseQuery)->whereIn('rental_status', ['returned', 'siap_disewakan'])->count(),
            'cancelled'   => (clone $baseQuery)->where('rental_status', 'cancelled')->count(),
            'total_nilai' => (clone $baseQuery)->whereIn('rental_status', ['returned', 'siap_disewakan'])->sum('total_amount'),
            // Piutang: belum lunas
            'outstanding' => (clone $baseQuery)
                                ->whereIn('payment_status', ['unpaid', 'partial'])
                                ->whereNotIn('rental_status', ['cancelled'])
                                ->selectRaw('COALESCE(SUM(total_amount - paid_amount), 0) as sisa')
                                ->value('sisa') ?? 0,
        ];

        $statuses = Rental::statusLabels();
        $packages = RentalPackage::active()->get();

        return view('reports.transactions', array_merge($shared, compact(
            'rentals', 'summary', 'statuses', 'packages',
            'search', 'status', 'dateFrom', 'dateTo', 'pkgId'
        )));
    }

    // ─── 3. Laporan Stok ─────────────────────────────────────────────────────

    public function stock(Request $request)
    {
        $branchId = $this->resolvedBranchId($request);
        $shared   = $this->sharedViewData($request);

        $search   = $request->search;
        $category = $request->category;
        $stockStatus = $request->stock_status; // 'available','rented','low','out'

        $query = Product::with(['branch', 'category'])->forBranch($branchId);
        if ($search) {
            $query->where(fn($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"));
        }
        if ($category) $query->where('category_id', $category);
        if ($stockStatus === 'low')       $query->where('stock_available', '>', 0)->where('stock_available', '<=', 2);
        elseif ($stockStatus === 'out')   $query->where('stock_available', 0);
        elseif ($stockStatus === 'available') $query->where('status', 'available');
        elseif ($stockStatus === 'rented')    $query->where('status', 'rented');

        $baseQuery = Product::forBranch($branchId);

        $products      = $query->orderBy('name')->paginate(25)->withQueryString();
        $categories    = \App\Models\Category::orderBy('name')->get();

        $totalRented = (clone $baseQuery)
            ->whereColumn('stock_available', '<', 'stock_total')
            ->sum(DB::raw('stock_total - stock_available'));

        return view('reports.stock', array_merge($shared, [
            'products'       => $products,
            'totalProducts'  => (clone $baseQuery)->count(),
            'totalStock'     => (clone $baseQuery)->sum('stock_total'),
            'totalAvail'     => (clone $baseQuery)->sum('stock_available'),
            'totalRented'    => $totalRented,
            'activeRentals'  => $totalRented, // alias untuk kompatibilitas view lama
            'outOfStock'     => (clone $baseQuery)->where('stock_available', 0)->count(),
            'lowStock'       => (clone $baseQuery)->where('stock_available', '>', 0)->where('stock_available', '<=', 2)->count(),
            'categories'     => $categories,
            'search'         => $search,
            'category'       => $category,
            'stockStatus'    => $stockStatus,
        ]));
    }

    // ─── 4. Laporan Pengembalian (DIPERLUAS) ──────────────────────────────────

    public function returns(Request $request)
    {
        $branchId = $this->resolvedBranchId($request);
        $shared   = $this->sharedViewData($request);

        $dateFrom = $request->date_from;
        $dateTo   = $request->date_to;

        $base = Rental::with(['customer', 'branch', 'createdBy', 'items', 'package', 'returnRecord'])
            ->forBranch($branchId);

        // ── Summary ───────────────────────────────────────────────────────────
        $summary = [
            'returned'         => (clone $base)->whereIn('rental_status', ['returned', 'siap_disewakan', 'dalam_laundry', 'menunggu_laundry'])->count(),
            'due_today'        => (clone $base)->whereIn('rental_status', ['active', 'overdue'])->whereDate('return_due_date', today())->count(),
            'overdue'          => (clone $base)->where('rental_status', 'overdue')->count(),
            'total_late_fee'   => (clone $base)->whereIn('rental_status', ['returned', 'siap_disewakan', 'menunggu_laundry', 'dalam_laundry'])->sum('late_fee'),
            'total_late_count' => (clone $base)->whereIn('rental_status', ['returned', 'siap_disewakan', 'menunggu_laundry', 'dalam_laundry'])->where('late_fee', '>', 0)->count(),
        ];

        // ── Terlambat (overdue) ───────────────────────────────────────────────
        $overdue = (clone $base)
            ->where('rental_status', 'overdue')
            ->orderBy('return_due_date')
            ->get()
            ->map(function ($r) {
                $lateDays     = $r->live_late_days;
                $estimatedFee = $r->live_late_fee;
                $r->live_late_days      = $lateDays;
                $r->estimated_late_fee  = $estimatedFee;
                return $r;
            });

        // ── Jatuh tempo hari ini ──────────────────────────────────────────────
        $dueToday = (clone $base)
            ->whereIn('rental_status', ['active', 'overdue'])
            ->whereDate('return_due_date', today())
            ->orderBy('return_due_date')
            ->get();

        // ── Riwayat pengembalian ──────────────────────────────────────────────
        $returnedQuery = (clone $base)
            ->whereIn('rental_status', ['returned', 'siap_disewakan', 'menunggu_laundry', 'dalam_laundry']);
        if ($dateFrom) $returnedQuery->whereDate('actual_return_date', '>=', $dateFrom);
        if ($dateTo)   $returnedQuery->whereDate('actual_return_date', '<=', $dateTo);

        $returned = $returnedQuery->latest('actual_return_date')->paginate(25)->withQueryString();

        // ── Denda per paket (statistik) ───────────────────────────────────────
        $lateByPackage = Rental::selectRaw('
                package_id,
                COUNT(*) as total_rentals,
                SUM(CASE WHEN late_fee > 0 THEN 1 ELSE 0 END) as late_count,
                SUM(late_fee) as total_late_fee,
                AVG(overdue_days) as avg_late_days
            ')
            ->whereIn('rental_status', ['returned', 'siap_disewakan', 'menunggu_laundry', 'dalam_laundry'])
            ->forBranch($branchId)
            ->groupBy('package_id')
            ->with('package')
            ->get();

        return view('reports.returns', array_merge($shared, compact(
            'summary', 'overdue', 'dueToday', 'returned',
            'lateByPackage', 'dateFrom', 'dateTo'
        )));
    }

    // ─── 5. Laporan Piutang ───────────────────────────────────────────────────

    public function outstanding(Request $request)
    {
        $branchId = $this->resolvedBranchId($request);
        $shared   = $this->sharedViewData($request);

        // NOTE: model Rental sudah punya scopeOutstanding() persis untuk kondisi
        // ini, tapi sebelumnya tidak dipakai di sini (kondisinya ditulis ulang
        // manual). Disatukan sekarang.
        $query = Rental::with(['customer', 'branch', 'package', 'payments'])
            ->outstanding()
            ->forBranch($branchId)
            ->orderBy('rental_date', 'desc');

        $rentals = $query->paginate(20)->withQueryString();

        $statsBase = Rental::outstanding()->forBranch($branchId);

        $stats = [
            'total_count'   => (clone $statsBase)->count(),
            'total_nilai'   => (clone $statsBase)->selectRaw('COALESCE(SUM(total_amount - paid_amount), 0) as sisa')->value('sisa') ?? 0,
            'unpaid_count'  => (clone $statsBase)->where('payment_status', 'unpaid')->count(),
            'partial_count' => (clone $statsBase)->where('payment_status', 'partial')->count(),
        ];

        return view('reports.outstanding', array_merge($shared, compact('rentals', 'stats')));
    }

    // ─── 6. Export CSV ───────────────────────────────────────────────────────

    public function exportExcel(Request $request)
    {
        $branchId = $this->resolvedBranchId($request);
        $rentals  = $this->baseRentalQuery($request, $branchId)->latest('rental_date')->get();

        $branchLabel = $branchId
            ? (Branch::find($branchId)?->name ?? "cabang-{$branchId}")
            : 'semua-cabang';

        $filename = 'laporan-rental-' . str($branchLabel)->slug() . '-' . now()->format('Ymd') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($rentals) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'No. Invoice', 'Paket', 'Durasi (hari)', 'Tanggal Rental',
                'Jatuh Tempo', 'Tgl Kembali Aktual', 'Cabang', 'Customer', 'HP Customer',
                'Status Rental', 'Status Bayar',
                'Subtotal', 'Diskon', 'Denda (Rp)', 'Total', 'Sudah Dibayar', 'Sisa',
                'Hari Terlambat', 'Denda%/hari', 'Dibuat Oleh',
            ], ';');

            foreach ($rentals as $r) {
                fputcsv($handle, [
                    $r->invoice_number,
                    $r->package?->name ?? '-',
                    $r->duration_days,
                    $r->rental_date?->format('d/m/Y'),
                    $r->return_due_date?->format('d/m/Y'),
                    $r->actual_return_date?->format('d/m/Y') ?? '-',
                    $r->branch?->name,
                    $r->customer?->name,
                    $r->customer?->phone ?? '-',
                    $r->status_label,
                    $r->payment_status_label,
                    $r->subtotal,
                    $r->discount,
                    $r->late_fee,
                    $r->total_amount,
                    $r->paid_amount,
                    $r->remaining_amount,
                    $r->overdue_days ?? 0,
                    $r->package?->penalty_percent ?? '-',
                    $r->createdBy?->name,
                ], ';');
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ─── 7. Export PDF ───────────────────────────────────────────────────────

    public function exportPdf(Request $request)
    {
        $branchId     = $this->resolvedBranchId($request);
        $rentals      = $this->baseRentalQuery($request, $branchId)->latest('rental_date')->get();
        $totalRevenue = $rentals->where('payment_status', Rental::PAYMENT_PAID)->sum('total_amount');
        $totalLateFee = $rentals->sum('late_fee');
        $branchName   = $branchId ? (Branch::find($branchId)?->name ?? "Cabang #{$branchId}") : 'Semua Cabang';

        $pdf = Pdf::loadView('reports.pdf.transactions', [
            'rentals'      => $rentals,
            'totalRevenue' => $totalRevenue,
            'totalLateFee' => $totalLateFee,
            'branchName'   => $branchName,
            'startDate'    => $request->start_date,
            'endDate'      => $request->end_date,
            'generatedAt'  => now()->format('d/m/Y H:i'),
            'generatedBy'  => Auth::user()->name,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-' . str($branchName)->slug() . '-' . now()->format('Ymd') . '.pdf');
    }

    // ─── 8. Export PDF Pengembalian ───────────────────────────────────────────

    public function exportReturnsPdf(Request $request)
    {
        $branchId   = $this->resolvedBranchId($request);
        $branchName = $branchId ? (Branch::find($branchId)?->name ?? "Cabang #{$branchId}") : 'Semua Cabang';

        $rentals = Rental::with(['customer', 'package', 'returnRecord'])
            ->whereIn('rental_status', ['returned', 'siap_disewakan', 'menunggu_laundry', 'dalam_laundry'])
            ->forBranch($branchId)
            ->when($request->date_from, fn($q) => $q->whereDate('actual_return_date', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('actual_return_date', '<=', $request->date_to))
            ->latest('actual_return_date')
            ->get();

        $pdf = Pdf::loadView('reports.pdf.returns', [
            'rentals'     => $rentals,
            'branchName'  => $branchName,
            'dateFrom'    => $request->date_from,
            'dateTo'      => $request->date_to,
            'generatedAt' => now()->format('d/m/Y H:i'),
            'totalFee'    => $rentals->sum('late_fee'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-pengembalian-' . now()->format('Ymd') . '.pdf');
    }
}