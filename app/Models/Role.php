<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    // Cast the role's active status to a boolean.
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // Retrieve users assigned to this role.
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // Retrieve permissions assigned to this role.
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'permission_role',
            'role_id',
            'permission_id'
        );
    }

    // Check whether this role includes the named permission.
    public function hasPermission(string $permission): bool
    {
        return $this->permissions()
            ->where('name', $permission)
            ->exists();
    }
}
