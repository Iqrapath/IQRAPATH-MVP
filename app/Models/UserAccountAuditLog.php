<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAccountAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'performed_by',
        'action',
        'field_name',
        'old_value',
        'new_value',
        'reason',
        'metadata',
        'ip_address',
        'user_agent',
        'performed_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'performed_at' => 'datetime',
        ];
    }

    /**
     * Get the user that this audit log belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who performed this action.
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Get the action display name.
     */
    public function getActionDisplayAttribute(): string
    {
        return match($this->action) {
            'created' => 'Account Created',
            'updated' => 'Account Updated',
            'suspended' => 'Account Suspended',
            'unsuspended' => 'Account Unsuspended',
            'deleted' => 'Account Deleted',
            'restored' => 'Account Restored',
            'force_deleted' => 'Account Permanently Deleted',
            'role_changed' => 'Role Changed',
            'status_changed' => 'Status Changed',
            'profile_created' => 'Profile Created',
            'profile_deleted' => 'Profile Deleted',
            default => ucfirst(str_replace('_', ' ', $this->action))
        };
    }

    /**
     * Get the action color for UI.
     */
    public function getActionColorAttribute(): string
    {
        return match($this->action) {
            'created', 'restored' => 'green',
            'updated', 'role_changed', 'status_changed' => 'blue',
            'suspended', 'deleted' => 'red',
            'unsuspended' => 'yellow',
            'force_deleted' => 'red',
            'profile_created' => 'green',
            'profile_deleted' => 'red',
            default => 'gray'
        };
    }
}