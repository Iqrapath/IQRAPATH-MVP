<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subject extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'teacher_profile_id',
        'subject_template_id',
        'teacher_notes',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the teacher profile that owns the subject.
     */
    public function teacherProfile(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class);
    }

    /**
     * Get the subject template for this subject.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(SubjectTemplates::class, 'subject_template_id');
    }

    /**
     * Get the subject name from the template.
     */
    public function getNameAttribute()
    {
        return $this->template->name ?? 'Unknown Subject';
    }
}
