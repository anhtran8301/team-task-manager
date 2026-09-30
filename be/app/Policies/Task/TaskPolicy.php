<?php

namespace App\Policies\Task;

use App\Modules\V1\Task\Models\Task;
use App\Modules\V1\User\Enums\UserRole;
use App\Modules\V1\User\Models\User;

class TaskPolicy
{
    /** The repository applies assignee scope after this role check. */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->hasRole(UserRole::User);
    }

    /** Assignment is authorized separately after input validation. */
    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    /** Ownership means assigned_to, regardless of who created the task. */
    public function update(User $user, Task $task): bool
    {
        return $user->isAdmin() || ($user->hasRole(UserRole::User) && $task->assigned_to === $user->id);
    }

    /** The same role and ownership rules apply to deletion. */
    public function delete(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }

    /** Form Requests validate existence before checking the proposed assignee. */
    public function assign(User $user, int $assigneeId): bool
    {
        return $user->isAdmin() || ($user->hasRole(UserRole::User) && $assigneeId === $user->id);
    }
}
