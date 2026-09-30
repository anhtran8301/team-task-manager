<?php

namespace App\Modules\V1\Task\Repositories\Interfaces;

use App\Modules\V1\Task\Models\Task;
use App\Modules\V1\User\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TaskRepositoryInterface
{
    /**
     * Apply ownership before user filters and eagerly load assignees.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(User $actor, array $filters): LengthAwarePaginator;

    /**
     * Persist validated task attributes.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Task;

    /** Persist validated attributes; the service must authorize ownership and assignment first. */
    public function update(Task $task, array $data): Task;

    /** Hard-delete the supplied task; callers must authorize first. */
    public function delete(Task $task): void;
}
