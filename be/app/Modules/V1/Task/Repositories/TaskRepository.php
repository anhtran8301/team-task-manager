<?php

namespace App\Modules\V1\Task\Repositories;

use App\Enums\SortDirection;
use App\Modules\V1\Task\Enums\TaskSortField;
use App\Modules\V1\Task\Models\Task;
use App\Modules\V1\Task\Repositories\Interfaces\TaskRepositoryInterface;
use App\Modules\V1\User\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TaskRepository implements TaskRepositoryInterface
{
    /**
     * Apply ownership before user filters and eagerly load assignees.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(User $actor, array $filters): LengthAwarePaginator
    {
        $query = Task::query()->select('tasks.*')->with('assignee')
            ->when(! $actor->isAdmin(), fn ($q) => $q->where('tasks.assigned_to', $actor->id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->whereIn('tasks.status', $status))
            ->when($filters['assigned_to'] ?? null, fn ($q, $id) => $q->whereIn('tasks.assigned_to', $id))
            ->when(isset($filters['search']) && $filters['search'] !== '', function ($q) use ($filters) {
                $q->whereFullText('tasks.title', $filters['search']);
            });

        return $this->applySort($query, $filters['sort'] ?? [])->paginate($filters['per_page'] ?? config('task_manager.pagination.default'))->withQueryString();
    }

    /**
     * Apply ordered, allowlisted criteria and a stable final tie-breaker.
     *
     * @param  Builder<Task>  $query
     * @param  list<array{field: string, direction: string}>  $sort  Validated request criteria.
     * @return Builder<Task>
     */
    private function applySort(Builder $query, array $sort): Builder
    {
        foreach ($sort as $criterion) {
            $field = TaskSortField::from($criterion['field']);
            $direction = SortDirection::from($criterion['direction']);
            if ($field === TaskSortField::Assignee) {
                $query->join('users as sort_assignee', 'sort_assignee.id', '=', 'tasks.assigned_to');
            }
            if ($field === TaskSortField::DueDate) {
                // Fixed SQL: null dates follow dated tasks within preceding sort groups.
                $query->orderByRaw('tasks.due_date IS NULL ASC');
            }
            $query->orderBy($field->column(), $direction->value);
        }

        return $query->orderByDesc('tasks.id');
    }

    /**
     * Persist validated task attributes.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Task
    {
        return Task::create($data)->load('assignee');
    }

    /** Persist validated attributes; the service must authorize ownership and assignment first. */
    public function update(Task $task, array $data): Task
    {
        $task->update($data);

        return $task->refresh()->load('assignee');
    }

    /** Hard-delete the supplied task; callers must authorize first. */
    public function delete(Task $task): void
    {
        $task->delete();
    }
}
