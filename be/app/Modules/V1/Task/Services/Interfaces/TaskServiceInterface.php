<?php

namespace App\Modules\V1\Task\Services\Interfaces;

use App\Modules\V1\Task\Models\Task;
use App\Modules\V1\User\Models\User;
use Illuminate\Http\Response;

interface TaskServiceInterface
{
    /** Return the authorized, filtered task page. */
    public function index(User $actor, array $filters): Response;

    /** Persist a task authorized by the HTTP boundary; internal callers must authorize first. */
    public function store(array $data): Response;

    /** Persist an authorized update; internal callers must check ownership and assignment first. */
    public function update(Task $task, array $data): Response;

    /** Hard-delete an authorized task and return the shared success envelope. */
    public function destroy(Task $task): Response;
}
