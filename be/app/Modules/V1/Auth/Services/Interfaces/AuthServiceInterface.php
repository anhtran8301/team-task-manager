<?php

namespace App\Modules\V1\Auth\Services\Interfaces;

use App\Modules\V1\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

interface AuthServiceInterface
{
    /** @param array{email: string, password: string} $credentials */
    public function login(array $credentials, Request $request): Response;

    /** Return the current account. */
    public function me(User $user): Response;

    /** Revoke the current personal access token. */
    public function logout(Request $request): Response;

    /** Initialize native CSRF protection. */
    public function csrfCookie(): Response;
}
