<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        Course::whereIn('code', ['WEB001', 'DS001', 'DM001', 'MOB001', 'GD001'])
            ->orWhere('code', 'like', 'STD-CLS-%')
            ->update(['status' => 'inactive']);

        $courses = [
            [
                'name' => 'Advanced Microsoft Office',
                'code' => 'DIT-MSO-01',
                'description' => 'Practical Microsoft Word, Excel and PowerPoint training with office documents, reports, presentations, Canva and productivity tools.',
                'price' => 5000,
                'duration' => 2,
                'duration_unit' => 'months',
                'delivery_mode' => 'offline',
                'category' => 'Office Productivity',
                'level' => 'beginner',
                'prerequisites' => ['Access to a computer or laptop'],
                'objectives' => ['Create professional office documents', 'Work confidently with spreadsheets', 'Build effective presentations'],
                'syllabus' => ['Microsoft Word', 'Microsoft Excel', 'Microsoft PowerPoint', 'Canva and productivity tools', 'Practical office projects'],
                'image' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=800&auto=format&fit=crop',
            ],
            [
                'name' => 'Professional Web Design & Freelancing',
                'code' => 'DIT-WD-01',
                'description' => 'Project-based website design using WordPress, Elementor, HTML, CSS and responsive design, combined with practical freelancing marketplace guidance.',
                'price' => 20000,
                'duration' => 5,
                'duration_unit' => 'months',
                'delivery_mode' => 'offline',
                'category' => 'Web Design',
                'level' => 'beginner',
                'prerequisites' => ['Basic computer and internet skills', 'Access to a computer or laptop'],
                'objectives' => ['Build responsive business websites', 'Create WordPress and ecommerce sites', 'Prepare a practical portfolio', 'Understand marketplace workflows'],
                'syllabus' => ['HTML and CSS', 'Responsive design', 'WordPress and Elementor', 'Ecommerce websites', 'Website security and optimization', 'Freelancing fundamentals'],
                'image' => 'https://images.unsplash.com/photo-1547658719-da2b51169166?w=800&auto=format&fit=crop',
            ],
            [
                'name' => 'Facebook Marketing & Ecommerce',
                'code' => 'DIT-FBM-01',
                'description' => 'Practical Facebook marketing, content planning, advertising fundamentals and ecommerce website workflows for business and freelancing.',
                'price' => 10000,
                'duration' => 6,
                'duration_unit' => 'months',
                'delivery_mode' => 'online',
                'category' => 'Digital Marketing',
                'level' => 'beginner',
                'prerequisites' => ['Basic internet and social media knowledge'],
                'objectives' => ['Plan Facebook marketing campaigns', 'Create useful marketing content', 'Understand ad and ecommerce workflows'],
                'syllabus' => ['Facebook page management', 'Content strategy', 'Advertising fundamentals', 'Audience and reporting', 'Ecommerce workflow', 'Client communication'],
                'image' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&auto=format&fit=crop',
            ],
            [
                'name' => 'Full Stack Web Development',
                'code' => 'DIT-FSWD-01',
                'description' => 'A long-form, project-based web development path covering modern frontend development, PHP, Laravel, databases, deployment and professional workflows.',
                'price' => 60000,
                'duration' => 12,
                'duration_unit' => 'months',
                'delivery_mode' => 'online',
                'category' => 'Web Development',
                'level' => 'intermediate',
                'prerequisites' => ['Basic computer skills', 'Commitment to regular practice', 'Access to a computer and internet'],
                'objectives' => ['Build production-oriented web applications', 'Work with databases and APIs', 'Deploy portfolio projects', 'Prepare for freelance and junior development work'],
                'syllabus' => ['HTML, CSS and JavaScript', 'Modern frontend fundamentals', 'PHP and object-oriented programming', 'Laravel', 'SQL and database design', 'APIs, testing and deployment', 'Freelancing and client workflow'],
                'image' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=800&auto=format&fit=crop',
            ],
        ];

        foreach ($courses as $course) {
            Course::updateOrCreate(
                ['code' => $course['code']],
                $course + [
                    'status' => 'active',
                    'class' => null,
                    'start_date' => now()->addWeek(),
                    'end_date' => now()->addMonths($course['duration']),
                    'max_students' => 30,
                ]
            );
        }
    }
}
