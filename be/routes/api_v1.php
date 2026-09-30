<?php

use App\Modules\V1\Auth\Controllers\AuthController;
use App\Modules\V1\Task\Controllers\TaskController;
use App\Modules\V1\Task\Models\Task;
use App\Modules\V1\User\Controllers\UserController;
use App\Modules\V1\User\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('csrf-cookie', [AuthController::class, 'csrfCookie']);
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('users', [UserController::class, 'index'])->can('viewAny', User::class);
    Route::get('tasks', [TaskController::class, 'index'])->can('viewAny', Task::class);
    Route::post('tasks', [TaskController::class, 'store'])->can('create', Task::class);
    Route::put('tasks/{task}', [TaskController::class, 'update'])->can('update', 'task');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->can('delete', 'task');
});
