<?php

namespace App\Modules\V1\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\V1\User\Services\Interfaces\UserServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends Controller
{
    /** Inject module dependencies through their interfaces. */
    public function __construct(private UserServiceInterface $users) {}

    /** Return the accounts available for assignment. */
    public function index(Request $request): Response
    {
        return $this->users->assignees($request->user());
    }
}
