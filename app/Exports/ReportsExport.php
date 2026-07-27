<?php

namespace App\Exports;

use App\Models\Rental;

class ReportsExport
{
    protected $branchId;

    public function __construct($branchId = null)
    {
        $this->branchId = $branchId;
    }

    public function download()
    {
        $rentals = Rental::with(['user', 'vehicle', 'branch'])
            ->when($this->branchId, fn($q) => $q->where('branch_id', $this->branchId))
            ->get();

        $filename = 'laporan-rental-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($rentals) {
            $file = fopen('php://output', 'w');

            // Heading row
            fputcsv($file, ['ID', 'Customer', 'Kendaraan', 'Cabang', 'Tanggal Mulai', 'Tanggal Selesai', 'Total', 'Status']);

            foreach ($rentals as $rental) {
                fputcsv($file, [
                    $rental->id,
                    $rental->user->name      ?? '-',
                    $rental->vehicle->name   ?? '-',
                    $rental->branch->name    ?? '-',
                    $rental->start_date,
                    $rental->end_date,
                    $rental->total_price,
                    $rental->status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}