<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Events\Hrm\AnnouncementPublished;
use App\Http\Controllers\Controller;
use App\Models\Hrm\Announcement;
use App\Models\Hrm\Department;
use App\Models\Hrm\Designation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Services\Hrm\AnnouncementService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class AnnouncementController extends Controller
{
    public function __construct(
        protected AnnouncementService $announcementService
    ) {}

    // ── Index ──

    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Announcement::class);

        $filters = $request->only(['search', 'type', 'status', 'priority', 'is_pinned', 'sort_by', 'sort_dir', 'per_page']);
        $announcements = $this->announcementService->getList($filters);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $announcements]);
        }

        return view('admin.hrm.announcements.index', [
            'announcements' => $announcements,
            'filters' => $filters,
            'typeOptions' => Announcement::TYPE_LABELS,
            'statusOptions' => Announcement::STATUS_LABELS,
            'priorityOptions' => Announcement::PRIORITY_LABELS,
        ]);
    }

    // ── Create ──

    public function create(): View
    {
        $this->authorize('create', Announcement::class);

        return view('admin.hrm.announcements.create', [
            'typeOptions' => Announcement::TYPE_LABELS,
            'priorityOptions' => Announcement::PRIORITY_LABELS,
            'targetOptions' => $this->getTargetOptions(),
        ]);
    }

    // ── Store ──

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorize('create', Announcement::class);

        $validated = $request->validate($this->rules());

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $validated['attachment'] = $file->store('hrm/announcements', 'local');
            $validated['attachment_name'] = $file->getClientOriginalName();
        }

        $announcement = $this->announcementService->create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Announcement created.',
                'data' => $announcement,
            ], 201);
        }

        return redirect()->route('admin.hrm.announcements.show', $announcement)
            ->with('success', 'Announcement created successfully.');
    }

    // ── Show ──

    public function show(Request $request, Announcement $announcement): View|JsonResponse
    {
        $this->authorize('view', $announcement);

        $announcement->load(['createdByUser', 'publishedByUser', 'acknowledgements.user'])
            ->loadCount([
                'acknowledgements',
                'acknowledgements as read_count' => fn ($q) => $q->whereNotNull('read_at'),
                'acknowledgements as acknowledged_count' => fn ($q) => $q->whereNotNull('acknowledged_at'),
            ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $announcement]);
        }

        return view('admin.hrm.announcements.show', compact('announcement'));
    }

    // ── Edit ──

    public function edit(Announcement $announcement): View
    {
        $this->authorize('update', $announcement);

        return view('admin.hrm.announcements.edit', [
            'announcement' => $announcement,
            'typeOptions' => Announcement::TYPE_LABELS,
            'priorityOptions' => Announcement::PRIORITY_LABELS,
            'targetOptions' => $this->getTargetOptions(),
        ]);
    }

    // ── Update ──

    public function update(Request $request, Announcement $announcement): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $announcement);

        $validated = $request->validate($this->rules($announcement));
        $announcement = $this->announcementService->update($announcement, $validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Announcement updated.',
                'data' => $announcement,
            ]);
        }

        return redirect()->route('admin.hrm.announcements.show', $announcement)
            ->with('success', 'Announcement updated successfully.');
    }

    // ── Destroy ──

    public function destroy(Request $request, Announcement $announcement): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $announcement);

        $this->announcementService->delete($announcement);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Announcement deleted.']);
        }

        return redirect()->route('admin.hrm.announcements.index')
            ->with('success', 'Announcement deleted.');
    }

    // ── Publish ──

    public function publish(Announcement $announcement): JsonResponse
    {
        $this->authorize('publish', $announcement);

        $announcement = $this->announcementService->publish($announcement);

        // 🚀 NEW: Dispatch notification event
        event(new AnnouncementPublished($announcement));

        return response()->json([
            'success' => true,
            'message' => 'Announcement published successfully.',
            'data' => $announcement,
        ]);
    }

    // ── Unpublish ──

    public function unpublish(Announcement $announcement): JsonResponse
    {
        $this->authorize('unpublish', $announcement);

        $announcement = $this->announcementService->unpublish($announcement);

        return response()->json([
            'success' => true,
            'message' => 'Announcement moved back to draft.',
            'data' => $announcement,
        ]);
    }

    // ── Schedule ──

    public function schedule(Request $request, Announcement $announcement): JsonResponse
    {
        $this->authorize('publish', $announcement);

        $request->validate([
            'publish_at' => ['required', 'date', 'after:now'],
        ]);

        $announcement = $this->announcementService->schedule(
            $announcement,
            Carbon::parse($request->publish_at)
        );

        return response()->json([
            'success' => true,
            'message' => 'Announcement scheduled.',
            'data' => $announcement,
        ]);
    }

    // ── Duplicate ──

    public function duplicate(Announcement $announcement): JsonResponse
    {
        $this->authorize('duplicate', $announcement);

        $copy = $this->announcementService->duplicate($announcement);

        return response()->json([
            'success' => true,
            'message' => 'Duplicated as draft.',
            'data' => $copy,
            'redirect' => route('admin.hrm.announcements.edit', $copy),
        ]);
    }

    // ── Restore ──

    public function restore(int $id): JsonResponse
    {
        $this->authorize('restore', Announcement::class);

        $announcement = $this->announcementService->restore($id);

        return response()->json([
            'success' => true,
            'message' => 'Announcement restored.',
            'data' => $announcement,
        ]);
    }

    // ─────────────────────────────────────────────
    //  Validation Rules
    // ─────────────────────────────────────────────

    private function rules(?Announcement $announcement = null): array
    {
        $isPublished = $announcement?->status === Announcement::STATUS_PUBLISHED;

        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'type' => ['required', Rule::in(array_keys(Announcement::TYPE_LABELS))],
            'priority' => ['required', Rule::in(array_keys(Announcement::PRIORITY_LABELS))],
            'target_audience' => ['required', Rule::in(array_keys(Announcement::TARGET_LABELS))],
            'target_ids' => ['required_unless:target_audience,all', 'nullable', 'array', 'min:1'],
            'target_ids.*' => ['integer'],
            'publish_at' => $isPublished ? ['sometimes'] : ['nullable', 'date'],
            'expire_at' => ['nullable', 'date', 'after:publish_at'],
            'requires_acknowledgement' => ['boolean'],
            'is_pinned' => ['boolean'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png,zip', 'max:10240'],
        ];
    }

    /**
     * Build target options for the audience multi-select dropdowns.
     */
    private function getTargetOptions(): array
    {
        $companyId = Auth::user()->company_id;

        return [
            'departments' => Department::orderBy('name')->pluck('name', 'id'),

            // Was completely unscoped — every tenant's stores appeared here and
            // could be targeted.
            'stores' => auth_stores()->orderBy('name')->pluck('name', 'id'),

            'designations' => Designation::orderBy('name')->pluck('name', 'id'),

            // Owner is excluded the same way it is on the Users screen; it is
            // an identity, not an audience.
            'roles' => Role::where(fn ($q) => $q->where('company_id', $companyId)->orWhereNull('company_id'))
                ->where('slug', '!=', 'owner')
                ->orderBy('name')
                ->pluck('name', 'id'),

            // Inactive logins cannot read anything, so listing them only makes
            // the audience look larger than it is.
            'users' => User::internal()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ];
    }

    // ── Download Attachment ──

    // ── Download Attachment ──

    public function downloadAttachment(Announcement $announcement)
    {
        $this->authorize('view', $announcement);

        if (! $announcement->attachment) {
            return back()->with('error', 'No attachment mapped to this announcement.');
        }

        // Resolved through the disk rather than a hardcoded storage_path(), so
        // moving the file between disks cannot silently break this download.
        if (! Storage::disk('local')->exists($announcement->attachment)) {
            return back()->with('error', 'The file was deleted or is missing from the server.');
        }

        return response()->download(
            Storage::disk('local')->path($announcement->attachment),
            $announcement->attachment_name ?? 'announcement-document'
        );
    }
}
