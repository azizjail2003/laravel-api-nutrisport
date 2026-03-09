<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Agent extends Authenticatable implements JWTSubject
{
    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password'];

    protected $casts = ['password' => 'hashed'];

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return ['guard' => 'agent'];
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(AgentPermission::class);
    }

    public function hasPermission(string $permission): bool
    {
        // Agent ID=1 bypasses all checks
        if ($this->id === 1) {
            return true;
        }

        return $this->permissions()->where('permission', $permission)->exists();
    }
}
