<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Dispute extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'complaint_id',
        'filed_by',
        'against',
        'subject',
        'issue',
        'status',
        'resolved_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($dispute) {
            // Generate complaint ID if not provided
            if (!$dispute->complaint_id) {
                $latestDispute = static::orderBy('id', 'desc')->first();
                $nextId = $latestDispute ? $latestDispute->id + 1 : 1;
                $dispute->complaint_id = 'DSP-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Get the user that filed the dispute.
     */
    public function filer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'filed_by');
    }

    /**
     * Get the user that the dispute is against.
     */
    public function respondent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'against');
    }

    /**
     * Get the attachments for the dispute.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(EvidenceAttachment::class, 'attachable');
    }

    /**
     * Get the action logs for the dispute.
     */
    public function actionLogs(): MorphMany
    {
        return $this->morphMany(ActionLog::class, 'loggable');
    }
}
