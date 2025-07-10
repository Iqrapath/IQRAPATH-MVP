<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuardianProfile extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'relationship',
        'occupation',
        'emergency_contact',
        'secondary_phone',
        'preferred_contact_method',
    ];

    /**
     * Get the user that owns the guardian profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the students associated with this guardian.
     */
    public function students(): HasMany
    {
        return $this->hasMany(StudentProfile::class, 'guardian_id', 'user_id');
    }
}
