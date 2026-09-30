<?php

namespace Tests\Feature;

use App\Modules\V1\Task\Models\Task;
use App\Modules\V1\User\Enums\UserRole;
use App\Modules\V1\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    /** Unknown roles must not become standard users through a permissive fallback. */
    public function test_unknown_role_fails_closed_at_http_boundary(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);
        $user->role = 'unsupported'; // Simulate invalid identity without defeating the database enum.
        Sanctum::actingAs($user);
        $this->getJson('/api/tasks')->assertForbidden();
        $this->getJson('/api/users')->assertForbidden();
        $this->postJson('/api/tasks', [])->assertForbidden();
        $this->putJson('/api/tasks/'.$task->id, [])->assertForbidden();
        $this->deleteJson('/api/tasks/'.$task->id)->assertForbidden();
    }

    /** Admin-created tasks belong to their assignee, who may update/delete them. */
    public function test_assignee_owns_admin_created_task_and_cannot_escalate_role(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        Sanctum::actingAs($admin);
        $payload = ['title' => 'Assigned task', 'status' => 'todo', 'assigned_to' => $user->id];
        $id = $this->postJson('/api/tasks', $payload)->assertCreated()->json('data.id');
        Sanctum::actingAs($user);
        $this->putJson('/api/tasks/'.$id, $payload + ['role' => 'admin', 'permissions' => ['task.access_all']])->assertOk();
        $this->assertTrue($user->fresh()->hasRole(UserRole::User));
        $this->assertFalse($user->fresh()->isAdmin());
        $this->deleteJson('/api/tasks/'.$id)->assertOk();
    }

    /** Validate existence/type before evaluating assignment, while ownership is checked first. */
    public function test_assignment_errors_keep_validation_and_authorization_distinct(): void
    {
        [$user, $other] = User::factory()->count(2)->create();
        Sanctum::actingAs($user);
        $payload = ['title' => 'Task', 'status' => 'todo'];
        $this->postJson('/api/tasks', $payload + ['assigned_to' => 'invalid'])->assertUnprocessable();
        $this->postJson('/api/tasks', $payload + ['assigned_to' => 999999])->assertUnprocessable();
        $this->postJson('/api/tasks', $payload + ['assigned_to' => $other->id])->assertForbidden();
        $foreign = Task::factory()->create(['assigned_to' => $other->id]);
        $this->putJson('/api/tasks/'.$foreign->id, [])->assertForbidden();
    }

    /** All account serializers expose the same single-role contract without relation queries. */
    public function test_role_contract_and_eager_loading(): void
    {
        $admin = User::factory()->admin()->create();
        Task::factory()->count(20)->create();
        Sanctum::actingAs($admin);
        $this->getJson('/api/me')->assertJsonPath('data.role', 'admin')->assertJsonMissingPath('data.roles')->assertJsonMissingPath('data.permissions');
        $this->getJson('/api/users')->assertJsonStructure(['data' => [['role']]]);
        DB::enableQueryLog();
        $this->getJson('/api/tasks')->assertOk()->assertJsonCount(20, 'data.data')
            ->assertJsonPath('data.data.0.assignee.role', 'user')->assertJsonMissingPath('data.data.0.assignee.permissions');
        $this->assertLessThan(8, count(DB::getQueryLog()));
        DB::disableQueryLog();
    }
}
