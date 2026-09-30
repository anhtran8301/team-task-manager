<?php

namespace Database\Seeders;

use App\Modules\V1\Task\Models\Task;
use App\Modules\V1\User\Enums\UserRole;
use App\Modules\V1\User\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Seed demo data without overwriting existing accounts or tasks. */
    public function run(): void
    {
        foreach ([['Admin', 'admin@example.com', UserRole::Admin], ['Alex Morgan', 'alex@example.com', UserRole::User], ['Sam Taylor', 'sam@example.com', UserRole::User]] as [$name, $email, $role]) {
            $user = User::firstOrNew(['email' => $email]);
            // Never reset existing credentials or overwrite existing tasks on a repeated seed.
            if ($user->exists) {
                continue;
            }
            $user->forceFill(['name' => $name, 'password' => 'Password123!', 'role' => $role->value])->save();
            for ($i = 1; $i <= 24; $i++) {
                Task::create([
                    'title' => ['Review onboarding checklist', 'Prepare sprint notes', 'Update release documentation', 'Test account access'][$i % 4].' #'.$i,
                    'description' => 'Demo task for '.$name.'. Replace this with your own work.',
                    'status' => ['todo', 'in_progress', 'done'][$i % 3], 'assigned_to' => $user->id,
                    'due_date' => $i % 4 === 0 ? null : now()->addDays($i - 12)->toDateString(),
                ]);
            }
        }
    }
}
