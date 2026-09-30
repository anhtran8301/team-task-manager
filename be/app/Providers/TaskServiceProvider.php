<?php

namespace App\Providers;

use App\Modules\V1\Task\Repositories\Interfaces\TaskRepositoryInterface;
use App\Modules\V1\Task\Repositories\TaskRepository;
use App\Modules\V1\Task\Services\Interfaces\TaskServiceInterface;
use App\Modules\V1\Task\Services\TaskService;
use Illuminate\Support\ServiceProvider;

class TaskServiceProvider extends ServiceProvider
{
    /** Bind module interfaces to their Eloquent-backed implementations. */
    public function register(): void
    {
        $this->app->bind(TaskRepositoryInterface::class, TaskRepository::class);
        $this->app->bind(TaskServiceInterface::class, TaskService::class);
    }
}
