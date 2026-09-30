<?php

namespace App\Modules\V1\Task\Enums;

enum TaskSortField: string
{
    case Title = 'title';
    case Assignee = 'assignee';
    case DueDate = 'due_date';

    /** Map public API keys to trusted, qualified SQL columns. */
    public function column(): string
    {
        return match ($this) {
            self::Title => 'tasks.title',
            self::Assignee => 'sort_assignee.name',
            self::DueDate => 'tasks.due_date',
        };
    }
}
