<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $query = $user->notifications();

        // ── Search inside JSON 'data' column ──
        if ($request->filled('q')) {
            $searchTerm = $request->q;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('data->title', 'like', "%{$searchTerm}%")
                    ->orWhere('data->message', 'like', "%{$searchTerm}%");
            });
        }

        // ── Filter by notification type ──
        if ($request->filled('type')) {
            $query->where('data->type', $request->type);
        }

        $notifications = $query->latest()->paginate(20)->withQueryString();

        return view('admin.notifications.index', compact('notifications'));
    }

    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications->firstWhere('id', $id);
        $notification?->markAsRead();

        return response()->json(['success' => true]);
    }

    public function markAllRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * Lightweight polling endpoint. Hit every 30 seconds per open admin tab,
     * so it must stay as close to a single indexed query as possible.
     */
    public function fetchRecent()
    {
        $user = Auth::user();

        $limit = 10;

        // Select only the columns the payload actually uses. The 'type' column
        // is never read by the bell, and 'data' is the only wide column.
        $unread = $user->unreadNotifications()
            ->select(['id', 'notifiable_id', 'notifiable_type', 'data', 'read_at', 'created_at'])
            ->latest()
            ->limit($limit)
            ->get();

        $items = $unread->map(fn ($n) => [
            'id' => $n->id,
            'title' => $n->data['title'] ?? 'Notification',
            'message' => $n->data['message'] ?? '',
            'icon' => $n->data['icon'] ?? 'bell',
            'color' => $n->data['color'] ?? 'blue',
            'link' => $n->data['link'] ?? '#',
            'time' => $n->created_at->diffForHumans(),
        ]);

        // Only issue a COUNT query when the result set is full. If fewer rows
        // than the limit came back, the collection size IS the total unread
        // count, so the second query is provably redundant. Most users sit
        // well under the limit, so in practice this endpoint runs one query.
        $count = $unread->count() < $limit
            ? $unread->count()
            : $user->unreadNotifications()->count();

        return response()->json([
            'count' => $count,
            'latest_id' => $items->first()['id'] ?? null,
            'items' => $items,
        ]);
    }
}
