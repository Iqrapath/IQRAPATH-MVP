<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Subject;
use App\Models\SubjectTemplates;
use App\Models\TeacherProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subject>
 */
class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'teacher_profile_id' => TeacherProfile::factory(),
            'subject_template_id' => SubjectTemplates::factory(),
            'teacher_notes' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
