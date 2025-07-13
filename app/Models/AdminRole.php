<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminRole extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'permissions',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'permissions' => 'json',
    ];

    /**
     * Get the users with this role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'admin_role_id');
    }

    /**
     * Check if this role has a specific permission.
     *
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        if (isset($this->permissions['full_access']) && $this->permissions['full_access']) {
            return true;
        }

        return isset($this->permissions[$permission]) && $this->permissions[$permission];
    }

    /**
     * Get all permissions as an array.
     *
     * @return array
     */
    public function getPermissions(): array
    {
        return $this->permissions ?? [];
    }

    /**
     * Set permissions.
     *
     * @param array $permissions
     * @return self
     */
    public function setPermissions(array $permissions): self
    {
        $this->permissions = $permissions;
        return $this;
    }

    /**
     * Grant a specific permission.
     *
     * @param string $permission
     * @return self
     */
    public function grantPermission(string $permission): self
    {
        $permissions = $this->getPermissions();
        $permissions[$permission] = true;
        $this->permissions = $permissions;
        return $this;
    }

    /**
     * Revoke a specific permission.
     *
     * @param string $permission
     * @return self
     */
    public function revokePermission(string $permission): self
    {
        $permissions = $this->getPermissions();
        $permissions[$permission] = false;
        $this->permissions = $permissions;
        return $this;
    }
}
