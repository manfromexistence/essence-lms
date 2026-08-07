<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            'institution_name' => 'Dhaka IT Institute',
            'institution_address' => 'House #5 (2nd floor), Road #8, Block-C, Section-10, Mirpur-10, Dhaka-1216. Behind Dhaka WASA.',
            'institution_phone' => '+880 1682-71557',
            'institution_email' => 'dhakaitinstitute@gmail.com',
            'institution_website' => 'https://dhakaitinstitute.com',
            'footer_text' => 'Dhaka IT Institute — Let’s Build Your Dream',
        ];

        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'group' => 'institution', 'type' => 'string', 'updated_at' => now(), 'created_at' => now()]
            );
        }

        DB::table('courses')->whereIn('code', ['WEB001', 'DS001', 'DM001', 'MOB001', 'GD001'])
            ->orWhere('code', 'like', 'STD-CLS-%')
            ->update(['status' => 'inactive', 'updated_at' => now()]);

        foreach ([
            ['code' => 'DIT-MSO-01', 'name' => 'Advanced Microsoft Office', 'price' => 5000, 'duration' => 2, 'delivery_mode' => 'offline', 'category' => 'Office Productivity'],
            ['code' => 'DIT-WD-01', 'name' => 'Professional Web Design & Freelancing', 'price' => 20000, 'duration' => 5, 'delivery_mode' => 'offline', 'category' => 'Web Design'],
            ['code' => 'DIT-FBM-01', 'name' => 'Facebook Marketing & Ecommerce', 'price' => 10000, 'duration' => 6, 'delivery_mode' => 'online', 'category' => 'Digital Marketing'],
            ['code' => 'DIT-FSWD-01', 'name' => 'Full Stack Web Development', 'price' => 60000, 'duration' => 12, 'delivery_mode' => 'online', 'category' => 'Web Development'],
        ] as $course) {
            DB::table('courses')->updateOrInsert(
                ['code' => $course['code']],
                $course + [
                    'description' => 'Practical, project-based training with professional and freelancing workflow guidance. Contact Dhaka IT Institute to confirm the latest fee, syllabus and batch schedule.',
                    'duration_unit' => 'months',
                    'status' => 'active',
                    'level' => 'beginner',
                    'start_date' => now()->addWeek()->toDateString(),
                    'end_date' => now()->addMonths($course['duration'])->toDateString(),
                    'max_students' => 30,
                    'prerequisites' => json_encode(['Access to a computer or laptop']),
                    'objectives' => json_encode(['Build practical skills', 'Complete portfolio-ready projects', 'Understand professional marketplace workflows']),
                    'syllabus' => json_encode(['Fundamentals', 'Practical projects', 'Professional workflow', 'Freelancing guidance']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->updatePage('home', [
            'slide1_title' => 'Dhaka IT Institute-এ স্বাগতম',
            'slide1_subtitle' => 'প্র্যাকটিক্যাল স্কিল থেকে ফ্রিল্যান্সিং ক্যারিয়ার',
            'banner_title' => 'Dhaka IT Institute',
            'banner_title_highlight' => '— Let’s Build Your Dream',
            'banner_subtitle' => 'স্কিল শিখুন, প্রজেক্ট করুন, ক্যারিয়ার গড়ুন',
            'banner_description' => 'ওয়েব ডেভেলপমেন্ট, Microsoft Office, digital marketing ও freelancing-এ হাতে-কলমে প্রশিক্ষণ।',
            'about_section_text1' => 'Dhaka IT Institute মিরপুর-১০, ঢাকায় অবস্থিত একটি প্র্যাকটিক্যাল IT ও freelancing training center।',
            'about_section_text2' => 'আমাদের উদ্দেশ্য শিক্ষার্থীদের বাস্তব প্রজেক্ট, marketplace workflow, client communication এবং সফল কাজ delivery-এর জন্য প্রস্তুত করা।',
        ], 'Dhaka IT Institute | Freelancing and IT Training in Mirpur');

        $this->updatePage('about', [
            'about_title' => 'প্র্যাকটিক্যাল স্কিল ও ফ্রিল্যান্সিং প্রস্তুতি',
            'about_text' => 'Dhaka IT Institute একটি বেসরকারি IT ও freelancing training center। এখানে অনলাইন ও অফলাইন ক্লাসে হাতে-কলমে শেখানো হয় এবং marketplace ও project delivery সম্পর্কে গাইড করা হয়।',
            'mission_text' => 'শুধু প্রশিক্ষণ নয়—শিক্ষার্থীকে বাস্তব কাজ খোঁজা, পরিকল্পনা করা, client communication এবং সফল delivery-এর উপযোগী করে তোলা।',
            'vision_text' => 'প্র্যাকটিক্যাল IT দক্ষতা ও পেশাদার মানসিকতার মাধ্যমে কর্মসংস্থান এবং freelancing-এর জন্য আত্মবিশ্বাসী মানুষ তৈরি করা।',
            'stats_students' => 'অনলাইন',
            'stats_teachers' => 'অফলাইন',
            'stats_staff' => 'প্রজেক্ট',
            'stats_rooms' => 'সাপোর্ট',
            'stats_buildings' => 'ক্যারিয়ার',
        ], 'About Dhaka IT Institute');

        $this->updatePage('contact', [
            'address' => 'House #5 (2nd floor), Road #8, Block-C, Section-10, Mirpur-10, Dhaka-1216. Behind Dhaka WASA.',
            'phone' => '+880 1682-71557',
            'email' => 'dhakaitinstitute@gmail.com',
            'map_embed' => 'https://www.google.com/maps?q=House%205%20Road%208%20Block%20C%20Section%2010%20Mirpur%2010%20Dhaka%201216&output=embed',
        ], 'Contact Dhaka IT Institute');
    }

    private function updatePage(string $slug, array $values, string $metaTitle): void
    {
        $page = DB::table('pages')->where('slug', $slug)->first();
        if (!$page) {
            return;
        }

        $content = json_decode($page->content ?: '{}', true) ?: [];
        DB::table('pages')->where('slug', $slug)->update([
            'content' => json_encode(array_merge($content, $values), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'meta_title' => $metaTitle,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Brand/content migrations are intentionally not reverted to legacy identities.
    }
};
