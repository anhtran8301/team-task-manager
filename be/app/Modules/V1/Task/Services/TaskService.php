<?php

namespace App\Modules\V1\Task\Services;

use App\Enums\InternalCodeEnum;
use App\Helpers\TransformerResponse;
use App\Modules\V1\Task\Models\Task;
use App\Modules\V1\Task\Repositories\Interfaces\TaskRepositoryInterface;
use App\Modules\V1\Task\Services\Interfaces\TaskServiceInterface;
use App\Modules\V1\User\Models\User;
use Illuminate\Http\Response;

class TaskService implements TaskServiceInterface
{
    /** Inject module dependencies through their interfaces. */
    public function __construct(private TaskRepositoryInterface $tasks, private TransformerResponse $transformerResponse) {}

    /** Return the authorized, filtered task page. */
    public function index(User $actor, array $filters): Response
    {
        return $this->transformerResponse->response(data: $this->tasks->paginate($actor, $filters));
    }

    /** Persist a task authorized by the HTTP boundary; internal callers must authorize first. */
    public function store(array $data): Response
    {
        return $this->transformerResponse->response(data: $this->tasks->create($data), code: Response::HTTP_CREATED, message: InternalCodeEnum::TASK_CREATED);
    }

    /** Persist an authorized update; internal callers must check ownership and assignment first. */
    public function update(Task $task, array $data): Response
    {
        return $this->transformerResponse->response(data: $this->tasks->update($task, $data), message: InternalCodeEnum::TASK_UPDATED);
    }

    /** Hard-delete an authorized task and return the shared success envelope. */
    public function destroy(Task $task): Response
    {
        $this->tasks->delete($task);

        return $this->transformerResponse->response(message: InternalCodeEnum::TASK_DELETED);
    }
}
