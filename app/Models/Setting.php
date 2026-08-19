<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public const INVOICE_DEFAULTS = [
        'invoice_company_name' => null,
        'invoice_tagline' => 'Premium Suit Rental',
        'invoice_theme' => 'gold',
        'invoice_primary_color' => '#D6B98C',
        'invoice_heading_color' => '#2B2520',
        'invoice_text_color' => '#2B2B2B',
        'invoice_muted_color' => '#6B6B6B',
        'invoice_logo_path' => null,
        'invoice_use_branch_logo' => '1',
        'invoice_show_logo' => '1',
        'invoice_show_qr' => '1',
        'invoice_show_watermark' => '1',
        'invoice_terms' => "1. Barang wajib dikembalikan sesuai tanggal jatuh tempo.\n2. Keterlambatan pengembalian dikenakan denda sesuai ketentuan toko.\n3. Kerusakan / kehilangan menjadi tanggung jawab penyewa.\n4. Jaminan dikembalikan setelah barang kembali dalam kondisi baik.",
        'invoice_footer_text' => null,
        'invoice_created_label' => 'Customer Service 1',
        'invoice_returned_label' => 'Customer Service 2',
    ];

    // ── Opsi Pembayaran (metode aktif, rekening bank, QRIS) ─────────────────
    public const PAYMENT_RESOLUTIONS = [256, 512, 768, 1024];

    public const PAYMENT_DEFAULTS = [
        'payment_methods_enabled' => '["cash","transfer","qris","other"]',
        'payment_banks' => '[]',
        'payment_qris_channels' => '["GoPay","OVO","Dana","ShopeePay"]',
        'payment_qris_image_path' => null,
        'payment_qris_resolution' => '512',
        'payment_qris_merchant_name' => null,
    ];

    public static function invoice(): array
    {
        return Cache::rememberForever('settings.invoice', function () {
            $stored = static::query()
                ->whereIn('key', array_keys(static::INVOICE_DEFAULTS))
                ->pluck('value', 'key')
                ->all();

            $settings = array_merge(static::INVOICE_DEFAULTS, $stored);

            foreach (['invoice_show_logo', 'invoice_use_branch_logo', 'invoice_show_qr', 'invoice_show_watermark'] as $key) {
                $settings[$key] = (bool) (int) ($settings[$key] ?? 0);
            }

            return $settings;
        });
    }

    public static function payment(): array
    {
        return Cache::rememberForever('settings.payment', function () {
            $stored = static::query()
                ->whereIn('key', array_keys(static::PAYMENT_DEFAULTS))
                ->pluck('value', 'key')
                ->all();

            $settings = array_merge(static::PAYMENT_DEFAULTS, $stored);

            $settings['payment_methods_enabled'] = static::decodeList($settings['payment_methods_enabled']) ?: ['cash'];
            $settings['payment_banks'] = static::decodeList($settings['payment_banks']);
            $settings['payment_qris_channels'] = static::decodeList($settings['payment_qris_channels']);
            $settings['payment_qris_resolution'] = (int) $settings['payment_qris_resolution'] ?: 512;
            $settings['payment_qris_image_url'] = $settings['payment_qris_image_path']
                ? Storage::disk('public')->url($settings['payment_qris_image_path'])
                : null;

            return $settings;
        });
    }

    private static function decodeList(?string $value): array
    {
        if (!$value) {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    public static function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(
                ['key' => $key],
                ['value' => is_bool($value) ? (string) (int) $value : $value]
            );
        }

        Cache::forget('settings.invoice');
        Cache::forget('settings.payment');
    }

    public static function forgetInvoiceLogo(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public static function forgetQrisImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}