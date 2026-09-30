<?php

namespace Tests\Feature;

use App\Modules\V1\Task\Models\Task;
use App\Modules\V1\User\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RoleMigrationTest extends TestCase
{
    use DatabaseMigrations;

    /** Collapse multiple known roles with admin precedence and preserve persisted account/task/token data. */
    public function test_role_migration_round_trip_preserves_data(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);
        $token = $user->createToken('test')->accessToken;
        $password = $user->password;
        $migration = require database_path('migrations/2026_09_30_000002_simplify_user_roles.php');
        $migration->down();
        $this->assertFalse(Schema::hasColumn('users', 'role'));
        $this->assertDatabaseCount('permissions', 8);
        $this->assertDatabaseCount('role_permission', 13);
        DB::table('user_role')->insert(['user_id' => $admin->id, 'role_id' => DB::table('roles')->where('slug', 'user')->value('id')]);
        $migration->up();
        $this->assertFalse(Schema::hasTable('roles'));
        $this->assertFalse(Schema::hasTable('permissions'));
        $this->assertSame('admin', $admin->fresh()->role);
        $this->assertSame('user', $user->fresh()->role);
        $this->assertSame($password, $user->fresh()->password);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'assigned_to' => $user->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $token->id, 'token' => $token->token]);
    }

    /** Preflight must stop before DDL for both missing and unrecognized roles. */
    public function test_unmappable_roles_stop_before_schema_changes(): void
    {
        $user = User::factory()->create();
        $migration = require database_path('migrations/2026_09_30_000002_simplify_user_roles.php');
        $migration->down();
        $userRoleId = DB::table('roles')->where('slug', 'user')->value('id');
        DB::table('user_role')->where('user_id', $user->id)->delete();
        try {
            foreach ([null, 'reviewer'] as $slug) {
                if ($slug !== null) {
                    $id = DB::table('roles')->insertGetId(['name' => 'Reviewer', 'slug' => $slug]);
                    DB::table('user_role')->insert(['user_id' => $user->id, 'role_id' => $id]);
                }
                try {
                    $migration->up();
                    $this->fail('Unmappable roles must reject the migration.');
                } catch (\RuntimeException $exception) {
                    $this->assertStringContainsString((string) $user->id, $exception->getMessage());
                    $this->assertFalse(Schema::hasColumn('users', 'role'));
                    $this->assertTrue(Schema::hasTable('role_permission'));
                }
            }
        } finally {
            DB::table('user_role')->where('user_id', $user->id)->delete();
            DB::table('user_role')->insert(['user_id' => $user->id, 'role_id' => $userRoleId]);
            $migration->up();
        }
    }
}
