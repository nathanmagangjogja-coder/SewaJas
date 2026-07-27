<?php

namespace App\Http\Controllers;

use App\Models\SupportMessage;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupportMessageController extends Controller
{
    public function __construct(private NotificationService $notifService) {}

    /**
     * Sales / Admin Toko mengirim pesan ke Super Admin
     * (kontak admin atau laporan bug), dari modal footer.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'type'    => 'required|in:bug_report,contact_admin,other',
            'subject' => 'nullable|string|max:150',
            'message' => 'required|string|max:2000',
        ]);

        $user = Auth::user();

        $supportMessage = SupportMessage::create([
            'user_id'   => $user->id,
            'branch_id' => $user->branch_id,
            'type'      => $data['type'],
            'subject'   => $data['subject'] ?? null,
            'message'   => $data['message'],
        ]);

        // Broadcast ke semua Super Admin lewat lonceng notifikasi yang sudah ada
        $superAdminIds = User::where('role', 'super_admin')->pluck('id')->toArray();

        if (!empty($superAdminIds)) {
            $title = $data['type'] === 'bug_report' ? 'Laporan Bug Baru' : 'Pesan dari ' . $user->name;

            $this->notifService->broadcast(
                $superAdminIds,
                'support_message',
                $title,
                ($data['subject'] ? $data['subject'] . ' — ' : '') . \Illuminate\Support\Str::limit($data['message'], 100),
                ['support_message_id' => $supportMessage->id, 'sub_type' => $data['type']],
                $user->branch_id,
                route('support-messages.index')
            );
        }

        return back()->with('success', 'Pesan berhasil dikirim ke Super Admin. Terima kasih!');
    }

    /**
     * Super Admin — daftar pesan masuk (inbox).
     */
    public function index(Request $request)
    {
        $query = SupportMessage::with(['user', 'branch'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->latest();

        $messages    = $query->paginate(15)->withQueryString();
        $unreadCount = SupportMessage::unread()->count();

        return view('support-messages.index', compact('messages', 'unreadCount'));
    }

    /**
     * Super Admin — tandai pesan sudah dibaca.
     */
    public function markRead(SupportMessage $supportMessage)
    {
        if ($supportMessage->status === 'unread') {
            $supportMessage->update([
                'status'  => 'read',
                'read_at' => now(),
                'read_by' => Auth::id(),
            ]);
        }

        return back()->with('success', 'Pesan ditandai sudah dibaca.');
    }

    /**
     * Polling ringan untuk badge jumlah pesan belum dibaca (Super Admin only).
     */
    public function unreadCount()
    {
        return response()->json([
            'count' => SupportMessage::unread()->count(),
        ]);
    }
}