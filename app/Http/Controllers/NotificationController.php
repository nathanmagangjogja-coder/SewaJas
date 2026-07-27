<?php
namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function data(Request $request)
    {
        $type  = $request->input('type', 'all');
        $query = Notification::forUser(auth()->id())->latest();
        if ($type !== 'all') $query->byType($type);

        $notifications = $query->limit(20)->get()->map(fn($n) => [
            'id'         => $n->id,
            'type'       => $n->type,
            'title'      => $n->title,
            'message'    => $n->message,
            'is_read'    => $n->is_read,
            'time_ago'   => $n->time_ago,
            'icon_name'  => $n->icon_name,
            'icon_class' => $n->icon_class,
            'meta'       => $n->meta,
            'action_url' => $n->action_url,
        ]);

        return response()->json([
            'notifications' => $notifications,
            'counts'        => $this->getCounts(),
        ]);
    }

    public function count()
    {
        return response()->json($this->getCounts());
    }

    public function markRead(int $id)
    {
        Notification::forUser(auth()->id())->findOrFail($id)->markAsRead();
        return response()->json(['counts' => $this->getCounts()]);
    }

    public function markAllRead(Request $request)
    {
        $q = Notification::forUser(auth()->id())->unread();
        if ($request->input('type') && $request->input('type') !== 'all') {
            $q->byType($request->input('type'));
        }
        $q->update(['is_read' => true, 'read_at' => now()]);
        return response()->json(['counts' => $this->getCounts()]);
    }

    public function destroy(int $id)
    {
        Notification::forUser(auth()->id())->findOrFail($id)->delete();
        return response()->json(['counts' => $this->getCounts()]);
    }

    private function getCounts(): array
    {
        $rows = Notification::forUser(auth()->id())
            ->unread()
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();

        return [
            'total'         => array_sum($rows),
            'rental_new'    => $rows['rental_new']    ?? 0,
            'rental_return' => $rows['rental_return'] ?? 0,
            'rental_late'   => $rows['rental_late']   ?? 0,
            'payment'       => $rows['payment']       ?? 0,
            'reminder'      => $rows['reminder']      ?? 0,
            'system'        => $rows['system']        ?? 0,
        ];
    }

public function show($id)
{
    $notification = auth()->user()->notifications()->findOrFail($id);
    if (!$notification->is_read) $notification->update(['is_read' => true]);
   return view('components.show', compact('notification'));
}

}