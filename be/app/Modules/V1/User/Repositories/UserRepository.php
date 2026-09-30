<?php

namespace App\Modules\V1\User\Repositories;

use App\Modules\V1\User\Models\User;
use App\Modules\V1\User\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    /** Find an account for authentication. */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /** List selectable accounts within the actor role scope. */
    public function assignees(User $actor): Collection
    {
        return User::query()->select('id', 'name', 'email', 'role')
            ->when(! $actor->isAdmin(), fn ($query) => $query->whereKey($actor->id))->orderBy('name')->get();
    }
}
