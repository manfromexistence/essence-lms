<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseVideo;
use Illuminate\Database\Seeder;

class CourseVideoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Seeding course videos...');

        // Real Educational YouTube video IDs
        $youtubeIds = [
            'RF_eOpCLayA', // Learn Python - Full Course for Beginners (freeCodeCamp)
            'PkZNo7MFNFg', // Learn JavaScript - Full Course for Beginners (freeCodeCamp)
            'pQN-pnXPaVg', // HTML Full Course - Build a Website Tutorial (freeCodeCamp)
            'yfoY53QXEnI', // CSS Tutorial - Zero to Hero (freeCodeCamp)
            'RGOj5yH7evk', // Git and GitHub for Beginners - Crash Course (freeCodeCamp)
            'mJrhQ-_6pkk', // Docker Tutorial for Beginners (Programming with Mosh)
            'SLpUKAGnm-g', // SQL Tutorial - Full Database Course for Beginners (freeCodeCamp)
            'nu_pCVPKzTk', // React Course - Beginner's Tutorial for React (freeCodeCamp)
            'ENrzD9HAZK4', // Node.js Tutorial for Beginners (Programming with Mosh)
            '_uQrJ0TkZlc', // Python Tutorial - Python Full Course for Beginners (Programming with Mosh)
            'XsxDH4HcOWA', // JavaScript Tutorial for Beginners (Programming with Mosh)
            '8JJ101D3knE', // Git Tutorial for Beginners - Learn Git in 1 Hour (Programming with Mosh)
            'zOjov-2OZ0E', // React Tutorial for Beginners (Programming with Mosh)
            'Wm6CUkswsNw', // Data Structures Easy to Advanced Course (freeCodeCamp)
            'RBSGKlAvoiM', // Data Structures and Algorithms in Python (freeCodeCamp)
            'Xj7k7RYu-r4', // Web Development In 2024 - A Practical Guide (Traversy Media)
            'hdI2bqOjy3c', // JavaScript Crash Course For Beginners (Traversy Media)
            'Oe421EPjeBE', // Next.js Crash Course (Traversy Media)
            'vmEHCJofslg', // Web Design Tutorial (DesignCourse)
            'UB1O30fR-EE', // HTML Crash Course For Absolute Beginners (Traversy Media)
        ];

        // Get all existing courses
        $courses = Course::all();

        if ($courses->isEmpty()) {
            $this->command?->warn('No courses found! Please seed courses first.');
            return;
        }

        $created = 0;
        $updated = 0;

        foreach ($courses as $course) {
            // Ensure a consistent preview lesson per course
            CourseVideo::updateOrCreate(
                [
                    'course_id' => $course->id,
                    'order' => 1,
                ],
                [
                    'title' => $course->name . ' — Free Orientation Class',
                    'description' => 'Watch this free demo lesson to understand the course teaching style and practical learning approach.',
                    'video_type' => 'youtube',
                    'external_id' => $youtubeIds[$course->id % count($youtubeIds)],
                    'duration' => rand(600, 2400),
                    'is_preview' => true,
                ]
            ) ? $created++ : $updated++;

            // Add a few more lessons if the course has none beyond the preview
            $existing = $course->videos()->count();
            $target = max($existing, rand(4, 6));

            for ($j = 2; $j <= $target; $j++) {
                if ($course->videos()->where('order', $j)->exists()) {
                    continue;
                }
                CourseVideo::create([
                    'course_id' => $course->id,
                    'title' => "Lesson {$j}: Introduction to Topic {$j}",
                    'description' => "This is a detailed video explanation for lesson {$j} of {$course->name}.",
                    'video_type' => 'youtube',
                    'external_id' => $youtubeIds[array_rand($youtubeIds)],
                    'duration' => rand(300, 3600),
                    'order' => $j,
                    'is_preview' => false,
                ]);
            }
        }

        $this->command?->info("Seeded course videos: {$created} created, {$updated} updated for {$courses->count()} courses.");
    }
}
