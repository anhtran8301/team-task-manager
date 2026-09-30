<?php

namespace Tests\Feature;

use App\Modules\V1\Task\Models\Task;
use App\Modules\V1\User\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskSearchTest extends TestCase
{
    // InnoDB FULLTEXT only sees committed rows; do not wrap these fixtures in transactions.
    use DatabaseMigrations;

    public function test_filters_search_and_pagination_compose_with_access_scope(): void
    {
        [$user, $other] = User::factory()->count(2)->create();
        Task::factory()->count(22)->create(['assigned_to' => $user->id, 'title' => 'Release checklist', 'status' => 'todo']);
        Task::factory()->create(['assigned_to' => $user->id, 'title' => 'Release finished', 'status' => 'done']);
        Task::factory()->create(['assigned_to' => $other->id, 'title' => 'Release checklist', 'status' => 'todo']);
        Sanctum::actingAs($user);
        $this->getJson('/api/tasks?status[]=todo&status[]=in_progress&assigned_to[]='.$user->id.'&assigned_to[]='.$other->id.'&search=Release&per_page=20&page=2')->assertOk()
            ->assertJsonCount(2, 'data.data')->assertJsonPath('data.total', 22)->assertJsonPath('data.current_page', 2);
        $this->getJson('/api/tasks?search=finished')->assertJsonPath('data.total', 1);
        Sanctum::actingAs(User::factory()->admin()->create());
        $this->getJson('/api/tasks?assigned_to[]='.$other->id)->assertJsonPath('data.total', 1);
        $this->getJson('/api/tasks')->assertJsonPath('data.total', 24);
    }

    /** Natural-language search matches whole tokens, never SQL wildcard syntax. */
    public function test_natural_language_search_handles_tokens_and_special_characters(): void
    {
        $user = User::factory()->create();
        $first = Task::factory()->create(['assigned_to' => $user->id, 'title' => 'Orchid deployment']);
        $second = Task::factory()->create(['assigned_to' => $user->id, 'title' => 'Cobalt review']);
        Task::factory()->create(['assigned_to' => $user->id, 'title' => 'Unrelated report']);
        Sanctum::actingAs($user);
        $this->getJson('/api/tasks?search=Orchid')->assertOk()->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $first->id);
        $this->getJson('/api/tasks?search='.urlencode('Orchid Cobalt'))->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.data.0.id', $second->id);
        foreach (['Orch', '0', 'ab', 'the', '%_', "' OR 1=1 --"] as $keyword) {
            $this->getJson('/api/tasks?search='.urlencode($keyword))->assertOk()->assertJsonPath('data.total', 0);
        }
        $this->getJson('/api/tasks?search='.urlencode('+Orchid -Cobalt'))->assertOk()->assertJsonPath('data.total', 2);
        $this->getJson('/api/tasks?search=')->assertJsonPath('data.total', 3);
    }

    /** Committed changes must be reflected without manual index maintenance. */
    public function test_committed_create_update_delete_and_foreign_scope(): void
    {
        [$user, $other] = User::factory()->count(2)->create();
        Task::factory()->create(['assigned_to' => $other->id, 'title' => 'Orchid']);
        Sanctum::actingAs($user);
        $payload = ['title' => 'Orchid', 'status' => 'todo', 'assigned_to' => $user->id];
        $id = $this->postJson('/api/tasks', $payload)->assertCreated()->json('data.id');
        $this->getJson('/api/tasks?search=Orchid')->assertJsonPath('data.total', 1);
        $this->getJson('/api/tasks?search=Orchid&assigned_to[]='.$other->id)->assertJsonPath('data.total', 0);
        $this->putJson('/api/tasks/'.$id, array_replace($payload, ['title' => 'Cobalt']))->assertOk();
        $this->getJson('/api/tasks?search=Orchid')->assertJsonPath('data.total', 0);
        $this->getJson('/api/tasks?search=Cobalt')->assertJsonPath('data.total', 1);
        $this->deleteJson('/api/tasks/'.$id)->assertOk();
        $this->getJson('/api/tasks?search=Cobalt')->assertJsonPath('data.total', 0);
    }

    /** Adding/removing the index must preserve existing rows and relational constraints. */
    public function test_fulltext_migration_round_trip_preserves_data(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id, 'title' => 'Orchid']);
        $before = DB::table('tasks')->where('id', $task->id)->first();
        $migration = require database_path('migrations/2026_09_30_000001_add_tasks_title_fulltext.php');
        $migration->down();
        $this->assertFalse(Schema::hasIndex('tasks', 'tasks_title_fulltext'));
        $migration->up();
        $this->assertTrue(Schema::hasIndex('tasks', 'tasks_title_fulltext', 'fulltext'));
        $this->assertEquals($before, DB::table('tasks')->where('id', $task->id)->first());
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        Sanctum::actingAs($user);
        $this->getJson('/api/tasks?search=Orchid')->assertJsonPath('data.total', 1);
    }
}
