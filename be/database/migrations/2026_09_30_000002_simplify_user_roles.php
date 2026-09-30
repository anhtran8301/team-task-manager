<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Reject unmappable accounts before any DDL, then preserve users/tasks while collapsing roles. */
    public function up(): void
    {
        $invalid = DB::table('users')->where(function ($query) {
            $query->whereNotExists(function ($roles) {
                $roles->selectRaw('1')->from('user_role')->whereColumn('user_role.user_id', 'users.id');
            })->orWhereExists(function ($roles) {
                $roles->selectRaw('1')->from('user_role')->join('roles', 'roles.id', '=', 'user_role.role_id')
                    ->whereColumn('user_role.user_id', 'users.id')->whereNotIn('roles.slug', ['admin', 'user']);
            });
        })->orderBy('id')->limit(20)->pluck('id');
        if ($invalid->isNotEmpty()) {
            throw new RuntimeException('Cannot map account roles. Resolve user IDs (first 20): '.$invalid->implode(', '));
        }

        // Frozen values keep migration history independent of application enums.
        Schema::table('users', fn (Blueprint $table) => $table->enum('role', ['admin', 'user'])->default('user'));
        DB::table('users')->whereIn('id', DB::table('user_role')->join('roles', 'roles.id', '=', 'user_role.role_id')
            ->where('roles.slug', 'admin')->select('user_role.user_id'))->update(['role' => 'admin']);
        Schema::drop('role_permission');
        Schema::drop('user_role');
        Schema::drop('permissions');
        Schema::drop('roles');
    }

    /** Restore the historical schema and canonical permissions, not removed custom grants/multiple roles. */
    public function down(): void
    {
        // The original migration is immutable and already defines this schema/backfill.
        $legacy = require __DIR__.'/2026_09_30_000000_create_role_permissions.php';
        $legacy->up();
        $standard = ['task.view', 'task.create', 'task.update', 'task.delete', 'user.view'];
        $extended = ['task.access_all', 'task.assign_any', 'user.view_all'];
        foreach (array_merge($standard, $extended) as $code) {
            $id = DB::table('permissions')->insertGetId(['name' => $code, 'code' => $code, 'created_at' => now(), 'updated_at' => now()]);
            $roles = DB::table('roles')->when(! in_array($code, $standard, true), fn ($query) => $query->where('slug', 'admin'))->pluck('id');
            foreach ($roles as $roleId) {
                DB::table('role_permission')->insert(['role_id' => $roleId, 'permission_id' => $id, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }
};
