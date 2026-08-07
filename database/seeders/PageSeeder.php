<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'title' => 'Home Page',
                'slug' => 'home',
                'meta_title' => 'Dhaka IT Institute | Freelancing and IT Training in Mirpur',
                'meta_description' => 'Practical online and offline IT, freelancing, web development, Microsoft Office and digital marketing training in Mirpur-10, Dhaka.',
                'content' => [
                    'slide1_title' => 'Dhaka IT Institute-এ স্বাগতম',
                    'slide1_subtitle' => 'প্র্যাকটিক্যাল স্কিল থেকে ফ্রিল্যান্সিং ক্যারিয়ার',
                    'slide1_image' => 'https://plus.unsplash.com/premium_photo-1677567996070-68fa4181775a?q=80&w=1172&auto=format&fit=crop',
                    'slide2_title' => 'প্রজেক্টভিত্তিক আইটি ট্রেনিং',
                    'slide2_subtitle' => 'অনলাইন ও অফলাইন ব্যাচ',
                    'slide2_image' => 'https://images.unsplash.com/photo-1509062522246-3755977927d7?w=1920',
                    'slide3_title' => 'মার্কেটপ্লেস প্রস্তুতি',
                    'slide3_subtitle' => 'শেখা, কাজ করা ও সফল ডেলিভারি',
                    'slide3_image' => 'https://images.unsplash.com/photo-1427504494785-3a9ca7044f45?w=1920',
                    'banner_image' => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=800',
                    'banner_title' => 'Dhaka IT Institute',
                    'banner_title_highlight' => '— Let’s Build Your Dream',
                    'banner_subtitle' => 'স্কিল শিখুন, প্রজেক্ট করুন, ক্যারিয়ার গড়ুন',
                    'banner_description' => 'ওয়েব ডেভেলপমেন্ট, Microsoft Office, ডিজিটাল মার্কেটিং ও ফ্রিল্যান্সিংয়ে হাতে-কলমে প্রশিক্ষণ। কোর্সের সঙ্গে রয়েছে প্র্যাকটিক্যাল প্রজেক্ট, মার্কেটপ্লেস গাইডলাইন এবং দীর্ঘমেয়াদি সাপোর্ট।',
                    'banner_button' => 'আমাদের সম্পর্কে জেনে নাও',
                    'courses_section_title' => 'জনপ্রিয় কোর্সসমূহ',
                    'courses_section_subtitle' => 'আমাদের সবচেয়ে জনপ্রিয় এবং চাহিদা সম্পন্ন কোর্সগুলি দেখুন',
                    'students_section_title' => 'শিক্ষার্থীদের অগ্রযাত্রা',
                    'students_section_subtitle' => 'নিয়মিত অনুশীলন, অ্যাসাইনমেন্ট ও বাস্তব প্রজেক্টে দক্ষতা অর্জন',
                    'about_section_title' => 'প্রতিষ্ঠান সম্পর্কে',
                    'about_section_text1' => 'Dhaka IT Institute মিরপুর-১০, ঢাকায় অবস্থিত একটি প্র্যাকটিক্যাল IT ও freelancing training center।',
                    'about_section_text2' => 'আমাদের উদ্দেশ্য শুধু প্রশিক্ষণ দেওয়া নয়—শিক্ষার্থীদের বাস্তব প্রজেক্ট, মার্কেটপ্লেস প্রস্তুতি, ক্লায়েন্ট কমিউনিকেশন এবং সফল কাজ ডেলিভারির জন্য প্রস্তুত করা।',
                    'about_section_button' => 'বিস্তারিত পড়ুন',
                    'about_section_image' => 'https://images.unsplash.com/photo-1562774053-701939374585?w=600',
                    'notice_title' => 'নোটিশ বোর্ড',
                    'notice_1' => 'নতুন শিক্ষাবর্ষের ভর্তি কার্যক্রম শুরু হয়েছে। বিস্তারিত জানতে যোগাযোগ করুন।',
                    'notice_view_all' => 'সকল নোটিশ',
                ],
            ],
            [
                'title' => 'About Page',
                'slug' => 'about',
                'meta_title' => 'About Dhaka IT Institute',
                'meta_description' => 'Learn about Dhaka IT Institute’s practical IT and freelancing training mission in Mirpur, Dhaka.',
                'content' => [
                    'page_title' => 'আমাদের সম্পর্কে',
                    'about_image' => 'https://images.unsplash.com/photo-1606761568499-6d2451b23c66?w=500&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8Mnx8dW5pdmVyc2l0eXxlbnwwfHwwfHx8MA%3D%3D',
                    'about_title' => 'প্র্যাকটিক্যাল স্কিল ও ফ্রিল্যান্সিং প্রস্তুতি',
                    'about_text' => 'Dhaka IT Institute একটি বেসরকারি IT ও freelancing training center। এখানে অনলাইন ও অফলাইন ক্লাসে হাতে-কলমে শেখানো হয় এবং শিক্ষার্থীদের marketplace account, portfolio, bidding, client communication ও project delivery সম্পর্কে গাইড করা হয়।',
                    'stats_students' => 'অনলাইন',
                    'stats_teachers' => 'অফলাইন',
                    'stats_staff' => 'প্রজেক্ট',
                    'stats_rooms' => 'সাপোর্ট',
                    'stats_buildings' => 'ক্যারিয়ার',
                    'mission_title' => 'আমাদের লক্ষ্য (Mission)',
                    'mission_text' => 'আমাদের লক্ষ্য শুধু সফটওয়্যার শেখানো নয়; শিক্ষার্থীকে বাস্তব কাজ খোঁজা, কাজ পরিকল্পনা করা, ক্লায়েন্টের সঙ্গে যোগাযোগ এবং সফলভাবে কাজ সম্পন্ন করার উপযোগী করে তোলা।',
                    'vision_image' => 'https://images.unsplash.com/photo-1509062522246-3755977927d7?w=800',
                    'vision_title' => 'আমাদের ভিশন (Vision)',
                    'vision_text' => 'প্র্যাকটিক্যাল IT দক্ষতা, পেশাদার মানসিকতা এবং ধারাবাহিক সাপোর্টের মাধ্যমে কর্মসংস্থান ও freelancing-এর জন্য আত্মবিশ্বাসী দক্ষ জনশক্তি তৈরি করা।',
                ],
            ],
            [
                'title' => 'Contact Page',
                'slug' => 'contact',
                'meta_title' => 'Contact Dhaka IT Institute',
                'meta_description' => 'Get in touch with us.',
                'content' => [
                    'page_title' => 'যোগাযোগ করুন',
                    'page_subtitle' => 'যেকোনো প্রয়োজনে আমাদের সাথে যোগাযোগ করুন',
                    'form_title' => 'বার্তা পাঠান',
                    'address' => 'House #5 (2nd floor), Road #8, Block-C, Section-10, Mirpur-10, Dhaka-1216. Behind Dhaka WASA.',
                    'phone' => '+880 1682-71557',
                    'email' => 'dhakaitinstitute@gmail.com',
                    'map_embed' => 'https://www.google.com/maps?q=House%205%20Road%208%20Block%20C%20Section%2010%20Mirpur%2010%20Dhaka%201216&output=embed',
                ],
            ],
            [
                'title' => 'Courses Page',
                'slug' => 'courses',
                'meta_title' => 'Our Courses',
                'meta_description' => 'Explore our available courses.',
                'content' => [
                    'page_title' => 'কোর্সসমূহ',
                    'page_subtitle' => 'আমাদের সকল একাডেমিক ও স্কিল ডেভেলপমেন্ট কোর্স',
                ],
            ],
            [
                'title' => 'Teachers Page',
                'slug' => 'teachers',
                'meta_title' => 'Our Teachers',
                'meta_description' => 'Meet our experienced faculty members.',
                'content' => [
                    'page_title' => 'শিক্ষকমণ্ডলী',
                    'page_subtitle' => 'আমাদের অভিজ্ঞ ও দক্ষ শিক্ষকমণ্ডলী',
                ],
            ],
            [
                'title' => 'Students Page',
                'slug' => 'students',
                'meta_title' => 'Our Students',
                'meta_description' => 'Student activities and achievements.',
                'content' => [
                    'page_title' => 'আমাদের শিক্ষার্থী',
                    'page_subtitle' => 'শিক্ষার্থীদের সকল তথ্য ও কার্যক্রম',
                    'stats_title' => 'শিক্ষার্থী পরিসংখ্যান',
                    'total_students' => 'Live data',
                    'male_students' => 'Live data',
                    'female_students' => 'Live data',
                    'attendance_rate' => 'Live data',
                    'activities_title' => 'সহশিক্ষা কার্যক্রম',
                    'activity1_title' => 'ডিবেটিং ক্লাব',
                    'activity1_text' => 'শিক্ষার্থীদের যুক্তি ও মেধা বিকাশের জন্য রয়েছে সক্রিয় ডিবেটিং ক্লাব।',
                    'activity2_title' => 'স্পোর্টস ক্লাব',
                    'activity2_text' => 'বার্ষিক ক্রীড়া প্রতিযোগিতা ও নিয়মিত খেলাধুলার ব্যবস্থা।',
                    'activity3_title' => 'সাংস্কৃতিক সংঘ',
                    'activity3_text' => 'নৃত্য, সংগীত ও আবৃত্তি চর্চার জন্য রয়েছে সাংস্কৃতিক সংঘ।',
                ],
            ],
            [
                'title' => 'Results Page',
                'slug' => 'results',
                'meta_title' => 'Exam Results',
                'meta_description' => 'Check exam results online.',
                'content' => [
                    'page_title' => 'ফলাফল',
                    'page_subtitle' => 'একাডেমিক পরীক্ষার ফলাফল দেখুন',
                    'search_title' => 'ফলাফল খুঁজুন',
                    'exam_type_label' => 'পরীক্ষার ধরন',
                    'roll_label' => 'রোল নম্বর',
                    'roll_placeholder' => 'রোল নম্বর ইংরেজিতে লিখুন (যেমন: 1001)',
                    'reg_label' => 'রেজিস্ট্রেশন নম্বর (অপশনাল)',
                    'search_button' => 'ফলাফল দেখুন',
                    'achievements_title' => 'আমাদের সাফল্য',
                    'avg_pass_rate' => 'Live data',
                    'avg_pass_rate_label' => 'গড় পাসের হার',
                    'gpa5_count' => 'Live data',
                    'gpa5_label' => 'মোট জিপিএ-৫',
                    'aplus_count' => 'Live data',
                    'aplus_label' => 'মোট এ+',
                    'scholarship_rate' => 'Live data',
                    'scholarship_label' => 'বৃত্তি প্রাপ্ত',
                ],
            ],
            [
                'title' => 'Services',
                'slug' => 'services',
                'meta_title' => 'IT Training and Digital Services | Dhaka IT Institute',
                'meta_description' => 'Professional IT training, freelancing mentorship, website development, digital marketing, domain and hosting services.',
                'content' => [],
            ],
            [
                'title' => 'Our Team',
                'slug' => 'team',
                'meta_title' => 'Our Training Team | Dhaka IT Institute',
                'meta_description' => 'Meet the practical instructors and support team at Dhaka IT Institute.',
                'content' => [],
            ],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(
                ['slug' => $page['slug']],
                [
                    'title' => $page['title'],
                    'meta_title' => $page['meta_title'],
                    'meta_description' => $page['meta_description'],
                    'content' => $page['content'],
                    'is_active' => true,
                ]
            );
        }
    }
}
