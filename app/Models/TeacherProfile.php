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
        'hourly_rate_usd',
        'hourly_rate_ngn',
        'preferred_currency',
        'permanent_meeting_link',
        'permanent_meeting_platform',
        'permanent_meeting_id',
        'permanent_meeting_password',
        'permanent_meeting_created_at',
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
        'join_date' => 'datetime',
        'hourly_rate_usd' => 'decimal:2',
        'hourly_rate_ngn' => 'decimal:2',
        'permanent_meeting_created_at' => 'datetime',
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

    /**
     * Check if the teacher is verified.
     */
    public function isVerified(): bool
    {
        return $this->verified;
    }

    /**
     * Check if the teacher has experience.
     */
    public function hasExperience(): bool
    {
        return !empty($this->experience_years);
    }

    /**
     * Get the formatted join date.
     */
    public function getFormattedJoinDateAttribute(): string
    {
        return $this->join_date ? $this->join_date->format('F j, Y') : 'N/A';
    }

    /**
     * Get the primary hourly rate in the user's preferred currency.
     */
    public function getPrimaryHourlyRateAttribute(): ?float
    {
        // Default to NGN if available, otherwise USD
        return $this->hourly_rate_ngn ?? $this->hourly_rate_usd;
    }

    /**
     * Get the formatted hourly rate.
     */
    public function getFormattedHourlyRateAttribute(): string
    {
        $rate = $this->getPrimaryHourlyRateAttribute();
        if (!$rate) {
            return 'Not set';
        }

        $currency = $this->hourly_rate_ngn ? '₦' : '$';
        return $currency . number_format($rate, 2);
    }

    /**
     * Check if teacher has a permanent meeting link.
     */
    public function hasPermanentMeetingLink(): bool
    {
        return !empty($this->permanent_meeting_link);
    }

    /**
     * Get the permanent meeting link or null.
     */
    public function getPermanentMeetingLink(): ?string
    {
        return $this->permanent_meeting_link;
    }

    /**
     * Get all permanent meeting details.
     */
    public function getPermanentMeetingDetails(): ?array
    {
        if (!$this->hasPermanentMeetingLink()) {
            return null;
        }

        return [
            'link' => $this->permanent_meeting_link,
            'platform' => $this->permanent_meeting_platform,
            'meeting_id' => $this->permanent_meeting_id,
            'password' => $this->permanent_meeting_password,
            'created_at' => $this->permanent_meeting_created_at,
        ];
    }
}
