<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubjectTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            'Hifz',
            'Tajweed',
            'Quran Recitation',
            'Tafsir',
            'Hadith',
            'Fiqh',
            'Tawheed',
            'Islamic Studies',
            'Islamic History',
            'Islamic Etiquette',
            'Qaida',
            'Arabic Language',
            'Advanced Tajweed',
        ];

        foreach ($subjects as $subjectName) {
            \App\Models\SubjectTemplates::updateOrCreate(
                ['name' => $subjectName],
                ['is_active' => true]
            );
        }
    }
}
