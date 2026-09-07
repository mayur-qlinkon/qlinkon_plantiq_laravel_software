<?php

namespace App\Services\Hrm;

use App\Models\Todo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TodoService
{
    // ── Create ───────────────────────────────────────────────────────
    public function create(array $data): Todo
    {
        $maxOrder = Todo::mine()->max('sort_order') ?? 0;

        return Todo::create([
            'company_id'   => Auth::user()->company_id,
            'user_id'      => Auth::id(),
            'title'        => $data['title'],
            'description'  => $data['description']  ?? null,
            'priority'     => $data['priority']      ?? 'medium',
            'due_date'     => $data['due_date']       ?? null,
            'is_important' => $data['is_important']  ?? false,
            'sort_order'   => $maxOrder + 1,
        ]);
    }

    // ── Update ───────────────────────────────────────────────────────
    public function update(Todo $todo, array $data): Todo
    {
        $todo->update([
            'title'        => $data['title']        ?? $todo->title,
            'description'  => $data['description']  ?? $todo->description,
            'priority'     => $data['priority']      ?? $todo->priority,
            'due_date'     => $data['due_date']       ?? $todo->due_date,
            'is_important' => $data['is_important']  ?? $todo->is_important,
        ]);

        return $todo->fresh();
    }

    // ── Toggle complete / reopen ─────────────────────────────────────
    public function toggleComplete(Todo $todo): Todo
    {
        if ($todo->status === 'completed') {
            $todo->update([
                'status'       => 'pending',
                'completed_at' => null,
            ]);
        } else {
            $todo->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);
        }

        return $todo->fresh();
    }

    // ── Toggle important flag ────────────────────────────────────────
    public function toggleImportant(Todo $todo): Todo
    {
        $todo->update(['is_important' => ! $todo->is_important]);

        return $todo->fresh();
    }

    // ── Reorder (drag-and-drop) ──────────────────────────────────────
    // $ordered = [['id' => 3, 'sort_order' => 1], ['id' => 7, 'sort_order' => 2], ...]
    public function reorder(array $ordered): void
    {
        DB::transaction(function () use ($ordered) {
            foreach ($ordered as $item) {
                Todo::mine()
                    ->where('id', $item['id'])
                    ->update(['sort_order' => $item['sort_order']]);
            }
        });
    }

    // ── Delete ───────────────────────────────────────────────────────
    public function delete(Todo $todo): void
    {
        $todo->delete();
    }

    // ── Fetch for dashboard (grouped) ────────────────────────────────
    public function getDashboardTodos(): array
    {
        $base = Todo::mine()->orderBy('sort_order')->orderByDesc('is_important');

        return [
            'pending'   => (clone $base)->pending()->get(),
            'completed' => (clone $base)->completed()->latest('completed_at')->limit(20)->get(),
            'overdue'   => (clone $base)->pending()
                               ->whereNotNull('due_date')
                               ->whereDate('due_date', '<', today())
                               ->get(),
            'counts' => [
                'pending'   => (clone $base)->pending()->count(),
                'completed' => (clone $base)->completed()->count(),
                'important' => (clone $base)->important()->pending()->count(),
                'overdue'   => (clone $base)->pending()
                                   ->whereNotNull('due_date')
                                   ->whereDate('due_date', '<', today())
                                   ->count(),
            ],
        ];
    }
}