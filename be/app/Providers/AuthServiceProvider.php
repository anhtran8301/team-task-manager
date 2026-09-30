<?php

namespace App\Providers;

use App\Modules\V1\Auth\Services\AuthService;
use App\Modules\V1\Auth\Services\Interfaces\AuthServiceInterface;
use App\Modules\V1\Task\Models\Task;
use App\Modules\V1\User\Models\User;
use App\Policies\Task\TaskPolicy;
use App\Policies\User\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /** Associate module models with Laravel's standard policy abilities. */
    public function boot(): void
    {
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }

    /** Bind module interfaces to their Eloquent-backed implementations. */
    public function register(): void
    {
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
    }
}
