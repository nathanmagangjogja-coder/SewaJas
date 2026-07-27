<?php

namespace Database\Seeders;

use App\Models\BroadcastSchedule;
use Illuminate\Database\Seeder;

class BroadcastScheduleSeeder extends Seeder
{
    public function run(): void
    {
        BroadcastSchedule::create([
            'name' => 'Broadcast Harian Standar',
            'description' => 'Pesan otomatis harian ke pelanggan untuk pengingat, promo, dan update koleksi.',
            'send_at_times' => ['08:00', '12:00', '16:00', '20:00'],
            'message_templates' => [
                '{greeting} {name}! Jas {product} Anda harus dikembalikan {days_left} hari lagi. Ada pertanyaan? Hubungi kami 😊',
                '{greeting} {name}! Sudah {total_rentals}x sewa di MaisonSewa. Terima kasih kepercayaannya! 🙏',
                '{greeting} {name}! Jangan lupa kembalikan {product} sebelum {return_date} ya 📅',
                '{greeting} {name}! Punya acara lagi? Jas koleksi baru kami siap untuk Anda 🎩',
                '{greeting} {name}! Pelanggan setia kami mendapat prioritas booking. Hubungi kami sekarang ✨',
                '{greeting} kak {name}! Terima kasih sudah memilih MaisonSewa. Kami senang melayani Anda 🌟',
                '{greeting} {name}! Ingatkan teman yang butuh jas? Referral dari Anda sangat kami hargai 💼',
                '{greeting} {name}! Update terbaru: koleksi jas premium kami baru saja bertambah. Cek sekarang! 👔',
            ],
            'target_audience' => 'all',
            'is_active' => true,
            'cooldown_hours' => 24,
        ]);
    }
}
