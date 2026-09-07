<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Todo;
use App\Services\Hrm\TodoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TodoController extends Controller
{
    public function __construct(protected TodoService $todos) {}
    
    // ── Dashboard widget data (always JSON — embedded on employee dashboard) ──
    public function index()
    {
        return response()->json($this->todos->getDashboardTodos());
    }

    // ── Create ────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string', 'max:1000'],
            'priority'     => ['nullable', 'in:low,medium,high'],
            'due_date'     => ['nullable', 'date', 'after_or_equal:today'],
            'is_important' => ['nullable', 'boolean'],
        ]);

        $todo = $this->todos->create($validated);

        return response()->json([
            'success' => true,
            'todo'    => $todo,
            'message' => 'Task added.',
        ], 201);
    }

    // ── Update ────────────────────────────────────────────────────────
    public function update(Request $request, Todo $todo)
    {
        $this->authorizeTodo($todo);

        $validated = $request->validate([
            'title'        => ['sometimes', 'required', 'string', 'max:255'],
            'description'  => ['nullable', 'string', 'max:1000'],
            'priority'     => ['nullable', 'in:low,medium,high'],
            'due_date'     => ['nullable', 'date'],
            'is_important' => ['nullable', 'boolean'],
        ]);

        $todo = $this->todos->update($todo, $validated);

        return response()->json([
            'success' => true,
            'todo'    => $todo,
        ]);
    }

    // ── Toggle complete ───────────────────────────────────────────────
    public function toggleComplete(Todo $todo)
    {
        $this->authorizeTodo($todo);

        $todo = $this->todos->toggleComplete($todo);

        return response()->json([
            'success' => true,
            'status'  => $todo->status,
            'todo'    => $todo,
        ]);
    }

    // ── Toggle important ─────────────────────────────────────────────
    public function toggleImportant(Todo $todo)
    {
        $this->authorizeTodo($todo);

        $todo = $this->todos->toggleImportant($todo);

        return response()->json([
            'success'      => true,
            'is_important' => $todo->is_important,
        ]);
    }

    // ── Reorder ───────────────────────────────────────────────────────
    public function reorder(Request $request)
    {
        $request->validate([
            'ordered'              => ['required', 'array'],
            'ordered.*.id'         => ['required', 'integer'],
            'ordered.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $this->todos->reorder($request->ordered);

        return response()->json(['success' => true]);
    }

    // ── Delete ────────────────────────────────────────────────────────
    public function destroy(Todo $todo)
    {
        $this->authorizeTodo($todo);

        $this->todos->delete($todo);

        return response()->json([
            'success' => true,
            'message' => 'Task deleted.',
        ]);
    }

    // ── Guard: ensure todo belongs to this user ───────────────────────
    // Using inline check instead of a Policy to keep it simple for
    // personal todos — no need for role-based ownership here.
    private function authorizeTodo(Todo $todo): void
    {
        if ($todo->user_id !== Auth::id() || $todo->company_id !== Auth::user()->company_id) {
            abort(403, 'This task does not belong to you.');
        }
    }
}