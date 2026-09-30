<?php

namespace App\Modules\V1\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\V1\Auth\Requests\AuthLoginRequest;
use App\Modules\V1\Auth\Services\Interfaces\AuthServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    /** Inject authentication use cases. */
    public function __construct(private AuthServiceInterface $auth) {}

    /** Exchange validated credentials for an HttpOnly cookie. */
    public function login(AuthLoginRequest $request): Response
    {
        return $this->auth->login($request->validated(), $request);
    }

    /** Read the authenticated account. */
    public function me(Request $request): Response
    {
        return $this->auth->me($request->user());
    }

    /** End the current browser session. */
    public function logout(Request $request): Response
    {
        return $this->auth->logout($request);
    }

    /** Bootstrap CSRF cookies before login or another mutation. */
    public function csrfCookie(): Response
    {
        return $this->auth->csrfCookie();
    }
}
