<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // Cast account status and date fields to their application types.
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'phone',
        'department',
        'profile_image',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    // Resolve the role assigned to this user.
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    // Return the public storage URL when the profile image exists.
    public function getProfileImageUrlAttribute(): ?string
    {
        if (! $this->profile_image) {
            return null;
        }

        $imagePath = ltrim($this->profile_image, '/');

        if (! Storage::disk('public')->exists($imagePath)) {
            return null;
        }

        return request()->getBaseUrl().'/storage/'.$imagePath;
    }

    // Check whether the user's assigned role matches the given name.
    public function hasRole(string $role): bool
    {
        return strcasecmp((string) $this->role?->name, $role) === 0;
    }

    // Check whether this active user can perform the given permission.
    public function hasPermission(string $permission): bool
    {
        if (! (bool) $this->is_active) {
            return false;
        }

        $role = $this->role;

        if (! $role || ! (bool) $role->is_active) {
            return false;
        }

        if (strcasecmp((string) $role->name, 'Admin') === 0) {
            return true;
        }

        return $role->hasPermission($permission);
    }
}
