<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Feedback extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'subject',
        'message',
        'feedback_type',
        'status',
    ];

    /**
     * Get the user that submitted the feedback.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attachments for the feedback.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(EvidenceAttachment::class, 'attachable');
    }

    /**
     * Get the action logs for the feedback.
     */
    public function actionLogs(): MorphMany
    {
        return $this->morphMany(ActionLog::class, 'loggable');
    }
}
