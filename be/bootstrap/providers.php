<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\TaskServiceProvider;
use App\Providers\UserServiceProvider;

return [AppServiceProvider::class, AuthServiceProvider::class,
    UserServiceProvider::class, TaskServiceProvider::class];
