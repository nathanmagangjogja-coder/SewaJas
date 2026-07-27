<?php

namespace App\Console\Commands;

use App\Models\Rental;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendRentalReminders extends Command
{
    protected $signature   = 'rentals:send-reminders';
    protected $description = 'Kirim WA reminder H-1 jatuh tempo sewa jas';

    public function handle(): void
    {
        $tomorrow = now('Asia/Jakarta')->addDay()->toDateString();

        $rentals = Rental::with('customer', 'package')
            ->whereIn('rental_status', ['active', 'overdue'])
            ->whereDate('return_due_date', $tomorrow)
            ->get();

        $this->info("Ditemukan {$rentals->count()} rental jatuh tempo besok.");

        $sent = $gagal = 0;

        foreach ($rentals as $rental) {
            $customer = $rental->customer;

            if (!$customer?->phone) {
                $this->warn("  ⚠ {$rental->invoice_number}: no HP kosong, skip.");
                continue;
            }

            $phone = $this->formatPhone($customer->phone);
            $pesan = $this->buildMessage($rental, $customer);

            if ($this->kirimWA($phone, $pesan)) {
                $sent++;
                $this->info("  ✓ Terkirim ke {$customer->name} ({$phone})");
            } else {
                $gagal++;
                $this->error("  ✗ Gagal ke {$customer->name} ({$phone})");
            }
        }

        $this->info("Selesai: {$sent} terkirim, {$gagal} gagal.");
        Log::info("rentals:send-reminders — {$sent} terkirim, {$gagal} gagal.");
    }

    private function formatPhone(string $phone): string
    {
        $clean = preg_replace('/\D/', '', $phone);
        if (str_starts_with($clean, '0')) {
            return '62' . substr($clean, 1);
        }
        return str_starts_with($clean, '62') ? $clean : '62' . $clean;
    }

    private function buildMessage(Rental $rental, $customer): string
    {
        $jatuhTempo = \Carbon\Carbon::parse($rental->return_due_date)
            ->locale('id')->isoFormat('dddd, D MMMM Y');

        $paket = $rental->package?->name ?? 'Penyewaan Jas';
        $sisa  = $rental->remaining_amount > 0
            ? "\n💰 Sisa pembayaran: *Rp " . number_format($rental->remaining_amount, 0, ',', '.') . "*"
            : '';

        return "Halo *{$customer->name}*,\n\n" .
               "Pengingat: {$paket} Anda (*{$rental->invoice_number}*) " .
               "jatuh tempo besok, *{$jatuhTempo}*." .
               "{$sisa}\n\n" .
               "Mohon kembalikan tepat waktu untuk menghindari denda keterlambatan.\n\n" .
               "Terima kasih 🙏\n_MaisonSewa_";
    }

    private function kirimWA(string $phone, string $pesan): bool
    {
        $token = config('services.fonnte.token');

        if (!$token) {
            // Tanpa token: log URL saja (berguna saat testing)
            Log::info("WA reminder (no token): https://wa.me/{$phone}");
            return true;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders(['Authorization' => $token])
                ->post('https://api.fonnte.com/send', [
                    'target'  => $phone,
                    'message' => $pesan,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("WA gagal ke {$phone}: " . $e->getMessage());
            return false;
        }
    }
}
