<?php

namespace App\Providers;

use App\Enums\InternalCodeEnum;
use App\Helpers\TransformerResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /** Configure infrastructure hooks without embedding domain rules. */
    public function boot(): void
    {
        Event::listen(DiagnosingHealth::class, fn () => DB::select('SELECT 1'));
        Sanctum::getAccessTokenFromRequestUsing(fn (Request $request) => $request->cookie(config('task_manager.cookie.name')));
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(config('task_manager.login_attempts'))
                ->by(Str::lower((string) $request->input('email')).'|'.$request->ip())
                ->response(fn (Request $request, array $headers) => app(TransformerResponse::class)
                    ->response(false, [], Response::HTTP_TOO_MANY_REQUESTS, InternalCodeEnum::TOO_MANY_REQUESTS)->withHeaders($headers));
        });
    }
}
