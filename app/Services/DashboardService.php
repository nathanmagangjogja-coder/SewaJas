<?php

namespace App\Services;

use App\Models\Rental;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardService
{
    // ─── Super Admin ───────────────────────────────────────────────────────────

public function getSuperAdminStats(): array
{
    $today = Carbon::today('Asia/Jakarta');

    return [
        'total_branches'     => Branch::where('is_active', true)->count(),
        'total_customers'    => Customer::count(),
        'active_rentals'     => Rental::whereIn('rental_status', ['active', 'overdue'])->count(),
        'overdue_rentals'    => Rental::where('rental_status', 'overdue')->count(),
        'month_revenue'      => Rental::whereMonth('rental_date', $today->month)
                                      ->whereYear('rental_date', $today->year)
                                      ->where('payment_status', 'paid')
                                      ->sum('total_amount'),

        'products_available' => Product::where('status', 'available')->count(),
        'products_rented'    => Product::where('status', 'rented')->count(),
        'today_rentals'      => Rental::whereDate('created_at', $today)->count(),
        'today_returns'      => Rental::whereDate('actual_return_date', $today)
                                      ->where('rental_status', 'returned')->count(),

        'monthly_chart'      => $this->getMonthlyChart(),
        'top_products'       => $this->getTopProducts(),
        'top_branches'       => $this->getTopBranches(),

        'total_rentals'      => Rental::count(),
        'total_revenue'      => Rental::where('payment_status', 'paid')->sum('total_amount'),
        'late_rentals'       => Rental::where('rental_status', 'overdue')->count(),
        'total_products'     => Product::count(),

        // ==========================================
        // TAMBAHAN BARU — 3 key untuk dashboard
        // ==========================================

        // 1. Total transaksi all-time (untuk card baru)
        'total_transactions' => Rental::count(),

        // 2. Distribusi status untuk donut chart
        'status_counts'      => [
            'active'    => Rental::where('rental_status', 'active')->count(),
            'overdue'   => Rental::where('rental_status', 'overdue')->count(),
            'returned'  => Rental::where('rental_status', 'returned')->count(),
            'cancelled' => Rental::where('rental_status', 'cancelled')->count(),
        ],

        // 3. Customer aktif untuk tabel dashboard (limit 5)
        'active_customers'   => Rental::with(['customer', 'items.product', 'branch'])
                                      ->whereIn('rental_status', ['active', 'overdue'])
                                      ->latest()
                                      ->limit(5)
                                      ->get(),

        // 4. Total piutang (belum lunas) — untuk card dashboard
        'outstanding_count'  => Rental::whereIn('payment_status', ['unpaid', 'partial'])
                                      ->whereNotIn('rental_status', ['cancelled'])
                                      ->count(),
        'outstanding_amount' => Rental::whereIn('payment_status', ['unpaid', 'partial'])
                                      ->whereNotIn('rental_status', ['cancelled'])
                                      ->selectRaw('COALESCE(SUM(total_amount - paid_amount), 0) as sisa')
                                      ->value('sisa') ?? 0,
    ];
}

    // ─── Admin Toko ──────────────────────────────────────────────────────────

    public function getAdminStats(?int $branchId): array
    {
        $today = Carbon::today();
        $query = fn() => Rental::when($branchId, fn($q) => $q->where('branch_id', $branchId));

        $dueToday = $query()->whereDate('return_due_date', $today)
                            ->whereNotIn('rental_status', ['returned', 'cancelled'])
                            ->with(['customer'])
                            ->get();

        return [
            'today_rentals'      => $query()->whereDate('rental_date', $today)->count(),
            'today_returns'      => $query()->whereDate('actual_return_date', $today)
                                            ->where('rental_status', 'returned')->count(),
            'pending_payment'    => $query()->where('payment_status', 'unpaid')->count(),
            'late_rentals'       => $query()->where('rental_status', 'overdue')->count(),             // ✅ fix
            'overdue_rentals'    => $query()->where('rental_status', 'overdue')->count(),             // ✅ fix
            'monthly_revenue'    => $query()->whereMonth('rental_date', $today->month)
                                            ->whereYear('rental_date', $today->year)
                                            ->where('payment_status', 'paid')
                                            ->sum('total_amount'),
            'active_rentals'     => $query()->whereIn('rental_status', ['active', 'overdue'])->count(), // ✅ fix
            'due_today'          => $dueToday,
            'due_today_count'    => $dueToday->count(),
            'products_available' => Product::when($branchId, fn($q) =>
                                        $q->where('branch_id', $branchId))
                                        ->where('status', 'available')->count(),
            'products_rented'    => $query()->whereIn('rental_status', ['active', 'overdue'])->count(), // ✅ fix
            'recent_transactions'=> $query()->with(['customer', 'items.product'])
                                            ->latest()->take(10)->get(),
            'monthly_chart'      => $this->getBranchMonthlyChart($branchId),
        ];
    }

    // ─── Sales ───────────────────────────────────────────────────────────────

    public function getSalesStats(?int $branchId): array
    {
        $today   = Carbon::today();
        $uid     = auth()->id();
        $query   = fn() => Rental::when($branchId, fn($q) => $q->where('branch_id', $branchId));
        $myQuery = fn() => $query()->where('created_by', $uid);

        $dueToday = $query()->whereDate('return_due_date', $today)
                            ->whereNotIn('rental_status', ['returned', 'cancelled'])
                            ->with(['customer', 'items'])
                            ->get();

        // Performa 7 hari terakhir (untuk mini bar chart di dashboard)
        $weeklyActivity = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $weeklyActivity[] = [
                'label' => $date->translatedFormat('D'),
                'date'  => $date->format('d/m'),
                'count' => $myQuery()->whereDate('rental_date', $date)->count(),
            ];
        }

        // Produk yang paling sering saya sewakan bulan ini
        $topProductsMine = DB::table('rental_items')
            ->join('rentals', 'rentals.id', '=', 'rental_items.rental_id')
            ->where('rentals.created_by', $uid)
            ->when($branchId, fn($q) => $q->where('rentals.branch_id', $branchId))
            ->whereMonth('rentals.rental_date', $today->month)
            ->whereYear('rentals.rental_date', $today->year)
            ->select('rental_items.product_name', DB::raw('SUM(rental_items.quantity) as total_qty'))
            ->groupBy('rental_items.product_name')
            ->orderByDesc('total_qty')
            ->limit(3)
            ->get();

        return [
            'today_rentals'      => $query()->whereDate('rental_date', $today)->count(),
            'today_returns'      => $query()->whereDate('actual_return_date', $today)->count(),
            'due_today'          => $dueToday,
            'due_today_count'    => $dueToday->count(),
            'my_rentals_today'   => $myQuery()->whereDate('rental_date', $today)->count(),
            'my_rentals_month'   => $myQuery()->whereMonth('rental_date', $today->month)
                                                       ->whereYear('rental_date', $today->year)->count(),
            'new_customers_month'=> Customer::when($branchId, fn($q) => $q->where('branch_id', $branchId))
                                            ->whereMonth('created_at', $today->month)
                                            ->whereYear('created_at', $today->year)->count(),
            'weekly_activity'    => $weeklyActivity,
            'top_products_mine'  => $topProductsMine,
            'recent_rentals'     => $query()->with(['customer'])->latest()->take(5)->get(),
            'my_transactions'    => $myQuery()->whereDate('rental_date', $today)
                                          ->with(['customer'])->latest()->get(),
        ];
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function getMonthlyChart(): array
    {
        $months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

        $data = Rental::select(
                DB::raw('MONTH(rental_date) as month_num'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->whereYear('rental_date', now()->year)
            ->groupBy(DB::raw('MONTH(rental_date)'))
            ->orderBy('month_num')
            ->get()
            ->keyBy('month_num');

        return collect(range(1, 12))->map(fn($m) => [
            'month'   => $months[$m - 1],
            'count'   => $data->get($m)?->count ?? 0,
            'revenue' => $data->get($m)?->revenue ?? 0,
        ])->values()->toArray();
    }

    private function getBranchMonthlyChart(?int $branchId): array
    {
        $months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

        $data = Rental::when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->select(
                DB::raw('MONTH(rental_date) as month_num'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->whereYear('rental_date', now()->year)
            ->groupBy(DB::raw('MONTH(rental_date)'))
            ->orderBy('month_num')
            ->get()
            ->keyBy('month_num');

        return collect(range(1, 12))->map(fn($m) => [
            'month'   => $months[$m - 1],
            'count'   => $data->get($m)?->count ?? 0,
            'revenue' => $data->get($m)?->revenue ?? 0,
        ])->values()->toArray();
    }

    private function getTopProducts(): array
    {
        return DB::table('rental_items')
            ->join('products', 'rental_items.product_id', '=', 'products.id')
            ->select('products.name', DB::raw('COUNT(*) as total_rented'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_rented')
            ->limit(5)
            ->get()
            ->toArray();
    }

    private function getTopBranches(): array
    {
        return Branch::withCount('rentals')
            ->withSum(['rentals' => fn($q) => $q->where('payment_status', 'paid')], 'total_amount')
            ->where('is_active', true)
            ->orderByDesc('rentals_count')
            ->limit(5)
            ->get()
            ->toArray();
    }
}