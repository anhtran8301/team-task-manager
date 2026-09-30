<?php

namespace App\Policies\User;

use App\Modules\V1\User\Enums\UserRole;
use App\Modules\V1\User\Models\User;

class UserPolicy
{
    /** The repository returns all assignees for admins and only self for users. */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->hasRole(UserRole::User);
    }
}
