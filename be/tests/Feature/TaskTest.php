<?php

namespace Tests\Feature;

use App\Modules\V1\Task\Models\Task;
use App\Modules\V1\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    private function payload(User $user, array $changes = []): array
    {
        return array_replace(['title' => 'Review release', 'description' => null, 'status' => 'todo', 'assigned_to' => $user->id, 'due_date' => '2026-01-02'], $changes);
    }

    public function test_admin_can_manage_and_reassign_any_task(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        Sanctum::actingAs($admin);
        $result = $this->postJson('/api/tasks', $this->payload($user))->assertCreated()
            ->assertJsonPath('data.assignee.id', $user->id)->assertJsonPath('data.due_date', '2026-01-02');
        $id = $result->json('data.id');
        $this->putJson('/api/tasks/'.$id, $this->payload($admin, ['status' => 'done', 'due_date' => null]))
            ->assertOk()->assertJsonPath('data.status', 'done')->assertJsonPath('data.assigned_to', $admin->id)->assertJsonPath('data.due_date', null);
        $this->deleteJson('/api/tasks/'.$id)->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('tasks', ['id' => $id]);
    }

    public function test_user_can_manage_only_their_own_tasks(): void
    {
        [$user, $other] = User::factory()->count(2)->create();
        $foreign = Task::factory()->create(['assigned_to' => $other->id]);
        Sanctum::actingAs($user);
        $id = $this->postJson('/api/tasks', $this->payload($user))->assertCreated()->json('data.id');
        $this->putJson('/api/tasks/'.$id, $this->payload($user, ['status' => 'in_progress']))->assertOk();
        $this->getJson('/api/tasks')->assertOk()->assertJsonPath('data.total', 1)->assertJsonPath('data.data.0.id', $id);
        $this->getJson('/api/tasks?assigned_to[]='.$other->id)->assertOk()->assertJsonPath('data.total', 0);
        $this->postJson('/api/tasks', $this->payload($other))->assertForbidden();
        $this->putJson('/api/tasks/'.$id, $this->payload($other))->assertForbidden();
        $this->putJson('/api/tasks/'.$foreign->id, $this->payload($user))->assertForbidden();
        $this->deleteJson('/api/tasks/'.$foreign->id)->assertForbidden();
        $this->deleteJson('/api/tasks/'.$id)->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('tasks', ['id' => $foreign->id, 'assigned_to' => $other->id]);
    }

    public function test_validation_and_not_found_do_not_leak_internal_details(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->postJson('/api/tasks', ['title' => ' ', 'status' => 'unknown', 'assigned_to' => 99999, 'due_date' => '2026-02-30'])
            ->assertUnprocessable()->assertJsonValidationErrors(['title', 'status', 'assigned_to', 'due_date'])->assertJsonMissingPath('exception');
        $this->getJson('/api/tasks?per_page=101&page=0&status=bad')->assertUnprocessable()->assertJsonValidationErrors(['per_page', 'page', 'status']);
        $this->putJson('/api/tasks/99999', $this->payload($user))->assertNotFound()->assertJsonPath('code', 404);
        $this->deleteJson('/api/tasks/99999')->assertNotFound();
        $this->postJson('/api/tasks', $this->payload($user, ['title' => str_repeat('a', 256)]))->assertUnprocessable();
    }

    public function test_assignee_choices_follow_role_and_hide_passwords(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($user);
        $this->getJson('/api/users')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $user->id)->assertJsonMissingPath('data.0.password');
        Sanctum::actingAs($admin);
        $this->getJson('/api/users')->assertJsonCount(2, 'data');
    }

    public function test_seed_is_repeatable_without_overwriting_tasks_or_passwords(): void
    {
        $this->seed();
        $user = User::where('email', 'alex@example.com')->firstOrFail();
        $user->forceFill(['name' => 'Updated name', 'password' => 'updated-password', 'role' => 'admin'])->save();
        $task = $user->tasks()->first();
        $task->update(['title' => 'Updated task']);
        $this->seed();
        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('tasks', 72);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Updated task']);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated name', 'role' => 'admin']);
    }
}
