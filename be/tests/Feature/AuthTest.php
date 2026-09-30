<?php

namespace Tests\Feature;

use App\Modules\V1\User\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_me_and_logout_revoke_the_cookie_token(): void
    {
        $user = User::factory()->create();
        $result = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()->assertJsonPath('success', true)->assertJsonPath('data.user.role', 'user')
            ->assertJsonMissingPath('data.user.password')->assertJsonMissingPath('data.access_token')
            ->assertJsonStructure(['message', 'data' => ['expires_at', 'user' => ['role']]]);
        $this->assertInstanceOf(Response::class, $result->baseResponse);
        $cookie = $result->getCookie(config('task_manager.cookie.name'));
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
        $this->assertSame('/', $cookie->getPath());
        $this->assertNull($cookie->getDomain());
        $this->withCredentials()->withCookie($cookie->getName(), $cookie->getValue())->getJson('/api/me')->assertOk()->assertJsonPath('data.id', $user->id);
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/logout')->assertOk()->assertCookieExpired($cookie->getName());
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/me')->assertUnauthorized();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_invalid_credentials_have_the_same_envelope_and_validation_has_field_errors(): void
    {
        $user = User::factory()->create();
        $known = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'incorrect'])->assertUnauthorized()->assertJsonPath('success', false);
        $unknown = $this->postJson('/api/login', ['email' => 'unknown@example.com', 'password' => 'password'])->assertUnauthorized();
        $this->assertSame($known->json(), $unknown->json());
        $this->postJson('/api/login', ['email' => 'not-an-email'])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
        $this->getJson('/api/tasks')->assertUnauthorized()->assertJsonPath('code', 401);
        $this->getJson('/api/missing')->assertNotFound()->assertJsonPath('success', false);
        $this->postJson('/api/me')->assertStatus(405)->assertHeader('Allow');
    }

    /** A matching fallback must never allow a nonexistent account to authenticate. */
    public function test_matching_dummy_password_never_creates_a_token(): void
    {
        config(['auth.dummy_password_hash' => Hash::make('dummy-password')]);
        $this->assertFalse(Hash::needsRehash(config('auth.dummy_password_hash')));
        $this->postJson('/api/login', ['email' => 'missing@example.com', 'password' => 'dummy-password'])
            ->assertUnauthorized()->assertCookieMissing(config('task_manager.cookie.name'))
            ->assertJsonMissingPath('data.access_token')->assertJsonMissingPath('data.user');
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /** Unknown emails still take the configured password-check path. */
    public function test_unknown_account_checks_the_configured_hash(): void
    {
        $hash = Hash::make('dummy-password');
        config(['auth.dummy_password_hash' => $hash]);
        Hash::shouldReceive('check')->once()->with('incorrect', $hash)->andReturn(false);
        $this->postJson('/api/login', ['email' => 'missing@example.com', 'password' => 'incorrect'])
            ->assertUnauthorized()->assertCookieMissing(config('task_manager.cookie.name'));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_expired_cookie_and_legacy_bearer_tokens_are_rejected(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test', ['*'], now()->subMinute())->plainTextToken;
        $this->withCredentials()->withCookie(config('task_manager.cookie.name'), $token)->getJson('/api/me')->assertUnauthorized();
        $token = $user->createToken('test', ['*'], now()->addHour())->plainTextToken;
        $this->withCookie(config('task_manager.cookie.name'), '')->withToken($token)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < config('task_manager.login_attempts'); $i++) {
            $this->postJson('/api/login', ['email' => 'limited@example.com', 'password' => 'wrong'])->assertUnauthorized();
        }
        $this->postJson('/api/login', ['email' => 'limited@example.com', 'password' => 'wrong'])
            ->assertStatus(429)->assertJsonPath('success', false)->assertHeader('Retry-After');
    }

    public function test_csrf_is_required_for_login_and_authenticated_mutations(): void
    {
        // Laravel skips CSRF in tests; override only that bypass, keeping real middleware behavior.
        $this->app->bind(ValidateCsrfToken::class, fn ($app) => new class($app, $app['encrypter']) extends ValidateCsrfToken
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });
        $this->getJson('/api/csrf-cookie')->assertOk()->assertCookie('XSRF-TOKEN');
        $this->postJson('/api/login', ['email' => 'a@example.com', 'password' => 'password'])->assertStatus(419)->assertJsonPath('success', false);
        $user = User::factory()->create();
        $token = $user->createToken('test', ['*'], now()->addHour())->plainTextToken;
        $this->withCredentials()->withCookie(config('task_manager.cookie.name'), $token)->postJson('/api/tasks', [])->assertStatus(419);
        $this->withSession(['_token' => 'known-csrf'])->withHeader('X-CSRF-TOKEN', 'known-csrf')
            ->postJson('/api/tasks', [])->assertUnprocessable();
    }

    public function test_secure_cookie_configuration_is_honored(): void
    {
        config(['task_manager.cookie.secure' => true]);
        $user = User::factory()->create();
        $result = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])->assertOk();
        $this->assertTrue($result->getCookie(config('task_manager.cookie.name'))->isSecure());
    }
}
