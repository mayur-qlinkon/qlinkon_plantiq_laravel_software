<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Models\Hrm\HrmTask;
use App\Models\Hrm\HrmTaskAttachment;
use App\Services\Hrm\HrmTaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class HrmTaskController extends Controller
{
    public function __construct(
        protected HrmTaskService $taskService
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['status', 'priority', 'project', 'employee_id', 'overdue', 'search', 'per_page']);
        $tasks = $this->taskService->getList($filters);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $tasks]);
        }

        $employees = Employee::active()->with('user')->get();

        return view('admin.hrm.tasks.index', compact('tasks', 'employees', 'filters'));
    }

    public function create()
    {
        $employees = Employee::active()->with('user')->get();

        return view('admin.hrm.tasks.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'project' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'assignees' => ['nullable', 'array'],
            'assignees.*' => ['exists:employees,id'],
            'primary_assignee' => ['nullable', 'exists:employees,id'],
        ]);

        try {
            $task = $this->taskService->create($validated);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Task created.', 'data' => $task]);
            }

            return redirect()->route('admin.hrm.tasks.show', $task)
                ->with('success', 'Task created successfully.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function edit(HrmTask $task)
    {
        $this->guardActiveStore($task);
        $employees = Employee::active()->with('user')->get();
        $task->load('assignees');

        return view('admin.hrm.tasks.edit', compact('task', 'employees'));
    }

    public function show(HrmTask $task)
    {
        $this->guardActiveStore($task);
        $task->load([
            'createdByUser', 'assignees.user',
            'attachments.uploadedByUser',
            'comments.user', 'comments.replies.user',
            'activities.user',
        ]);

        return view('admin.hrm.tasks.show', compact('task'));
    }

    public function update(Request $request, HrmTask $task)
    {
        $this->guardActiveStore($task);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'project' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'progress_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'assignees' => ['nullable', 'array'],
            'assignees.*' => ['exists:employees,id'],
            'primary_assignee' => ['nullable', 'exists:employees,id'],
        ]);

        try {
            $task = $this->taskService->update($task, $validated);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Task updated.', 'data' => $task]);
            }

            return redirect()->route('admin.hrm.tasks.show', $task)
                ->with('success', 'Task updated successfully.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function destroy(HrmTask $task)
    {
        $this->guardActiveStore($task);
        $this->taskService->delete($task);

        return response()->json(['success' => true, 'message' => 'Task deleted.']);
    }

    /**
     * Update task status.
     */
    public function updateStatus(Request $request, HrmTask $task)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(HrmTask::STATUS_LABELS))],
            'note' => ['nullable', 'string'],
        ]);

        try {
            $task = $this->taskService->updateStatus($task, $validated['status'], $validated['note'] ?? null);

            return response()->json(['success' => true, 'message' => 'Task status updated.', 'data' => $task]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Add comment to task.
     */
    public function addComment(Request $request, HrmTask $task)
    {
        $validated = $request->validate([
            'body' => ['required', 'string'],
            'parent_id' => ['nullable', 'exists:hrm_task_comments,id'],
        ]);

        $comment = $this->taskService->addComment($task, $validated);

        return response()->json(['success' => true, 'message' => 'Comment added.', 'data' => $comment->load('user')]);
    }

    /**
     * Fetch comments via AJAX for Alpine polling.
     */
    public function getComments(HrmTask $task)
    {
        $comments = $task->comments()
            ->whereNull('parent_id')
            ->reorder() // 🌟 STRIPS any default ascending order from the model
            ->orderBy('id', 'desc') // 🌟 Forces newest at the top
            ->with([
                'user.employee', 
                'replies' => fn($query) => $query->reorder()->orderBy('id', 'desc')->with('user.employee') 
            ]) 
            ->get()
            ->map(function ($comment) {
                return $this->formatCommentData($comment);
            });

        return response()->json([
            'comments' => $comments,
            'count'    => $task->comments()->count(),
        ]);
    }

    /**
     * Format the comment payload to keep the JSON lightweight.
     */
    protected function formatCommentData($comment)
    {
        $userName = $comment->user->name ?? 'Unknown User';
        
        // Use existing avatar or fallback to UI-Avatars
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
                                    ? $comment->replies->map(fn($r) => $this->formatCommentData($r))
                                    : [],
        ];
    }

    /**
     * Upload attachment to task.
     */
    public function addAttachment(Request $request, HrmTask $task)
    {
        $this->guardActiveStore($task);

        $request->validate([
            // Mirrors the employee-side rule in MyTaskController::uploadAttachment.
            // Without it this endpoint accepted any file type at all.
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,gif,zip,doc,docx,xls,xlsx,txt'],
        ]);

        $attachment = $this->taskService->addAttachment($task, $request->file('file'));

        return response()->json(['success' => true, 'message' => 'Attachment uploaded.', 'data' => $attachment]);
    }

    /**
     * Download an attachment.
     */
    public function downloadAttachment(int $attachment)
    {
        // Resolved through owned() rather than route-model binding. Binding
        // resolves by primary key alone, so any attachment id from any company
        // was downloadable by whoever guessed it.
        $attachment = HrmTaskAttachment::owned()->with('task')->findOrFail($attachment);

        // owned() enforces the tenant boundary but says nothing about branches.
        // Every other task action runs this, so the attachment routes were the
        // only way to reach another store's file.
        $this->guardActiveStore($attachment->task);

        abort_if(! Storage::disk('local')->exists($attachment->file_path), 404, 'File not found.');

        return response()->download(
            Storage::disk('local')->path($attachment->file_path),
            $attachment->file_name ?? $attachment->original_name
        );
    }

    /**
     * Delete attachment.
     */
    public function deleteAttachment(int $attachment)
    {
        // Same binding problem as downloadAttachment, but destructive: this
        // removed the file from disk and the row from the database for any
        // company's attachment.
        $attachment = HrmTaskAttachment::owned()->with('task')->findOrFail($attachment);

        $this->guardActiveStore($attachment->task);

        $this->taskService->deleteAttachment($attachment);

        return response()->json(['success' => true, 'message' => 'Attachment deleted.']);
    }

    /**
     * A task belongs to a store through whoever it is assigned to. An unassigned
     * task has no store, so it stays visible everywhere — hiding it would leave
     * it unreachable from every branch.
     */
    protected function guardActiveStore(HrmTask $hrmTask): void
    {
        $storeId = active_store()?->id;

        if (! $storeId) {
            return;
        }

        $hrmTask->loadMissing('assignees:id,store_id');

        if ($hrmTask->assignees->isEmpty()) {
            return;
        }

        if (! $hrmTask->assignees->contains('store_id', $storeId)) {
            abort(404);
        }
    }
}
