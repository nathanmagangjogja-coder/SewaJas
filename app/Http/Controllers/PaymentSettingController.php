<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PaymentSettingController extends Controller
{
    private const METHODS = ['cash', 'transfer', 'qris', 'other'];

    public function edit()
    {
        return view('settings.payment', [
            'settings' => Setting::payment(),
            'resolutions' => Setting::PAYMENT_RESOLUTIONS,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'methods' => ['nullable', 'array'],
            'methods.*' => [Rule::in(self::METHODS)],

            'banks' => ['nullable', 'array'],
            'banks.*.name' => ['nullable', 'string', 'max:60'],
            'banks.*.account_number' => ['nullable', 'string', 'max:50'],
            'banks.*.account_holder' => ['nullable', 'string', 'max:100'],

            'qris_channels' => ['nullable', 'string', 'max:500'],
            'qris_image' => ['nullable', 'image', 'max:4096'],
            'remove_qris_image' => ['nullable', 'boolean'],
            'qris_resolution' => ['required', Rule::in(Setting::PAYMENT_RESOLUTIONS)],
            'qris_merchant_name' => ['nullable', 'string', 'max:100'],
        ]);

        // Metode pembayaran aktif — minimal 1 metode harus tetap aktif (fallback ke cash).
        $methods = array_values(array_intersect(self::METHODS, $data['methods'] ?? []));
        if (empty($methods)) {
            $methods = ['cash'];
        }

        // Buang baris rekening bank yang kosong semua (nama & nomor rekening).
        $banks = collect($data['banks'] ?? [])
            ->map(fn ($bank) => [
                'name' => trim($bank['name'] ?? ''),
                'account_number' => trim($bank['account_number'] ?? ''),
                'account_holder' => trim($bank['account_holder'] ?? ''),
            ])
            ->filter(fn ($bank) => $bank['name'] !== '' || $bank['account_number'] !== '')
            ->values()
            ->all();

        // "GoPay, OVO, Dana" → ['GoPay', 'OVO', 'Dana'] — buang spasi/duplikat/kosong.
        $qrisChannels = collect(explode(',', $data['qris_channels'] ?? ''))
            ->map(fn ($c) => trim($c))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $current = Setting::payment();
        $qrisImagePath = $current['payment_qris_image_path'];

        if ($request->boolean('remove_qris_image')) {
            Setting::forgetQrisImage($qrisImagePath);
            $qrisImagePath = null;
        }

        if ($request->hasFile('qris_image')) {
            Setting::forgetQrisImage($qrisImagePath);
            $qrisImagePath = $this->storeQrisImage($request->file('qris_image'), (int) $data['qris_resolution']);
        }

        Setting::putMany([
            'payment_methods_enabled' => json_encode(array_values($methods)),
            'payment_banks' => json_encode($banks),
            'payment_qris_channels' => json_encode($qrisChannels),
            'payment_qris_image_path' => $qrisImagePath,
            'payment_qris_resolution' => (string) $data['qris_resolution'],
            'payment_qris_merchant_name' => $data['qris_merchant_name'] ?: null,
        ]);

        return back()->with('success', 'Pengaturan opsi pembayaran berhasil disimpan.');
    }

    /**
     * Resize foto QRIS menjadi kanvas persegi (background putih, contain-fit)
     * sesuai resolusi yang dipilih superadmin, supaya ukuran file konsisten
     * dan tidak terlalu berat ditampilkan di halaman pembayaran / thermal print.
     */
    private function storeQrisImage(UploadedFile $file, int $size): string
    {
        Storage::disk('public')->makeDirectory('payment/qris');
        $filename = 'payment/qris/qris_' . uniqid() . '.png';

        if (function_exists('imagecreatefromstring')) {
            $source = @imagecreatefromstring(file_get_contents($file->getRealPath()));

            if ($source !== false) {
                $srcW = imagesx($source);
                $srcH = imagesy($source);

                $canvas = imagecreatetruecolor($size, $size);
                $white = imagecolorallocate($canvas, 255, 255, 255);
                imagefill($canvas, 0, 0, $white);

                $ratio = min($size / $srcW, $size / $srcH);
                $newW = max(1, (int) round($srcW * $ratio));
                $newH = max(1, (int) round($srcH * $ratio));
                $dstX = (int) (($size - $newW) / 2);
                $dstY = (int) (($size - $newH) / 2);

                imagecopyresampled($canvas, $source, $dstX, $dstY, 0, 0, $newW, $newH, $srcW, $srcH);
                imagepng($canvas, Storage::disk('public')->path($filename));

                imagedestroy($source);
                imagedestroy($canvas);

                return $filename;
            }
        }

        // Fallback bila ekstensi GD tidak tersedia — simpan file asli apa adanya.
        return $file->storeAs('payment/qris', basename($filename), 'public');
    }
}
