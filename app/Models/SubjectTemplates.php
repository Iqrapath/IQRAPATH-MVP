<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectTemplates extends Model
{
    /** @use HasFactory<\Database\Factories\SubjectTemplatesFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationship with teacher subjects
    public function teacherSubjects()
    {
        return $this->hasMany(Subject::class, 'subject_template_id');
    }

    // Get all teachers who teach this subject
    public function teachers()
    {
        return $this->belongsToMany(TeacherProfile::class, 'subjects', 'subject_template_id', 'teacher_profile_id');
    }
}
