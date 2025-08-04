<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherProfile extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'bio',
        'experience_years',
        'verified',
        'languages',
        'teaching_type',
        'teaching_mode',
        'intro_video_url',
        'education',
        'qualification',
        'rating',
        'reviews_count',
        'join_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'verified' => 'boolean',
        'languages' => 'array',
        'rating' => 'decimal:1',
        'reviews_count' => 'integer',
        'join_date' => 'date',
    ];

    /**
     * Get the user that owns the teacher profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subjects for the teacher profile.
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    /**
     * Get the documents for the teacher profile.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Get the ID verification documents.
     */
    public function idVerifications()
    {
        return $this->documents()->where('type', Document::TYPE_ID_VERIFICATION);
    }

    /**
     * Get the certificate documents.
     */
    public function certificates()
    {
        return $this->documents()->where('type', Document::TYPE_CERTIFICATE);
    }

    /**
     * Get the resume document.
     */
    public function resume()
    {
        return $this->documents()->where('type', Document::TYPE_RESUME)->latest()->first();
    }

    /**
     * Get the verification requests for the teacher profile.
     */
    public function verificationRequests()
    {
        return $this->hasMany(VerificationRequest::class);
    }

    /**
     * Get the formatted rating with total reviews.
     */
    public function getFormattedRatingAttribute()
    {
        if (!$this->rating) {
            return 'No ratings yet';
        }
        
        return $this->rating . ' (' . $this->reviews_count . ' Reviews)';
    }
}
