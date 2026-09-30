<?php

namespace App\Modules\V1\Task\Models;

use App\Modules\V1\Task\Enums\TaskStatus;
use App\Modules\V1\User\Models\User;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description', 'status', 'assigned_to', 'due_date'];

    /**
     * Eloquent casts preserve date-only due dates.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['status' => TaskStatus::class, 'assigned_to' => 'integer', 'due_date' => 'date:Y-m-d'];
    }

    /** Resolve the factory for this module model. */
    protected static function newFactory(): TaskFactory
    {
        return TaskFactory::new();
    }

    /**
     * Account responsible for this task.
     *
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
