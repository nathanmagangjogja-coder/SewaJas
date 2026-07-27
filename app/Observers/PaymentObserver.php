<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Payment;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        $rental = $payment->rental;
        ActivityLog::record('process_payment',
            sprintf('Pembayaran %s: Rp %s via %s untuk %s',
                $payment->payment_number,
                number_format($payment->amount, 0, ',', '.'),
                strtoupper($payment->method),
                $rental?->invoice_number ?? '-'
            ),
            $payment, null,
            ['amount' => $payment->amount, 'method' => $payment->method,
             'rental_id' => $payment->rental_id, 'payment_number' => $payment->payment_number]
        );
    }
}
