<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use App\Models\VideoView;
use Illuminate\Support\Str;

class CertificateService
{
    public function courseIsComplete(Student $student, Course $course): bool
    {
        $videoIds = $course->videos()->pluck('id');

        return $videoIds->isNotEmpty()
            && VideoView::where('student_id', $student->id)
                ->whereIn('course_video_id', $videoIds)
                ->where('completed', true)
                ->distinct('course_video_id')
                ->count('course_video_id') === $videoIds->count();
    }

    public function issueForCompletedCourse(Student $student, Course $course, ?User $issuer = null): ?Certificate
    {
        if (!$this->courseIsComplete($student, $course)) {
            return null;
        }

        return $this->issue($student, $course, $issuer);
    }

    public function issue(Student $student, Course $course, ?User $issuer = null): Certificate
    {
        // Use the default template, or fall back to creating a basic one
        $template = CertificateTemplate::where('is_default', true)->where('is_active', true)->first()
            ?? CertificateTemplate::firstOrCreate(
                ['type' => 'course_completion', 'is_default' => true],
                ['name' => 'Dhaka IT Institute Course Completion', 'is_active' => true]
            );
        $issuer ??= User::whereHas('roles', fn ($query) => $query->whereIn('slug', ['super-admin', 'admin']))->first();
        $issuer ??= $student->user;

        return Certificate::firstOrCreate(
            ['student_id' => $student->id, 'course_id' => $course->id],
            [
                'template_id' => $template->id,
                'certificate_number' => 'DII-' . now()->format('Ym') . '-' . str_pad((string) $student->id, 5, '0', STR_PAD_LEFT) . '-' . str_pad((string) $course->id, 3, '0', STR_PAD_LEFT),
                'verification_code' => strtoupper(Str::random(12)),
                'issued_at' => now(),
                'issued_by' => $issuer->id,
                'status' => 'active',
            ]
        );
    }
}
