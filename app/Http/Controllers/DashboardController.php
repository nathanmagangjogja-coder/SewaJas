<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
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