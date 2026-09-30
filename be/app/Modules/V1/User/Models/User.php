<?php

namespace App\Modules\V1\User\Models;

use App\Modules\V1\Task\Models\Task;
use App\Modules\V1\User\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    /**
     * Cast passwords through Laravel's configured hasher.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    /** Resolve this module's factory. */
    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    /**
     * Tasks assigned to this account.
     *
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    /** Compare roles centrally; unrecognized values never grant access. */
    public function hasRole(UserRole $role): bool
    {
        return $this->role === $role->value;
    }

    /** Administrators manage all tasks and assignees. */
    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::Admin);
    }

    /** @return array{id: int, name: string, email: string, role: string} */
    public function toArray(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'email' => $this->email, 'role' => $this->role];
    }
}
