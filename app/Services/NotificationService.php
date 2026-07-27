<?php
namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    public function broadcast(array $userIds, string $type, string $title, string $message, array $meta = [], ?int $branchId = null, ?string $actionUrl = null): void
    {
        $now  = now();
        $rows = array_map(fn($uid) => [
            'user_id'    => $uid,
            'branch_id'  => $branchId,
            'type'       => $type,
            'title'      => $title,
            'message'    => $message,
            'meta'       => $meta ? json_encode($meta) : null,
            'action_url' => $actionUrl,
            'is_read'    => false,
            'created_at' => $now,
            'updated_at' => $now,
        ], $userIds);

        Notification::insert($rows);
    }

    public function rentalCreated($rental, array $userIds): void
    {
        $this->broadcast($userIds, 'rental_new', 'Penyewaan Baru',
            "{$rental->customer->name} — {$rental->invoice_no}",
            ['invoice_no' => $rental->invoice_no], $rental->branch_id);
    }

    public function rentalReturned($rental, array $userIds): void
    {
        $this->broadcast($userIds, 'rental_return', 'Jas Dikembalikan',
            "{$rental->customer->name} mengembalikan jas — {$rental->invoice_no}",
            ['invoice_no' => $rental->invoice_no], $rental->branch_id);
    }

    public function rentalLate($rental, int $days, int $fine, array $userIds): void
    {
        $this->broadcast($userIds, 'rental_late', 'Jas Telat',
            "{$rental->customer->name} telat {$days} hari. Denda Rp " . number_format($fine, 0, ',', '.'),
            ['invoice_no' => $rental->invoice_no, 'fine' => $fine], $rental->branch_id);
    }

    public function paymentReceived($rental, int $amount, array $userIds): void
    {
        $this->broadcast($userIds, 'payment', 'Pembayaran Diterima',
            "{$rental->customer->name} — Rp " . number_format($amount, 0, ',', '.'),
            ['invoice_no' => $rental->invoice_no], $rental->branch_id);
    }

    public function returnReminder($rental, array $userIds): void
    {
        $this->broadcast($userIds, 'reminder', 'Reminder Pengembalian',
            "{$rental->customer->name} — jatuh tempo hari ini",
            ['invoice_no' => $rental->invoice_no], $rental->branch_id);
    }
}