<?php

namespace App\Services;

use App\Models\Rental;

/**
 * REFACTOR: dipindahkan dari RentalController (method private
 * buildWhatsAppMessage / buildReminderMessage / normalisasi nomor HP),
 * yang sebelumnya menyumbang ~100 baris ke controller dan menduplikasi
 * logika normalisasi nomor HP di dua tempat (whatsapp() & sendReminder()).
 *
 * Manfaat pemisahan ini:
 *  - RentalController jadi jauh lebih ringkas & fokus ke HTTP concerns saja.
 *  - Logika pesan WhatsApp bisa dipakai ulang oleh controller lain
 *    (mis. BroadcastController) tanpa copy-paste.
 *  - Lebih mudah di-unit-test tanpa perlu request HTTP penuh.
 */
class WhatsAppMessageService
{
    /**
     * Normalisasi nomor HP Indonesia ke format wa.me (62xxxxxxxxxx).
     * Sebelumnya logika ini diduplikasi persis sama di whatsapp() dan
     * sendReminder() pada RentalController.
     */
    public function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        return $phone;
    }

    public function buildWaLink(Rental $rental, string $message): string
    {
        $phone = $this->normalizePhone($rental->customer->phone);

        return "https://wa.me/{$phone}?text=" . urlencode($message);
    }

        public function buildPdfInvoiceMessage(Rental $rental, string $pdfLink): string
    {
        $clipboard = "\u{1F4CB}";
        $link      = "\u{1F517}";
        $pray      = "\u{1F64F}";

        return "{$clipboard} *NOTA PENYEWAAN JAS*\n" .
               "No. Invoice: {$rental->invoice_number}\n" .
               "Total: Rp " . number_format($rental->total_amount, 0, ',', '.') . "\n\n" .
               "{$link} Invoice lengkap (PDF):\n{$pdfLink}\n\n" .
               "Terima kasih! {$pray}";
    }

    // Method di bawah ini (buildInvoiceMessage) TIDAK LAGI dipakai sebagai
    // flow default "Kirim WA" — dipertahankan sebagai opsi kalau suatu saat
    // dibutuhkan nota teks lengkap lagi (mis. dikirim manual, bukan otomatis).
    public function buildInvoiceMessage(Rental $rental, string $invoiceLink = ''): string
    {
        $items = $rental->items->map(fn ($i) =>
            "- {$i->product_name} ({$i->product_size}) x{$i->quantity} @ Rp " .
            number_format($i->price_per_day, 0, ',', '.') . " (paket)"
        )->join("\n");

        $clipboard = "\u{1F4CB}";
        $package   = "\u{1F4E6}";
        $money     = "\u{1F4B0}";
        $pray      = "\u{1F64F}";
        $link      = "\u{1F517}";

        return "{$clipboard} *NOTA PENYEWAAN JAS*\n" .
               "========================\n" .
               "No. Invoice: {$rental->invoice_number}\n" .
               "Tanggal Sewa: {$rental->rental_date->format('d/m/Y')}\n" .
               "Tanggal Kembali: {$rental->return_due_date->format('d/m/Y')}\n" .
               "Durasi: {$rental->duration_days} hari (paket)\n\n" .
               "{$package} *Barang:*\n{$items}\n\n" .
               "{$money} *Rincian Biaya:*\n" .
               "Subtotal: Rp " . number_format($rental->subtotal, 0, ',', '.') . "\n" .
               ($rental->has_manual_discount
                   ? "Diskon Manual" . ($rental->discount_name ? " ({$rental->discount_name})" : "") . ": -Rp " . number_format($rental->discount, 0, ',', '.') . "\n"
                   : ($rental->discount > 0 ? "Diskon: -Rp " . number_format($rental->discount, 0, ',', '.') . "\n" : "")) .
               "*Total: Rp " . number_format($rental->total_amount, 0, ',', '.') . "*\n\n" .
               ($invoiceLink ? "{$link} *Lihat Invoice:*\n{$invoiceLink}\n\n" : "") .
               "Harap kembalikan barang tepat waktu.\nTerima kasih! {$pray}";
    }

    public function buildReminderMessage(Rental $rental): string
    {
        $now      = now()->startOfDay();
        $due      = $rental->return_due_date->startOfDay();
        $diffDays = (int) round($due->floatDiffInDays($now, false));

        $bell    = "\u{1F514}";
        $warning = "\u{26A0}";
        $fire    = "\u{1F6A8}";
        $pray    = "\u{1F64F}";
        $package = "\u{1F4E6}";

        $items = $rental->items->map(fn ($i) =>
            "- {$i->product_name}" . ($i->product_size ? " ({$i->product_size})" : "") . " x{$i->quantity}"
        )->join("\n");

        $customerName = $rental->customer->name;
        $dueFormatted = $due->format('d/m/Y');
        $invoice      = $rental->invoice_number;

        if ($diffDays <= -2) {
            $daysLeft = abs($diffDays);
            $header   = "{$bell} *PENGINGAT PENGEMBALIAN SEWA*";
            $status   = "Sewa Anda akan berakhir dalam *{$daysLeft} hari lagi* ({$dueFormatted}).";
            $closing  = "Mohon siapkan barang untuk dikembalikan tepat waktu. Terima kasih! {$pray}";
        } elseif ($diffDays === -1) {
            $header  = "{$warning} *PENGINGAT PENGEMBALIAN SEWA*";
            $status  = "Sewa Anda akan berakhir *besok* ({$dueFormatted}). Harap segera dipersiapkan.";
            $closing = "Pastikan barang sudah siap untuk dikembalikan. Terima kasih! {$pray}";
        } elseif ($diffDays === 0) {
            $header  = "{$warning} *PENGEMBALIAN HARI INI*";
            $status  = "*Hari ini* ({$dueFormatted}) adalah batas waktu pengembalian sewa Anda.";
            $closing = "Segera kembalikan barang sebelum toko tutup. Terima kasih! {$pray}";
        } else {
            $header  = "{$fire} *KETERLAMBATAN PENGEMBALIAN*";
            $status  = "Batas waktu pengembalian telah *melewati {$diffDays} hari* (jatuh tempo: {$dueFormatted}).";
            $closing = "Keterlambatan dikenakan denda. Mohon segera tindak lanjuti. {$pray}";
        }

        return "{$header}\n" .
               "========================\n" .
               "Yth. Bapak/Ibu *{$customerName}*\n\n" .
               "{$status}\n\n" .
               "{$package} *Detail Sewa:*\n" .
               "No. Invoice : {$invoice}\n" .
               "Tanggal Sewa: {$rental->rental_date->format('d/m/Y')}\n" .
               "Jatuh Tempo : {$dueFormatted}\n" .
               "Barang      :\n{$items}\n\n" .
               "{$closing}";
    }
}