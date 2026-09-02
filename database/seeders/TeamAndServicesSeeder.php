<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TeamAndServicesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Seeding team members...');
        $this->seedTeam();
        $this->command?->info('Seeding services catalog...');
        $this->seedServices();
    }

    private function seedTeam(): void
    {
        $teacherRole = Role::where('slug', 'teacher')->first();

        $members = [
            [
                'name' => 'Rafiqul Islam',
                'email' => 'rafiqul@example.com',
                'department' => 'Web Development',
                'designation' => 'Lead Web Instructor',
                'bio' => '10+ years of experience in full-stack web development. Passionate about teaching practical skills that help students land real freelance projects.',
                'image' => '/images/team-kawsar.jpg',
                'subjects' => ['PHP', 'Laravel', 'React', 'JavaScript'],
                'social' => ['facebook' => '#', 'linkedin' => '#', 'github' => '#'],
                'featured' => true, 'order' => 1,
            ],
            [
                'name' => 'Nusrat Jahan',
                'email' => 'nusrat@example.com',
                'department' => 'Digital Marketing',
                'designation' => 'Digital Marketing Strategist',
                'bio' => 'Facebook Ads expert with 7+ years helping businesses scale. Specializes in content strategy, campaign optimization, and performance marketing.',
                'image' => '/images/team-mikdad.jpg',
                'subjects' => ['Facebook Ads', 'SEO', 'Content Marketing', 'Analytics'],
                'social' => ['facebook' => '#', 'linkedin' => '#', 'instagram' => '#'],
                'featured' => true, 'order' => 2,
            ],
            [
                'name' => 'Tahmid Hasan',
                'email' => 'tahmid@example.com',
                'department' => 'Graphic Design',
                'designation' => 'Creative Design Lead',
                'bio' => 'Award-winning designer specializing in UI/UX, branding, and motion graphics. Teaches design thinking from concept to final delivery.',
                'image' => '/images/team-galib.jpg',
                'subjects' => ['UI/UX Design', 'Figma', 'Adobe Suite', 'Branding'],
                'social' => ['facebook' => '#', 'linkedin' => '#', 'github' => '#', 'website' => '#'],
                'featured' => true, 'order' => 3,
            ],
            [
                'name' => 'Fatima Akhter',
                'email' => 'fatima@example.com',
                'department' => 'Office Applications',
                'designation' => 'Microsoft Office Specialist',
                'bio' => 'Certified Microsoft Office trainer with 5+ years of corporate training experience. Makes complex tools simple and practical.',
                'image' => '/images/team-hasibur.jpg',
                'subjects' => ['MS Word', 'MS Excel', 'PowerPoint', 'Data Entry'],
                'social' => ['facebook' => '#', 'linkedin' => '#'],
                'featured' => false, 'order' => 4,
            ],
            [
                'name' => 'Shamim Ahmed',
                'email' => 'shamim@example.com',
                'department' => 'Python & Data Science',
                'designation' => 'Data Science Instructor',
                'bio' => 'Data scientist turned educator. Teaches Python, machine learning, and data analysis with real-world case studies.',
                'image' => '/images/team-hafez.jpg',
                'subjects' => ['Python', 'Data Science', 'Machine Learning', 'SQL'],
                'social' => ['linkedin' => '#', 'github' => '#'],
                'featured' => false, 'order' => 5,
            ],
            [
                'name' => 'Arifa Sultana',
                'email' => 'arifa@example.com',
                'department' => 'Freelancing',
                'designation' => 'Freelancing Mentor',
                'bio' => 'Top-rated freelancer on Fiverr and Upwork. Guides students through marketplace setup, bidding, client communication, and project delivery.',
                'image' => '/images/team-happy.jpg',
                'subjects' => ['Marketplace Setup', 'Bidding', 'Client Communication', 'Profile Optimization'],
                'social' => ['facebook' => '#', 'linkedin' => '#', 'website' => '#'],
                'featured' => true, 'order' => 6,
            ],
            [
                'name' => 'Kamrul Hasan',
                'email' => 'kamrul@example.com',
                'department' => 'Video Editing',
                'designation' => 'Video Production Specialist',
                'bio' => 'Professional video editor and motion designer. Teaches Premiere Pro, After Effects, and creative storytelling.',
                'image' => '/images/team-member-1.jpg',
                'subjects' => ['Premiere Pro', 'After Effects', 'Color Grading', 'Motion Graphics'],
                'social' => ['facebook' => '#', 'instagram' => '#'],
                'featured' => false, 'order' => 7,
            ],
            [
                'name' => 'Sabrina Nawar',
                'email' => 'sabrina@example.com',
                'department' => 'Spoken English',
                'designation' => 'Language & Communication Coach',
                'bio' => 'IELTS and communication specialist. Helps students build confidence in English speaking, writing, and professional communication.',
                'image' => '/images/team-jems.jpg',
                'subjects' => ['Spoken English', 'IELTS Prep', 'Business Writing', 'Presentation Skills'],
                'social' => ['facebook' => '#', 'linkedin' => '#'],
                'featured' => false, 'order' => 8,
            ],
        ];

        $teacherRoleId = $teacherRole?->id;
        // Preload existing teacher-role assignments once
        $assigned = $teacherRoleId
            ? \Illuminate\Support\Facades\DB::table('role_user')->where('role_id', $teacherRoleId)->pluck('user_id')->all()
            : [];
        $assignedSet = array_flip($assigned);
        // Preload existing users by email
        $existingUsers = User::whereIn('email', array_column($members, 'email'))->pluck('id', 'email');

        foreach ($members as $i => $data) {
            if (isset($existingUsers[$data['email']])) {
                $user = User::find($existingUsers[$data['email']]);
            } else {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => \Illuminate\Support\Facades\Hash::make('password', ['rounds' => 4]),
                    'is_active' => true,
                ]);
                $existingUsers[$data['email']] = $user->id;
            }
            if ($teacherRoleId && !isset($assignedSet[$user->id])) {
                \Illuminate\Support\Facades\DB::table('role_user')->insert([
                    'role_id' => $teacherRoleId,
                    'user_id' => $user->id,
                ]);
                $assignedSet[$user->id] = true;
            }

            Teacher::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'phone' => '017' . str_pad((string) (10000000 + $i * 113), 8, '0', STR_PAD_LEFT),
                    'department' => $data['department'],
                    'designation' => $data['designation'],
                    'bio' => $data['bio'],
                    'profile_image' => $data['image'],
                    'social_links' => $data['social'],
                    'subjects' => $data['subjects'],
                    'is_featured' => $data['featured'],
                    'display_order' => $data['order'],
                    'status' => 'active',
                    'salary' => 30000 + ($i * 7000),
                ]
            );
        }
    }

    private function seedServices(): void
    {
        $services = [
            [
                'title' => 'Professional Web Development',
                'icon' => 'fa-globe',
                'badge' => 'Bestseller',
                'short_description' => 'Full-stack web development training covering HTML, CSS, JavaScript, PHP, Laravel, and React.',
                'description' => "Master web development from the ground up. This comprehensive program covers:\n\n• Frontend: HTML5, CSS3, Tailwind CSS, JavaScript, React\n• Backend: PHP, Laravel, REST APIs\n• Database: MySQL, query optimization\n• Tools: Git, GitHub, VS Code, npm\n• Deployment: cPanel, VPS, cloud hosting\n\nHands-on projects throughout the course with real client scenarios. By the end, you'll be able to build and deploy complete web applications.",
                'features' => ['Hands-on projects', 'Real client scenarios', 'Lifetime access', 'Certificate', 'Job placement support', 'Mentorship sessions'],
                'faqs' => [['q' => 'Do I need prior coding experience?', 'a' => 'No — the course starts from absolute basics and progresses to advanced topics.'], ['q' => 'How long is the course?', 'a' => 'The training runs for 4 months with 2 hours of daily sessions.']],
                'price' => 15000, 'compare_price' => 22000,
                'image' => '/images/course-laravel.jpg',
                'featured' => true, 'order' => 1,
            ],
            [
                'title' => 'Digital Marketing Mastery',
                'icon' => 'fa-bullhorn',
                'badge' => 'Popular',
                'short_description' => 'Complete digital marketing program: Facebook Ads, Google Ads, SEO, and content strategy.',
                'description' => "Become a certified digital marketer. This practical course covers:\n\n• Facebook & Instagram Ads (campaign setup, targeting, retargeting)\n• Google Ads & Analytics\n• SEO on-page, off-page, and technical\n• Content strategy and planning\n• Email marketing and automation\n• Real campaign budgets for hands-on practice",
                'features' => ['Live campaign practice', 'Real ad spend budget', 'Analytics dashboard access', 'Portfolio building', 'Freelancing guidance'],
                'faqs' => [['q' => 'Will I get to run actual ads?', 'a' => 'Yes! Each student gets a small real budget to practice campaign management.']],
                'price' => 12000, 'compare_price' => 18000,
                'image' => '/images/marketing-post-1.png',
                'featured' => true, 'order' => 2,
            ],
            [
                'title' => 'Graphic Design Professional',
                'icon' => 'fa-paint-brush',
                'badge' => null,
                'short_description' => 'Master UI/UX design, branding, and visual storytelling with Figma and Adobe Creative Suite.',
                'description' => "Learn professional graphic design with hands-on practice:\n\n• UI/UX design principles\n• Figma for web and mobile prototyping\n• Adobe Photoshop and Illustrator\n• Branding and visual identity\n• Social media design\n• Portfolio development",
                'features' => ['Portfolio projects', 'Industry mentors', 'Software access', 'Design critique sessions', 'Freelance marketplace prep'],
                'faqs' => [['q' => 'Do I need a powerful PC?', 'a' => 'A standard laptop with 8GB RAM is sufficient. We use cloud-based tools where possible.']],
                'price' => 10000, 'compare_price' => 15000,
                'image' => '/images/marketing-post-2.png',
                'featured' => false, 'order' => 3,
            ],
            [
                'title' => 'Microsoft Office Suite',
                'icon' => 'fa-file-word',
                'badge' => 'Essential',
                'short_description' => 'Comprehensive Office training: MS Word, Excel, PowerPoint for professional workplace readiness.',
                'description' => "Master office productivity tools used in every workplace:\n\n• MS Word: Advanced formatting, mail merge, templates\n• MS Excel: Formulas, pivot tables, charts, data analysis\n• MS PowerPoint: Professional presentations, animations\n• Practical office workflow scenarios",
                'features' => ['Real-world exercises', 'Certificate', 'Typing speed training', 'Bangla typing', 'Office-ready skills'],
                'faqs' => [['q' => 'Is this suitable for beginners?', 'a' => 'Absolutely! We start from the basics and progress to advanced features.']],
                'price' => 5000, 'compare_price' => 8000,
                'image' => '/images/course-job-banner.jpg',
                'featured' => false, 'order' => 4,
            ],
            [
                'title' => 'Freelancing Bootcamp',
                'icon' => 'fa-briefcase',
                'badge' => 'Top Rated',
                'short_description' => 'Marketplace setup, profile optimization, bidding strategies, and client management for freelancers.',
                'description' => "Launch your freelance career with practical guidance:\n\n• Choosing the right marketplace (Fiverr, Upwork, Freelancer)\n• Profile setup and optimization\n• Gig/Service creation that converts\n• Effective bidding and proposal writing\n• Client communication and negotiation\n• Project delivery and getting 5-star reviews",
                'features' => ['Live profile review', 'Mock client sessions', 'Real gig creation', 'Payment gateway setup', 'Ongoing mentorship'],
                'faqs' => [['q' => 'Can I start without skills?', 'a' => 'We recommend completing at least one skill-based course first, then this bootcamp will help you sell it.']],
                'price' => 8000, 'compare_price' => 12000,
                'image' => '/images/seats-available.png',
                'featured' => true, 'order' => 5,
            ],
            [
                'title' => 'Domain, Hosting & Deployment',
                'icon' => 'fa-server',
                'badge' => null,
                'short_description' => 'Domain registration, hosting setup, cPanel management, deployment, and business email configuration.',
                'description' => "Learn the complete deployment pipeline:\n\n• Domain registration and DNS management\n• Shared, VPS, and cloud hosting comparison\n• cPanel and server management\n• SSL certificate installation\n• Business email setup (Google Workspace)\n• Website deployment workflows",
                'features' => ['Live deployment', 'Free domain practice', 'cPanel access', 'Security basics', 'Ongoing support'],
                'faqs' => [['q' => 'Will I get a domain for practice?', 'a' => 'Yes, we provide temp subdomains and a free domain for your first deployment.']],
                'price' => 4000, 'compare_price' => null,
                'image' => '/images/course-tech.jpg',
                'featured' => false, 'order' => 6,
            ],
        ];

        foreach ($services as $data) {
            Service::updateOrCreate(
                ['slug' => Str::slug($data['title'])],
                [
                    'title' => $data['title'],
                    'icon' => $data['icon'],
                    'badge' => $data['badge'],
                    'short_description' => $data['short_description'],
                    'description' => $data['description'],
                    'features' => $data['features'],
                    'faqs' => $data['faqs'],
                    'price' => $data['price'],
                    'compare_price' => $data['compare_price'],
                    'image' => $data['image'],
                    'is_active' => true,
                    'is_featured' => $data['featured'],
                    'display_order' => $data['order'],
                ]
            );
        }
    }
}
