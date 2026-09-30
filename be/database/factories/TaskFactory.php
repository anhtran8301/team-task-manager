<?php

namespace Database\Factories;

use App\Modules\V1\Task\Models\Task;
use App\Modules\V1\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    /** Generate test attributes. @return array<string, mixed> */
    public function definition(): array
    {
        return ['title' => fake()->sentence(4), 'description' => fake()->paragraph(), 'status' => 'todo', 'assigned_to' => User::factory(), 'due_date' => null];
    }
}
