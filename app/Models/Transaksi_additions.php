<?php

// ============================================================
// TAMBAHKAN KE MODEL TRANSAKSI YANG SUDAH ADA
// File: app/Models/Transaksi.php
// ============================================================
// Tambahkan use statement:
// use Illuminate\Database\Eloquent\Relations\HasOne;
// use Illuminate\Database\Eloquent\Relations\MorphMany;
// ============================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// ... use lainnya yang sudah ada ...
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Transaksi extends Model
{
    // ... kode existing Anda ...

    // ─── Tambahkan ke $fillable ───────────────────────────────────────────────
    // 'dikembalikan_at', 'mulai_laundry_at', 'selesai_laundry_at'

    // ─── Tambahkan ke $casts ─────────────────────────────────────────────────
    // 'dikembalikan_at'   => 'datetime',
    // 'mulai_laundry_at'  => 'datetime',
    // 'selesai_laundry_at'=> 'datetime',

    // ─── Status Constants (TAMBAHKAN ke existing) ─────────────────────────────

    const STATUS_DISEWA            = 'disewa';
    const STATUS_MENUNGGU_KEMBALI  = 'menunggu_kembali';
    const STATUS_DIKEMBALIKAN      = 'dikembalikan';
    const STATUS_MENUNGGU_LAUNDRY  = 'menunggu_laundry';
    const STATUS_DALAM_LAUNDRY     = 'dalam_laundry';
    const STATUS_SIAP_DISEWAKAN    = 'siap_disewakan';
    const STATUS_SELESAI           = 'selesai';

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DISEWA           => 'Disewa',
            self::STATUS_MENUNGGU_KEMBALI => 'Menunggu Kembali',
            self::STATUS_DIKEMBALIKAN     => 'Dikembalikan',
            self::STATUS_MENUNGGU_LAUNDRY => 'Menunggu Laundry',
            self::STATUS_DALAM_LAUNDRY    => 'Dalam Laundry',
            self::STATUS_SIAP_DISEWAKAN   => 'Siap Disewakan',
            self::STATUS_SELESAI          => 'Selesai',
        ];
    }

    // ─── Relationships (TAMBAHKAN) ────────────────────────────────────────────

    public function laundry(): HasOne
    {
        return $this->hasOne(Laundry::class);
    }

    public function statusHistories(): MorphMany
    {
        return $this->morphMany(StatusHistory::class, 'model')->orderBy('changed_at', 'desc');
    }

    // ─── Method untuk trigger laundry setelah scan QR ─────────────────────────

    /**
     * Dipanggil setelah QR scan sukses → otomatis buat record laundry
     */
    public function prosesKembaliDanBuatLaundry(User $user): Laundry
    {
        $statusLama = $this->status;

        // Update status transaksi
        $this->update([
            'status'          => self::STATUS_MENUNGGU_LAUNDRY,
            'dikembalikan_at' => now(),
        ]);

        // Catat history transaksi
        StatusHistory::create([
            'model_type'  => self::class,
            'model_id'    => $this->id,
            'status_lama' => $statusLama,
            'status_baru' => self::STATUS_MENUNGGU_LAUNDRY,
            'keterangan'  => 'Pengembalian via Scan QR',
            'user_id'     => $user->id,
            'changed_at'  => now(),
        ]);

        // Buat record laundry baru
        $laundry = Laundry::create([
            'transaksi_id'    => $this->id,
            'produk_id'       => $this->produk_id, // sesuaikan nama kolom
            'status'          => Laundry::STATUS_MENUNGGU_LAUNDRY,
            'dikembalikan_at' => now(),
            'diproses_oleh'   => $user->id,
        ]);

        // Catat history laundry
        StatusHistory::create([
            'model_type'  => Laundry::class,
            'model_id'    => $laundry->id,
            'status_lama' => null,
            'status_baru' => Laundry::STATUS_MENUNGGU_LAUNDRY,
            'keterangan'  => 'Laundry dibuat otomatis setelah pengembalian',
            'user_id'     => $user->id,
            'changed_at'  => now(),
        ]);

        return $laundry;
    }
}
