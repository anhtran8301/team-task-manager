<?php

return [
    'routes' => false,
    'stateful' => [], 'guard' => [], // The CSRF session must not authenticate a user by itself.
    'expiration' => (int) env('SANCTUM_EXPIRATION', 120),
    'token_prefix' => '', 'middleware' => [],
];
