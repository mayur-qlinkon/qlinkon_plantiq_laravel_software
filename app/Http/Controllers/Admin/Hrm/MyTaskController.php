<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\HrmTask;
use App\Models\Hrm\HrmTaskAttachment;
use App\Models\Hrm\HrmTaskComment;
use App\Models\Hrm\HrmTaskActivity;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MyTaskController extends Controller
{
    protected function myEmployee()
    {
        $emp = Auth::user()->employee;
        abort_if(! $emp, 403, 'No employee record linked to your account.');

        return $emp;
    }

    /** Scoped query — only tasks assigned to this employee */
    protected function myTaskQuery()
    {
        $employee = $this->myEmployee();

        return HrmTask::whereHas('assignments', fn ($q) => $q->where('employee_id', $employee->id));
    }

    public function index(Request $request)
    {
        if (! Auth::user()->employee) {
            return view('admin.hrm.employee.no-profile');
        }
        $employee = $this->myEmployee();
        $query = $this->myTaskQuery()->with(['assignments.employee.user', 'attachments', 'allComments']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $tasks = $query->orderByRaw("FIELD(priority,'urgent','high','medium','low')")
            ->orderBy('due_date')
            ->get();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $tasks]);
        }

        // Board groups
        $board = [];
        foreach (HrmTask::STATUS_LABELS as $status => $label) {
            $board[$status] = $tasks->where('status', $status)->values();
        }

        return view('admin.hrm.my-tasks.index', compact('tasks', 'board', 'employee'));
    }

    /** Return task detail + comments + attachments as JSON for the slide panel */
    public function show(HrmTask $task)
    {
        $this->authorizeTask($task);

        $task->load([
            'allComments.user',
            'attachments.uploadedByUser',
            'createdByUser',
            'assignments.employee.user',
        ]);

        $allowedTransitions = HrmTask::STATUS_TRANSITIONS[$task->status] ?? [];

        return response()->json([
            'success' => true,
            'data' => $task,
            'allowed_transitions' => $allowedTransitions,
        ]);
    }

    /** Employee updates task status and/or progress */
    public function updateProgress(Request $request, HrmTask $task)
    {
        $this->authorizeTask($task);

        $validated = $request->validate([
            'progress_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'status' => ['nullable', Rule::in(array_keys(HrmTask::STATUS_LABELS))],
        ]);

        $oldStatus = $task->status;

        if (isset($validated['status']) && $validated['status'] !== $task->status) {
            if (! $task->canTransitionTo($validated['status'])) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot move task from '{$task->status_label}' to '{$validated['status']}'.",
                ], 422);
            }
            if ($validated['status'] === HrmTask::STATUS_COMPLETED) {
                $validated['completed_at'] = now();
                $validated['progress_percent'] = 100;
            }
            if ($validated['status'] === HrmTask::STATUS_IN_PROGRESS && is_null($task->started_at)) {
                $validated['started_at'] = now();
            }
        }

        $task->update(array_filter($validated, fn ($v) => ! is_null($v)));

        if (isset($validated['status']) && $validated['status'] !== $oldStatus) {
            HrmTaskActivity::create([
                'hrm_task_id' => $task->id,
                'user_id'     => Auth::id(),
                'event'       => 'status_changed',
                'description' => 'Status changed from '.HrmTask::STATUS_LABELS[$oldStatus].' to '.HrmTask::STATUS_LABELS[$validated['status']],
                'old_value'   => $oldStatus,
                'new_value'   => $validated['status'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Progress updated.',
            'data' => $task->fresh(),
        ]);
    }

    /** Employee adds a comment */
    public function addComment(Request $request, HrmTask $task)
    {
        $this->authorizeTask($task);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'exists:hrm_task_comments,id'],
        ]);

        $comment = HrmTaskComment::create([
            'hrm_task_id' => $task->id,
            'user_id' => Auth::id(),
            'body' => $validated['body'],
            'parent_id' => $validated['parent_id'] ?? null,
            'is_system' => false,
        ]);

        $comment->load('user');

        HrmTaskActivity::create([
            'hrm_task_id' => $task->id,
            'user_id'     => Auth::id(),
            'event'       => 'comment_added',
            'description' => 'Comment added',
        ]);

        return response()->json(['success' => true, 'data' => $comment]);
    }

    /** Employee uploads an attachment */
    public function uploadAttachment(Request $request, HrmTask $task)
    {
        $this->authorizeTask($task);

        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,gif,zip,doc,docx,xls,xlsx,txt'],
        ]);

        $file = $request->file('file');
        // 'local' (storage/app/private) — the download action reads from this
        // disk, and a public-disk write would also leave the file fetchable at
        // /storage/<path> with no session and no ownership check.
        $path = $file->store("hrm/task-attachments/{$task->id}", 'local');


        $attachment = HrmTaskAttachment::create([
            'hrm_task_id' => $task->id,
            'uploaded_by' => Auth::id(),
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        $attachment->load('uploadedByUser');

        HrmTaskActivity::create([
            'hrm_task_id' => $task->id,
            'user_id'     => Auth::id(),
            'event'       => 'file_uploaded',
            'description' => 'File uploaded: '.$file->getClientOriginalName(),
            'meta'        => ['file_name' => $file->getClientOriginalName(), 'file_size' => $file->getSize()],
        ]);

        return response()->json(['success' => true, 'data' => $attachment]);
    }

    /** Download an attachment — available to anyone assigned or who uploaded */
    public function downloadAttachment(HrmTaskAttachment $attachment)
    {
        // Must be assigned to the task OR be the uploader
        $employee = $this->myEmployee();
        $isAssigned = HrmTask::where('id', $attachment->hrm_task_id)
            ->whereHas('assignments', fn ($q) => $q->where('employee_id', $employee->id))
            ->exists();

        abort_if(! $isAssigned && $attachment->uploaded_by !== Auth::id(), 403);

        if (! Storage::disk('local')->exists($attachment->file_path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('local')->download($attachment->file_path, $attachment->file_name);
    }

    /**
     * Comment thread for a task assigned to the current employee.
     *
     * Mirrors HrmTaskController::getComments() so the shared _comments partial
     * renders identically on both sides. authorizeTask() keeps an employee from
     * reading a thread on someone else's task.
     */
    public function getComments(HrmTask $task)
    {
        $this->authorizeTask($task);

        $comments = $task->comments()
            ->whereNull('parent_id')
            ->reorder()
            ->orderBy('id', 'desc')
            ->with([
                'user.employee',
                'replies' => fn ($query) => $query->reorder()->orderBy('id', 'desc')->with('user.employee'),
            ])
            ->get()
            ->map(fn ($comment) => $this->formatCommentData($comment));

        return response()->json([
            'comments' => $comments,
            'count'    => $task->comments()->count(),
        ]);
    }

    protected function formatCommentData($comment)
    {
        $userName = $comment->user->name ?? 'Unknown User';

        $avatarUrl = $comment->user->avatar_url
            ?? 'https://ui-avatars.com/api/?name='.urlencode($userName).'&color=1f2937&background=f3f4f6';

        return [
            'id'               => $comment->id,
            'body'             => $comment->body,
            'is_system'        => (bool) $comment->is_system,
            'user_name'        => $userName,
            'avatar_url'       => $avatarUrl,
            'created_at_human' => $comment->created_at->diffForHumans(),
            'replies'          => $comment->relationLoaded('replies')
                                    ? $comment->replies->map(fn ($r) => $this->formatCommentData($r))
                                    : [],
        ];
    }

    protected function authorizeTask(HrmTask $task): void
    {
        $employee = $this->myEmployee();
        $assigned = $task->assignments()->where('employee_id', $employee->id)->exists();
        abort_if(! $assigned, 403, 'You are not assigned to this task.');
    }
}
