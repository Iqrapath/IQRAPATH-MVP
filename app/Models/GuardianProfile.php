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
        'status',
        'registration_date',
        'children_count',
        'relationship',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'registration_date' => 'datetime',
        'children_count' => 'integer',
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

    /**
     * Update the children count based on actual student relationships.
     *
     * @return bool
     */
    public function updateChildrenCount(): bool
    {
        $this->children_count = $this->students()->count();
        return $this->save();
    }
}
