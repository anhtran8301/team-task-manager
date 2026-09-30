<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Backfill existing account roles before removing the duplicated role column. */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
        });
        Schema::create('user_role', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'role_id']);
            $table->timestamps();
        });
        Schema::create('role_permission', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
            $table->timestamps();
        });
        // Historical values are intentional: migrations must not depend on future enum changes.
        foreach (['admin', 'user'] as $slug) {
            $id = DB::table('roles')->insertGetId(['slug' => $slug, 'name' => ucfirst($slug), 'created_at' => now(), 'updated_at' => now()]);
            DB::table('users')->where('role', $slug)->orderBy('id')->chunkById(500, function ($users) use ($id) {
                DB::table('user_role')->insert($users->map(fn ($user) => ['user_id' => $user->id, 'role_id' => $id, 'created_at' => now(), 'updated_at' => now()])->all());
            });
        }
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('role'));
    }

    /** Restore the legacy single role (admin takes precedence), retaining all users/tasks. */
    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->string('role')->default('user'));
        DB::table('users')->whereIn('id', DB::table('user_role')->join('roles', 'roles.id', '=', 'user_role.role_id')->where('roles.slug', 'admin')->select('user_role.user_id'))->update(['role' => 'admin']);
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('user_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
