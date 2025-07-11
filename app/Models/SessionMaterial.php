<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionMaterial extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'session_id',
        'title',
        'description',
        'file_path',
        'file_type',
        'uploaded_by_id',
    ];

    /**
     * Get the session that owns the material.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(TeachingSession::class, 'session_id');
    }

    /**
     * Get the user who uploaded the material.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    /**
     * Get the file extension.
     */
    public function getFileExtensionAttribute(): string
    {
        return pathinfo($this->file_path, PATHINFO_EXTENSION);
    }

    /**
     * Check if the file is an image.
     */
    public function getIsImageAttribute(): bool
    {
        return in_array($this->file_extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }

    /**
     * Check if the file is a document.
     */
    public function getIsDocumentAttribute(): bool
    {
        return in_array($this->file_extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt']);
    }

    /**
     * Check if the file is a video.
     */
    public function getIsVideoAttribute(): bool
    {
        return in_array($this->file_extension, ['mp4', 'mov', 'avi', 'wmv', 'flv', 'webm']);
    }

    /**
     * Check if the file is an audio.
     */
    public function getIsAudioAttribute(): bool
    {
        return in_array($this->file_extension, ['mp3', 'wav', 'ogg', 'aac', 'flac']);
    }
} 