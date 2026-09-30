<?php

namespace App\Modules\V1\User\Repositories\Interfaces;

use App\Modules\V1\User\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    /** Find an account for authentication. */
    public function findByEmail(string $email): ?User;

    /** List selectable accounts within the actor role scope. */
    public function assignees(User $actor): Collection;
}
