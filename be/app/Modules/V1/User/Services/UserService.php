<?php

namespace App\Modules\V1\User\Services;

use App\Helpers\TransformerResponse;
use App\Modules\V1\User\Models\User;
use App\Modules\V1\User\Repositories\Interfaces\UserRepositoryInterface;
use App\Modules\V1\User\Services\Interfaces\UserServiceInterface;
use Illuminate\Http\Response;

class UserService implements UserServiceInterface
{
    /** Inject module dependencies through their interfaces. */
    public function __construct(private UserRepositoryInterface $users, private TransformerResponse $transformerResponse) {}

    /** List selectable accounts within the actor permission scope. */
    public function assignees(User $actor): Response
    {
        return $this->transformerResponse->response(data: $this->users->assignees($actor));
    }
}
