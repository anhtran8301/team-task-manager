<?php

namespace Database\Factories;

use App\Modules\V1\User\Enums\UserRole;
use App\Modules\V1\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['name' => fake()->name(), 'email' => fake()->unique()->safeEmail(), 'password' => 'password', 'role' => UserRole::User->value];
    }

    /** Create an administrator without relation seeding. */
    public function admin(): static
    {
        return $this->state(fn () => ['role' => UserRole::Admin->value]);
    }
}
