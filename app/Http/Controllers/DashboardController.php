<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Models\Rental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboardService) {}

    public function index()
    {
        $user = Auth::user();

        return match($user->role) {
            'super_admin' => $this->superAdminDashboard(),
            'admin_toko'  => $this->adminDashboard(),
            'sales'       => $this->salesDashboard(),
            default       => abort(403),
        };
    }

    private function superAdminDashboard()
    {
        $stats = $this->dashboardService->getSuperAdminStats();

        // Total piutang (tagihan belum lunas) di semua cabang
        $stats['total_piutang'] = (float) DB::table('rentals')
            ->where('payment_status', '!=', 'paid')
            ->sum(DB::raw('total_amount - paid_amount'));
        $stats['piutang_count'] = DB::table('rentals')
            ->where('payment_status', '!=', 'paid')
            ->count();

        // Performa sales: cabang -> sales -> skor (jumlah customer unik yang
        // dilayani) + daftar nama customer, untuk section "Performa Sales
        // per Cabang" di dashboard.
        $stats['sales_performance'] = $this->getSalesPerformance();

        // Build chartData (monthly last 6 months) and sparklines placeholders
        $chartData = ['monthly' => ['labels' => [], 'revenue' => [], 'count' => []], 'weekly' => [], 'daily' => []];

        // last 6 months labels (e.g. Jan 2026)
        for ($i = 5; $i >= 0; $i--) {
            $dt = Carbon::now()->subMonths($i);
            $chartData['monthly']['labels'][] = $dt->translatedFormat('M Y');

            $revenue = DB::table('rentals')
                ->whereYear('created_at', $dt->year)
                ->whereMonth('created_at', $dt->month)
                ->sum('total_amount');
            $count = DB::table('rentals')
                ->whereYear('created_at', $dt->year)
                ->whereMonth('created_at', $dt->month)
                ->count();

            $chartData['monthly']['revenue'][] = (int) $revenue;
            $chartData['monthly']['count'][] = (int) $count;
        }

        // weekly & daily: build basic structures (detailed grouping helper methods can be added)
        $chartData['weekly'] = $this->buildWeeklyData();
        $chartData['daily']  = $this->buildDailyData();

        $sparklines = [
            'cabang'       => [0,0,0,0,0,0,0],
            'customer'     => [0,0,0,0,0,0,0],
            'aktif'        => [0,0,0,0,0,0,0],
            'transaksi'    => [0,0,0,0,0,0,0],
            'pendapatan'   => [0,0,0,0,0,0,0],
            'stok'         => [0,0,0,0,0,0,0],
            'sedang'       => [0,0,0,0,0,0,0],
            'sewa_today'   => [0,0,0,0,0,0,0],
            'kembali_today'=> [0,0,0,0,0,0,0],
        ];

        return view('dashboard.super-admin', compact('stats','chartData','sparklines'));
    }

    private function adminDashboard()
    {
        $user  = Auth::user();
        $stats = $this->dashboardService->getAdminStats($user->branch_id);

        // Total piutang (tagihan belum lunas) khusus cabang ini
        $stats['total_piutang'] = (float) DB::table('rentals')
            ->where('branch_id', $user->branch_id)
            ->where('payment_status', '!=', 'paid')
            ->sum(DB::raw('total_amount - paid_amount'));
        $stats['piutang_count'] = DB::table('rentals')
            ->where('branch_id', $user->branch_id)
            ->where('payment_status', '!=', 'paid')
            ->count();

        // pass empty chartData and sparklines so admin view can render without errors
        $chartData = ['monthly' => ['labels' => [], 'revenue' => [], 'count' => []], 'weekly' => [], 'daily' => []];
        $sparklines = [
            'cabang'     => [0,0,0,0,0,0,0],
            'customer'   => [0,0,0,0,0,0,0],
            'aktif'      => [0,0,0,0,0,0,0],
            'transaksi'  => [0,0,0,0,0,0,0],
            'pendapatan' => [0,0,0,0,0,0,0],
        ];
        return view('dashboard.admin', compact('stats','chartData','sparklines'));
    }

    private function salesDashboard()
    {
        $user  = Auth::user();
        $stats = $this->dashboardService->getSalesStats($user->branch_id);
        $chartData = ['monthly' => ['labels' => [], 'revenue' => [], 'count' => []], 'weekly' => [], 'daily' => []];
        $sparklines = [
            'cabang'     => [0,0,0,0,0,0,0],
            'customer'   => [0,0,0,0,0,0,0],
            'aktif'      => [0,0,0,0,0,0,0],
            'transaksi'  => [0,0,0,0,0,0,0],
            'pendapatan' => [0,0,0,0,0,0,0],
        ];
        return view('dashboard.sales', compact('stats','chartData','sparklines'));
    }

    // Performa sales dikelompokkan per cabang, dipecah jadi 2 kategori aksi:
    //   1. "Menerima Pesanan"      -> dari rentals.created_by (staf yang membuat transaksi)
    //   2. "Menerima Pengembalian" -> dari rentals.returned_by (staf yang MEMPROSES
    //      PENGEMBALIAN barang — lihat relasi Rental::returnedBy(), diisi otomatis
    //      di RentalService::finalizeReturn()).
    // Angka utama tiap kartu = JUMLAH TRANSAKSI/LAYANAN (dihitung tiap kejadian),
    // bukan jumlah customer unik — customer unik cuma keterangan tambahan.
    private function getSalesPerformance()
    {
        $rentals = Rental::query()
            ->whereHas('customer')
            ->where(fn($q) => $q->whereNotNull('created_by')->orWhereNotNull('returned_by'))
            ->with(['createdBy:id,name', 'returnedBy:id,name', 'branch:id,name,code', 'customer:id,name'])
            ->get(['id', 'branch_id', 'created_by', 'returned_by', 'customer_id']);

        // $data[branch_id] = ['branch' => Branch|null, 'users' => [user_id => [...]]]
        $data = [];

        // ── Kategori 1: Menerima Pesanan ──────────────────────────────────
        foreach ($rentals as $rental) {
            $branchId = $rental->branch_id;
            $userId   = $rental->created_by;
            if (!$branchId || !$userId) continue;

            $data[$branchId]['branch'] ??= $rental->branch;
            $data[$branchId]['users'][$userId]['name'] ??= $rental->createdBy->name ?? 'Tidak diketahui';
            $data[$branchId]['users'][$userId]['orders_received'] =
                ($data[$branchId]['users'][$userId]['orders_received'] ?? 0) + 1;

            if ($rental->customer) {
                $data[$branchId]['users'][$userId]['customers_received'][$rental->customer->id] = $rental->customer->name;
            }
        }

        // ── Kategori 2: Menerima Pengembalian ─────────────────────────────
        foreach ($rentals as $rental) {
            $branchId = $rental->branch_id;
            $userId   = $rental->returned_by;
            if (!$branchId || !$userId) continue;

            $data[$branchId]['branch'] ??= $rental->branch;
            $data[$branchId]['users'][$userId]['name'] ??= $rental->returnedBy->name ?? 'Tidak diketahui';
            $data[$branchId]['users'][$userId]['returns_processed'] =
                ($data[$branchId]['users'][$userId]['returns_processed'] ?? 0) + 1;

            if ($rental->customer) {
                $data[$branchId]['users'][$userId]['customers_returned'][$rental->customer->id] = $rental->customer->name;
            }
        }

        return collect($data)
            ->map(function ($branchData) {
                $branch = $branchData['branch'] ?? null;

                $sales = collect($branchData['users'] ?? [])
                    ->map(function ($u) {
                        $received = collect($u['customers_received'] ?? []);
                        $returned = collect($u['customers_returned'] ?? []);

                        return [
                            'sales_name'          => $u['name'] ?? 'Tidak diketahui',
                            'orders_received'     => $u['orders_received'] ?? 0,
                            'returns_processed'   => $u['returns_processed'] ?? 0,
                            'customers_received_count' => $received->count(),
                            'customers_received'  => $received->values(),
                            'customers_returned_count' => $returned->count(),
                            'customers_returned'  => $returned->values(),
                        ];
                    })
                    ->sortByDesc(fn($s) => $s['orders_received'] + $s['returns_processed'])
                    ->values();

                return [
                    'branch_name' => $branch->name ?? 'Cabang Tidak Diketahui',
                    'branch_code' => $branch->code ?? '-',
                    'sales'       => $sales,
                ];
            })
            ->sortByDesc(fn($b) => $b['sales']->sum('orders_received') + $b['sales']->sum('returns_processed'))
            ->values();
    }

    // Basic weekly grouping placeholder — returns an empty structure keyed by YYYY-MM
    private function buildWeeklyData()
    {
        // A full implementation would group rentals by ISO week per month.
        return [];
    }

    // Basic daily grouping placeholder — returns an empty structure keyed by YYYY-MM-Wx
    private function buildDailyData()
    {
        // A full implementation would return daily breakdowns per week key.
        return [];
    }
}