<?php

namespace Tests\Feature;

use App\Modules\V1\Task\Models\Task;
use App\Modules\V1\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskSortTest extends TestCase
{
    use RefreshDatabase;

    /** Exercise ordered criteria through the public HTTP contract. */
    private function ids(array $query = []): array
    {
        return $this->getJson('/api/tasks?'.http_build_query($query))->assertOk()->json('data.data.*.id');
    }

    public function test_each_sort_direction_and_null_dates_last(): void
    {
        $admin = User::factory()->admin()->create();
        $amy = User::factory()->create(['name' => 'Amy']);
        $zoe = User::factory()->create(['name' => 'Zoe']);
        $a = Task::factory()->create(['title' => 'Alpha', 'assigned_to' => $zoe->id, 'due_date' => '2026-01-01']);
        $b = Task::factory()->create(['title' => 'Beta', 'assigned_to' => $amy->id, 'due_date' => '2026-02-01']);
        $c = Task::factory()->create(['title' => 'Gamma', 'assigned_to' => $amy->id, 'due_date' => null]);
        Sanctum::actingAs($admin);
        foreach ([['title', 'asc', [$a->id, $b->id, $c->id]], ['title', 'desc', [$c->id, $b->id, $a->id]],
            ['assignee', 'asc', [$c->id, $b->id, $a->id]], ['assignee', 'desc', [$a->id, $c->id, $b->id]],
            ['due_date', 'asc', [$a->id, $b->id, $c->id]], ['due_date', 'desc', [$b->id, $a->id, $c->id]]] as [$field, $direction, $ids]) {
            $this->assertSame($ids, $this->ids(['sort' => [compact('field', 'direction')]]));
        }
        $this->assertSame([$c->id, $b->id, $a->id], $this->ids());
        $this->assertSame([$b->id, $c->id, $a->id], $this->ids(['sort' => [['field' => 'assignee', 'direction' => 'asc'], ['field' => 'due_date', 'direction' => 'desc']]]));
        $this->assertSame([$a->id, $b->id, $c->id], $this->ids(['sort' => [['field' => 'due_date', 'direction' => 'asc'], ['field' => 'assignee', 'direction' => 'asc']]]));
    }

    public function test_ties_and_join_preserve_pagination_and_assignee_serialization(): void
    {
        $admin = User::factory()->admin()->create();
        [$first, $second] = User::factory()->count(2)->create(['name' => 'Same name']);
        $a = Task::factory()->create(['title' => 'Same', 'assigned_to' => $first->id]);
        $b = Task::factory()->create(['title' => 'Same', 'assigned_to' => $second->id]);
        Sanctum::actingAs($admin);
        $query = ['sort' => [['field' => 'assignee', 'direction' => 'asc'], ['field' => 'title', 'direction' => 'asc']], 'per_page' => 1];
        $this->assertSame([$b->id], $this->ids($query));
        $this->assertSame([$a->id], $this->ids($query + ['page' => 2]));
        $this->getJson('/api/tasks?'.http_build_query($query))->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.data.0.assignee.id', $second->id)->assertJsonPath('data.data.0.assignee.role', 'user');
    }

    public function test_multiple_filters_are_ored_within_groups_and_anded_across_groups(): void
    {
        $admin = User::factory()->admin()->create();
        [$one, $two, $three] = User::factory()->count(3)->create();
        $a = Task::factory()->create(['assigned_to' => $one->id, 'status' => 'todo']);
        $b = Task::factory()->create(['assigned_to' => $two->id, 'status' => 'done']);
        Task::factory()->create(['assigned_to' => $two->id, 'status' => 'in_progress']);
        Task::factory()->create(['assigned_to' => $three->id, 'status' => 'done']);
        $query = ['status' => ['todo', 'done'], 'assigned_to' => [$one->id, $two->id]];
        Sanctum::actingAs($admin);
        $this->assertSame([$b->id, $a->id], $this->ids($query));
        Sanctum::actingAs($one);
        $this->assertSame([$a->id], $this->ids($query));
        $this->assertSame([], $this->ids(['assigned_to' => [$two->id, $three->id]]));
        $this->assertSame([], $this->ids(['assigned_to' => [999999]]));
        $this->assertSame([$a->id], $this->ids(['status' => [], 'assigned_to' => [], 'sort' => []]));
    }

    public function test_malformed_lists_and_sort_sql_are_rejected(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $queries = [
            ['status' => 'todo'], ['status' => ['todo', 'todo']], ['status' => ['invalid']], ['status' => [['todo']]], ['status' => ['named' => 'todo']],
            ['assigned_to' => 1], ['assigned_to' => [1, 1]], ['assigned_to' => [0]], ['assigned_to' => ['invalid']], ['assigned_to' => range(1, 101)],
            ['sort' => 'title'], ['sort' => [['field' => 'title']]], ['sort' => [['direction' => 'asc']]],
            ['sort' => [['field' => 'title', 'direction' => 'asc', 'extra' => 'x']]],
            ['sort' => [['field' => 'title; DROP TABLE users', 'direction' => 'asc']]],
            ['sort' => [['field' => 'title', 'direction' => 'desc; SELECT 1']]],
            ['sort' => array_fill(0, 2, ['field' => 'title', 'direction' => 'asc'])],
            ['sort' => array_fill(0, 4, ['field' => 'title', 'direction' => 'asc'])],
        ];
        foreach ($queries as $query) {
            $this->getJson('/api/tasks?'.http_build_query($query))->assertUnprocessable()->assertJsonPath('success', false)->assertJsonStructure(['errors']);
        }
    }
}
