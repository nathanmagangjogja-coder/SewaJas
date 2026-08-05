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
        $summary = "{$rental->customer->name} · {$rental->invoice_number} · {$rental->duration_days} hari · Rp " . number_format($rental->total_amount, 0, ',', '.');

        $this->broadcast($userIds, 'rental_new', 'Penyewaan Baru',
            "{$rental->customer->name} — {$rental->invoice_number}",
            [
                'invoice_no' => $rental->invoice_number,
                'summary'    => $summary,
            ],
            $rental->branch_id
        );
    }

    public function rentalReturned($rental, array $userIds): void
    {
        $summary = "{$rental->customer->name} · {$rental->invoice_number} · {$rental->duration_days} hari";

        $this->broadcast($userIds, 'rental_return', 'Jas Dikembalikan',
            "{$rental->customer->name} mengembalikan jas — {$rental->invoice_number}",
            [
                'invoice_no' => $rental->invoice_number,
                'summary'    => $summary,
            ],
            $rental->branch_id
        );
    }

    public function rentalLate($rental, int $days, int $fine, array $userIds): void
    {
        $summary = "{$rental->customer->name} · {$rental->invoice_number} · Telat {$days} hari";

        $this->broadcast($userIds, 'rental_late', 'Jas Telat',
            "{$rental->customer->name} telat {$days} hari. Denda Rp " . number_format($fine, 0, ',', '.'),
            [
                'invoice_no' => $rental->invoice_number,
                'fine'       => $fine,
                'summary'    => $summary,
            ],
            $rental->branch_id
        );
    }

    public function paymentReceived($rental, int $amount, array $userIds): void
    {
        $summary = "{$rental->customer->name} · {$rental->invoice_number} · Rp " . number_format($amount, 0, ',', '.');

        $this->broadcast($userIds, 'payment', 'Pembayaran Diterima',
            "{$rental->customer->name} — Rp " . number_format($amount, 0, ',', '.'),
            [
                'invoice_no' => $rental->invoice_number,
                'summary'    => $summary,
            ],
            $rental->branch_id
        );
    }

    public function returnReminder($rental, array $userIds): void
    {
        $summary = "{$rental->customer->name} · {$rental->invoice_number} · Jatuh tempo {$rental->return_due_date->format('d M Y')}";

        $this->broadcast($userIds, 'reminder', 'Reminder Pengembalian',
            "{$rental->customer->name} — jatuh tempo hari ini",
            [
                'invoice_no' => $rental->invoice_number,
                'summary'    => $summary,
            ],
            $rental->branch_id
        );
    }

    /**
     * BARU: notifikasi lonceng untuk alert jatuh tempo (H-1 / hari ini /
     * baru saja terlambat). Dipanggil oleh command `rentals:send-reminders`
     * (H-1/hari ini) dan `rentals:update-overdue` (baru jadi overdue).
     *
     * NOTE: dipakai `invoice_number` (bukan `invoice_no` seperti method2 lain
     * di atas) karena itulah nama kolom yang sebenarnya ada di tabel
     * `rentals` — method2 lain di file ini (rentalCreated, rentalReturned,
     * dst.) memakai `invoice_no` yang TIDAK ADA di model Rental, jadi akan
     * selalu tampil kosong pada notifikasi. Sebaiknya diperbaiki juga kalau
     * sempat, tapi di luar cakupan perubahan ini.
     */
    public function rentalDueAlert($rental, string $stage, array $userIds): void
    {
        $labels = [
            'due_tomorrow' => ['Jatuh Tempo Besok (H-1)', 'jatuh tempo BESOK'],
            'due_today'    => ['Jatuh Tempo Hari Ini',    'jatuh tempo HARI INI'],
            'overdue'      => ['Baru Saja Terlambat',     'baru saja melewati jatuh tempo'],
        ];

        [$title, $phrase] = $labels[$stage] ?? ['Alert Jatuh Tempo', 'mendekati jatuh tempo'];

        $this->broadcast(
            $userIds,
            'due_alert',
            $title,
            "{$rental->customer->name} — {$rental->invoice_number} {$phrase} ({$rental->return_due_date->format('d M Y')})",
            ['invoice_number' => $rental->invoice_number, 'stage' => $stage],
            $rental->branch_id,
            route('rentals.show', $rental)
        );
    }
}