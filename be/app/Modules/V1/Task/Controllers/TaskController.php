<?php

namespace App\Modules\V1\Task\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\V1\Task\Models\Task;
use App\Modules\V1\Task\Requests\SearchTaskRequest;
use App\Modules\V1\Task\Requests\StoreTaskRequest;
use App\Modules\V1\Task\Requests\UpdateTaskRequest;
use App\Modules\V1\Task\Services\Interfaces\TaskServiceInterface;
use Illuminate\Http\Response;

class TaskController extends Controller
{
    /** Inject module dependencies through their interfaces. */
    public function __construct(private TaskServiceInterface $tasks) {}

    /** Return the authorized, filtered task page. */
    public function index(SearchTaskRequest $request): Response
    {
        return $this->tasks->index($request->user(), $request->validated());
    }

    /** Create a task after checking creation and assignment permissions. */
    public function store(StoreTaskRequest $request): Response
    {
        return $this->tasks->store($request->validated());
    }

    /** Update an existing task only after checking ownership and the new assignee. */
    public function update(UpdateTaskRequest $request, Task $task): Response
    {
        return $this->tasks->update($task, $request->validated());
    }

    /** Hard-delete an authorized task and return the shared success envelope. */
    public function destroy(Task $task): Response
    {
        return $this->tasks->destroy($task);
    }
}
