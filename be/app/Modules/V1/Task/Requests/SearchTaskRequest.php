<?php

namespace App\Modules\V1\Task\Requests;

use App\Enums\SortDirection;
use App\Helpers\CommonFormRequest;
use App\Modules\V1\Task\Enums\TaskSortField;
use App\Modules\V1\Task\Enums\TaskStatus;
use Illuminate\Validation\Rule;

class SearchTaskRequest extends CommonFormRequest
{
    /**
     * Validation constraints for this request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'array', 'list', 'max:'.count(TaskStatus::cases())],
            'status.*' => ['required', 'distinct', Rule::enum(TaskStatus::class)],
            'assigned_to' => ['sometimes', 'array', 'list', 'max:'.config('task_manager.filters.maximum_assignees')],
            'assigned_to.*' => ['required', 'integer', 'min:1', 'distinct'],
            'sort' => ['sometimes', 'array', 'list', 'max:'.count(TaskSortField::cases())],
            'sort.*' => ['required', 'array:field,direction'],
            'sort.*.field' => ['required', 'distinct', Rule::enum(TaskSortField::class)],
            'sort.*.direction' => ['required', Rule::enum(SortDirection::class)],
            'search' => [
                'nullable',
                'string',
                'max:'.config('task_manager.limits.title'),
            ],
            'page' => [
                'sometimes',
                'integer',
                'min:1',
            ],
            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:'.config('task_manager.pagination.maximum'),
            ],
        ];
    }
}
