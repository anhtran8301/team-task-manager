<?php

use App\Modules\V1\User\Models\User;

return [
    // Precomputed bcrypt cost 12; regenerate when changing the hashing driver or cost.
    'dummy_password_hash' => env('AUTH_DUMMY_PASSWORD_HASH', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.'),
    'defaults' => ['guard' => 'web', 'passwords' => 'users'],
    'guards' => ['web' => ['driver' => 'session', 'provider' => 'users']],
    'providers' => ['users' => ['driver' => 'eloquent', 'model' => User::class]],
];
