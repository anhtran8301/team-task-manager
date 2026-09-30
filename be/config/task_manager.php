<?php

return [
    'filters' => ['maximum_assignees' => 100],
    'pagination' => ['default' => 20, 'maximum' => 100],
    'limits' => ['title' => 255, 'description' => 10000, 'email' => 255, 'password' => 255],
    'login_attempts' => (int) env('LOGIN_ATTEMPTS_PER_MINUTE', 5),
    'cookie' => ['name' => 'team_tasks_access_token', 'path' => '/',
        'secure' => (bool) env('AUTH_COOKIE_SECURE', str_starts_with(env('APP_URL', ''), 'https://')), 'same_site' => 'lax'],
];
