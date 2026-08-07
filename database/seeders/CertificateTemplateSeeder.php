<?php

namespace Database\Seeders;

use App\Models\CertificateTemplate;
use Illuminate\Database\Seeder;

class CertificateTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Dhaka IT Course Completion',
                'type' => 'course_completion',
                'width' => 1200,
                'height' => 900,
                'is_active' => true,
                'is_default' => true,
                'variables' => ['student_name', 'course_name', 'certificate_number', 'verification_code', 'issued_at', 'grade'],
            ],
            [
                'name' => 'Dhaka IT Achievement',
                'type' => 'achievement',
                'width' => 1200,
                'height' => 900,
                'is_active' => true,
                'is_default' => false,
                'variables' => ['student_name', 'course_name', 'certificate_number', 'verification_code', 'issued_at'],
            ],
            [
                'name' => 'Dhaka IT Participation',
                'type' => 'participation',
                'width' => 1200,
                'height' => 900,
                'is_active' => true,
                'is_default' => false,
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
