<?php

namespace App\Providers;

use App\Modules\V1\User\Repositories\Interfaces\UserRepositoryInterface;
use App\Modules\V1\User\Repositories\UserRepository;
use App\Modules\V1\User\Services\Interfaces\UserServiceInterface;
use App\Modules\V1\User\Services\UserService;
use Illuminate\Support\ServiceProvider;

class UserServiceProvider extends ServiceProvider
{
    /** Bind module interfaces to their Eloquent-backed implementations. */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(UserServiceInterface::class, UserService::class);
    }
}
