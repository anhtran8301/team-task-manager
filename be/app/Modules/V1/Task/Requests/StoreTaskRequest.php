<?php

namespace App\Modules\V1\Task\Requests;

use App\Helpers\CommonFormRequest;
use App\Modules\V1\Task\Enums\TaskStatus;
use App\Modules\V1\Task\Models\Task;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends CommonFormRequest
{
    /**
     * Validation constraints for this request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:'.config('task_manager.limits.title'),
            ],
            'description' => [
                'nullable',
                'string',
                'max:'.config('task_manager.limits.description'),
            ],
            'status' => [
                'required',
                Rule::enum(TaskStatus::class),
            ],
            'assigned_to' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'due_date' => [
                'nullable',
                'date_format:Y-m-d',
            ],
        ];
    }

    /** Authorize only validated assignment; routes already check the current task. */
    protected function passedValidation(): void
    {
        Gate::authorize('assign', [Task::class, (int) $this->validated('assigned_to')]);
    }
}
