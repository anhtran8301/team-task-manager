<?php

namespace App\Modules\V1\Auth\Services;

use App\Enums\InternalCodeEnum;
use App\Helpers\TransformerResponse;
use App\Modules\V1\Auth\Services\Interfaces\AuthServiceInterface;
use App\Modules\V1\User\Models\User;
use App\Modules\V1\User\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Cookie;

class AuthService implements AuthServiceInterface
{
    /** Inject the account query boundary and shared response factory. */
    public function __construct(private UserRepositoryInterface $users, private TransformerResponse $transformerResponse) {}

    /**
     * Check a precomputed fallback for unknown accounts to reduce timing differences.
     * This does not guarantee constant response times or authenticate a missing user.
     *
     * @param  array{email: string, password: string}  $credentials  Validated credentials only.
     *
     * @throws AuthenticationException
     */
    public function login(array $credentials, Request $request): Response
    {
        $user = $this->users->findByEmail($credentials['email']);
        $hash = $user?->password ?? config('auth.dummy_password_hash');
        if (! Hash::check($credentials['password'], $hash) || ! $user) {
            throw new AuthenticationException;
        }
        $request->session()->regenerate();
        $expires = now()->addMinutes(config('sanctum.expiration'));
        $token = $user->createToken('browser', ['*'], $expires);

        return $this->transformerResponse->response(data: ['user' => $user, 'expires_at' => $expires->toIso8601String()], message: InternalCodeEnum::LOGIN_SUCCESSFUL)
            ->withCookie($this->cookie($token->plainTextToken, $expires));
    }

    /** Return the current account and its single role. */
    public function me(User $user): Response
    {
        return $this->transformerResponse->response(data: $user);
    }

    /** Revoke this browser's token and clear its authentication cookie. */
    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->transformerResponse->response(message: InternalCodeEnum::LOGOUT_SUCCESSFUL)
            ->withCookie($this->cookie('', now()->subYear()));
    }

    /** Native CSRF middleware adds the readable XSRF cookie to this response. */
    public function csrfCookie(): Response
    {
        return $this->transformerResponse->response(message: InternalCodeEnum::CSRF_COOKIE_CREATED);
    }

    /** Build and expire cookies with identical scope and security attributes. */
    private function cookie(string $value, \DateTimeInterface $expires): Cookie
    {
        return Cookie::create(config('task_manager.cookie.name'), $value, $expires,
            config('task_manager.cookie.path'), null, config('task_manager.cookie.secure'), true, false,
            config('task_manager.cookie.same_site'));
    }
}
