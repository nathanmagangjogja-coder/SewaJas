<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Rental;

class RentalObserver
{
    private array $watch = [
        'rental_status','payment_status',
        'total_amount','paid_amount','late_fee','discount','overdue_days',
    ];

    public function created(Rental $rental): void
    {
        ActivityLog::record('create_rental',
            "Penyewaan dibuat: {$rental->invoice_number}",
            $rental, null,
            ['invoice_number' => $rental->invoice_number,
             'rental_status'  => $rental->rental_status,
             'total_amount'   => $rental->total_amount]
        );
    }

    public function updated(Rental $rental): void
    {
        $dirty = collect($rental->getDirty())->only($this->watch)->toArray();
        if (empty($dirty)) return;

        $old = [];
        foreach (array_keys($dirty) as $col) {
            $old[$col] = $rental->getOriginal($col);
        }

        $action = match(true) {
            isset($dirty['rental_status']) && $dirty['rental_status'] === 'cancelled' => 'cancel_rental',
            isset($dirty['rental_status']) && in_array($dirty['rental_status'], ['returned','menunggu_laundry']) => 'return_rental',
            isset($dirty['paid_amount'])   => 'process_payment',
            default                        => 'update_rental',
        };

        ActivityLog::record($action,
            "Penyewaan {$rental->invoice_number} diperbarui",
            $rental, $old, $dirty
        );
    }

    public function deleted(Rental $rental): void
    {
        ActivityLog::record('delete_rental',
            "Penyewaan dihapus: {$rental->invoice_number}",
            $rental,
            ['invoice_number' => $rental->invoice_number, 'total_amount' => $rental->total_amount],
            null
        );
    }
}
