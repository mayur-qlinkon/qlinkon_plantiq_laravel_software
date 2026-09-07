<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Announcement;
use App\Services\Hrm\AnnouncementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AnnouncementPopupController extends Controller
{
    public function __construct(
        protected AnnouncementService $announcementService
    ) {}

    /**
     * Fetch pending announcements for the current user (AJAX).
     */
    public function pending(): JsonResponse
    {
        $user = Auth::user();
        $announcements = $this->announcementService->getPendingForUser($user);

        return response()->json([
            'success' => true,
            'data' => $announcements->map(fn (Announcement $a) => [
                'id' => $a->id,
                'title' => $a->title,
                'content' => $a->content,
                'type' => $a->type,
                'type_label' => $a->type_label,
                'type_color' => $a->type_color,
                'priority' => $a->priority,
                'priority_label' => $a->priority_label,
                'requires_acknowledgement' => $a->requires_acknowledgement,
                'is_pinned' => $a->is_pinned,
                'published_at' => $a->published_at?->diffForHumans(),
                'attachment_url' => $a->attachment_url,
                'attachment_name' => $a->attachment_name,
            ]),
            'mandatory_count' => $announcements->where('requires_acknowledgement', true)->count(),
        ]);
    }

    /**
     * Mark announcement as read (viewed).
     */
    public function markRead(Announcement $announcement): JsonResponse
    {
        $this->announcementService->markRead(
            $announcement,
            request()->ip(),
            request()->userAgent()
        );

        return response()->json(['success' => true]);
    }

    /**
     * Acknowledge a mandatory announcement ("I Accept").
     */
    public function acknowledge(Announcement $announcement): JsonResponse
    {
        $this->announcementService->acknowledge(
            $announcement,
            request()->ip(),
            request()->userAgent()
        );

        return response()->json([
            'success' => true,
            'message' => 'Announcement acknowledged.',
        ]);
    }

    /**
     * Dismiss a non-mandatory announcement (skip it).
     */
    public function dismiss(Announcement $announcement): JsonResponse
    {
        if ($announcement->requires_acknowledgement) {
            return response()->json([
                'success' => false,
                'message' => 'Mandatory announcements cannot be dismissed. Please acknowledge.',
            ], 422);
        }

        $this->announcementService->dismiss(
            $announcement,
            request()->ip(),
            request()->userAgent()
        );

        return response()->json([
            'success' => true,
            'message' => 'Announcement dismissed.',
        ]);
    }
}
