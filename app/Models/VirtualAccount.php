<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VirtualAccount extends Model
{
    protected $fillable = [
        'user_id',
        'account_number',
        'account_name',
        'bank_name',
        'bank_code',
        'provider',
        'provider_account_id',
        'provider_response',
        'is_active',
        'activated_at',
    ];

    protected $casts = [
        'provider_response' => 'array',
        'is_active' => 'boolean',
        'activated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the virtual account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
