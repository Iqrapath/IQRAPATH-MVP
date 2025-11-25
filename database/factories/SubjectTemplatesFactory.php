<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubjectTemplates>
 */
class SubjectTemplatesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subjects = [
            'Quran Recitation (Hifz)',
            'Tajweed',
            'Arabic Language',
            'Islamic Studies',
            'Quran Tafsir',
            'Hadith Studies',
            'Fiqh (Islamic Jurisprudence)',
            'Aqeedah (Islamic Creed)',
        ];

        return [
            'name' => fake()->unique()->randomElement($subjects),
            'is_active' => true,
        ];
    }
}
