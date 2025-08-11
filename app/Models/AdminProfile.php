<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminProfile extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'department',
        'admin_level',
        'permissions',
        'bio',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'permissions' => 'array',
    ];

    /**
     * Get the user that owns the admin profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the admin has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->permissions || !is_array($this->permissions)) {
            return false;
        }

        return in_array($permission, $this->permissions);
    }

    /**
     * Check if the admin is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->admin_level === 'super-admin';
    }

    /**
     * Get the department display name.
     */
    public function getDepartmentDisplayAttribute(): string
    {
        return ucfirst($this->department ?? '');
    }

    /**
     * Get the admin level display name.
     */
    public function getAdminLevelDisplayAttribute(): string
    {
        return ucfirst(str_replace('-', ' ', $this->admin_level ?? ''));
    }
}
