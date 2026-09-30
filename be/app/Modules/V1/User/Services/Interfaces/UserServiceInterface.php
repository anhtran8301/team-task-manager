<?php

namespace App\Modules\V1\User\Services\Interfaces;

use App\Modules\V1\User\Models\User;
use Illuminate\Http\Response;

interface UserServiceInterface
{
    /** List selectable accounts within the actor permission scope. */
    public function assignees(User $actor): Response;
}
