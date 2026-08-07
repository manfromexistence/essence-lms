<?php

namespace Database\Seeders;

use App\Models\CertificateTemplate;
use Illuminate\Database\Seeder;

class CertificateTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $defaultElements = [
            ['type' => 'text', 'content' => '{institution_name}', 'x' => 50, 'y' => 110, 'width' => 1100, 'fontSize' => 46, 'fontFamily' => 'Georgia, serif', 'color' => '#14532d', 'bold' => true, 'align' => 'center', 'letterSpacing' => 0, 'opacity' => 1, 'rotation' => 0],
            ['type' => 'text', 'content' => 'CERTIFICATE OF COMPLETION', 'x' => 50, 'y' => 190, 'width' => 1100, 'fontSize' => 24, 'fontFamily' => 'Arial, sans-serif', 'color' => '#166534', 'bold' => true, 'align' => 'center', 'letterSpacing' => 8, 'opacity' => 1, 'rotation' => 0],
            ['type' => 'text', 'content' => 'This certifies that', 'x' => 50, 'y' => 290, 'width' => 1100, 'fontSize' => 22, 'fontFamily' => 'Georgia, serif', 'color' => '#374151', 'align' => 'center', 'letterSpacing' => 0, 'opacity' => 1, 'rotation' => 0],
            ['type' => 'text', 'content' => '{student_name}', 'x' => 50, 'y' => 345, 'width' => 1100, 'fontSize' => 60, 'fontFamily' => 'Georgia, serif', 'color' => '#111827', 'bold' => true, 'italic' => true, 'align' => 'center', 'letterSpacing' => 0, 'opacity' => 1, 'rotation' => 0],
            ['type' => 'text', 'content' => 'has successfully completed all learning requirements for', 'x' => 50, 'y' => 450, 'width' => 1100, 'fontSize' => 20, 'fontFamily' => 'Georgia, serif', 'color' => '#374151', 'align' => 'center', 'letterSpacing' => 0, 'opacity' => 1, 'rotation' => 0],
            ['type' => 'text', 'content' => '{course_name}', 'x' => 50, 'y' => 505, 'width' => 1100, 'fontSize' => 40, 'fontFamily' => 'Georgia, serif', 'color' => '#166534', 'bold' => true, 'align' => 'center', 'letterSpacing' => 0, 'opacity' => 1, 'rotation' => 0],
            ['type' => 'text', 'content' => 'Grade: {grade}', 'x' => 50, 'y' => 585, 'width' => 1100, 'fontSize' => 20, 'fontFamily' => 'Arial, sans-serif', 'color' => '#374151', 'align' => 'center', 'letterSpacing' => 0, 'opacity' => 1, 'rotation' => 0],
            ['type' => 'image', 'imageField' => 'logo', 'x' => 520, 'y' => 25, 'width' => 160, 'height' => 60, 'opacity' => 1, 'rotation' => 0],
            ['type' => 'image', 'imageField' => 'signature', 'x' => 520, 'y' => 660, 'width' => 170, 'height' => 70, 'opacity' => 1, 'rotation' => 0],
            ['type' => 'text', 'content' => 'Certificate No: {certificate_number}  |  Issued: {issued_at}', 'x' => 50, 'y' => 790, 'width' => 1100, 'fontSize' => 14, 'fontFamily' => 'Arial, sans-serif', 'color' => '#6b7280', 'align' => 'center', 'letterSpacing' => 0, 'opacity' => 1, 'rotation' => 0],
            ['type' => 'text', 'content' => 'Verify: {verification_code}', 'x' => 50, 'y' => 820, 'width' => 1100, 'fontSize' => 12, 'fontFamily' => 'Arial, sans-serif', 'color' => '#9ca3af', 'align' => 'center', 'letterSpacing' => 0, 'opacity' => 1, 'rotation' => 0],
        ];
        $defaultLayout = ['elements' => $defaultElements, 'background_opacity' => 0.6];

        $templates = [
            [
                'name' => 'Dhaka IT Course Completion',
                'type' => 'course_completion',
                'width' => 1200,
                'height' => 900,
                'is_active' => true,
                'is_default' => true,
                'layout_config' => $defaultLayout,
                'variables' => ['student_name', 'course_name', 'certificate_number', 'verification_code', 'issued_at', 'grade'],
            ],
            [
                'name' => 'Dhaka IT Achievement',
                'type' => 'achievement',
                'width' => 1200,
                'height' => 900,
                'is_active' => true,
                'is_default' => false,
                'layout_config' => $defaultLayout,
                'variables' => ['student_name', 'course_name', 'certificate_number', 'verification_code', 'issued_at'],
            ],
            [
                'name' => 'Dhaka IT Participation',
                'type' => 'participation',
                'width' => 1200,
                'height' => 900,
                'is_active' => true,
                'is_default' => false,
                'layout_config' => $defaultLayout,
                'variables' => ['student_name', 'certificate_number', 'verification_code', 'issued_at'],
            ],
        ];

        foreach ($templates as $data) {
            CertificateTemplate::updateOrCreate(
                ['type' => $data['type']],
                $data
            );
        }

        $this->command?->info('Seeded certificate templates.');
    }
}
