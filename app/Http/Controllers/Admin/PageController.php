<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index()
    {
        $pages = Page::orderBy('title')->get();
        return view('dashboard.cms.index', compact('pages'));
    }

    public function create()
    {
        return view('dashboard.cms.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'slug' => 'required|string|max:255|unique:pages',
            'title' => 'required|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        Page::create([
            'slug' => $request->slug,
            'title' => $request->title,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'content' => [],
            'sections' => [],
            'is_active' => true,
        ]);

        return redirect()->route('dashboard.cms.index')->with('success', 'Page created successfully.');
    }

    public function edit(Page $page)
    {
        return view('dashboard.cms.edit', compact('page'));
    }

    public function update(Request $request, Page $page)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        // Handle content from specific page editors (home, about, contact)
        $content = $request->input('content', []);

        // Handle content from generic editor with key-value pairs
        if ($request->has('content_keys')) {
            $keys = $request->input('content_keys', []);
            $values = $request->input('content_values', []);
            $content = [];
            foreach ($keys as $index => $key) {
                if (!empty($key)) {
                    $content[$key] = $values[$index] ?? '';
                }
            }
        } else {
            // Page editors only submit changed fields — keep everything else.
            $content = array_merge($page->content ?? [], $content);
        }

        // Image inputs submit URL text (or a filename when a file is picked) as content[<key>].
        // Wherever a file was actually uploaded, store it and replace the value with the storage path.
        foreach (array_keys($content) as $key) {
            if ($request->hasFile('content.' . $key)) {
                $content[$key] = 'storage/' . $request->file('content.' . $key)->store('cms', 'public');
            }
        }

        // Cleared fields fall back to view defaults instead of rendering empty.
        $content = array_filter($content, fn ($v) => trim((string) $v) !== '');

        $sections = $request->input('sections', []);

        $page->update([
            'title' => $request->title,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'content' => $content,
            'sections' => $sections,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Page updated successfully.');
    }

    public function destroy(Page $page)
    {
        $page->delete();
        return redirect()->route('dashboard.cms.index')->with('success', 'Page deleted successfully.');
    }

    // Specific page editors
    public function editHome()
    {
        $page = Page::firstOrCreate(
            ['slug' => 'home'],
            [
                'title' => 'Home Page',
                'content' => $this->getDefaultHomeContent(),
                'sections' => [],
            ]
        );
        return view('dashboard.cms.edit-home', compact('page'));
    }

    public function editAbout()
    {
        $page = Page::firstOrCreate(
            ['slug' => 'about'],
            [
                'title' => 'About Page',
                'content' => $this->getDefaultAboutContent(),
                'sections' => [],
            ]
        );
        return view('dashboard.cms.edit-about', compact('page'));
    }

    public function editContact()
    {
        $page = Page::firstOrCreate(
            ['slug' => 'contact'],
            [
                'title' => 'Contact Page',
                'content' => $this->getDefaultContactContent(),
                'sections' => [],
            ]
        );
        return view('dashboard.cms.edit-contact', compact('page'));
    }

    public function editCourses()
    {
        $page = Page::firstOrCreate(
            ['slug' => 'courses'],
            [
                'title' => 'Courses Page',
                'content' => $this->getDefaultCoursesContent(),
                'sections' => [],
            ]
        );
        return view('dashboard.cms.edit-courses', compact('page'));
    }

    public function editServices()
    {
        $page = Page::firstOrCreate(
            ['slug' => 'services'],
            [
                'title' => 'Services Page',
                'content' => $this->getDefaultServicesContent(),
                'sections' => [],
            ]
        );
        return view('dashboard.cms.edit-services', compact('page'));
    }

    public function editTeam()
    {
        $page = Page::firstOrCreate(
            ['slug' => 'team'],
            [
                'title' => 'Team Page',
                'content' => $this->getDefaultTeamContent(),
                'sections' => [],
            ]
        );
        return view('dashboard.cms.edit-team', compact('page'));
    }

    protected function getDefaultHomeContent(): array
    {
        return [
            // Hero Slider
            'slide1_title' => 'Dhaka IT Institute-এ স্বাগতম',
            'slide1_subtitle' => 'প্র্যাকটিক্যাল স্কিল থেকে ফ্রিল্যান্সিং ক্যারিয়ার',
            'slide1_image' => '/images/homepage-banner.png',
            'slide2_title' => 'শিখুন, অনুশীলন করুন, আয় করুন',
            'slide2_subtitle' => 'রিয়েল প্রজেক্ট নিয়ে হাতে-কলমে প্রশিক্ষণ',
            'slide2_image' => '/images/slide-classroom.png',
            'slide3_title' => 'ফ্রিল্যান্সিং ও জব মার্কেটে প্রস্তুতি',
            'slide3_subtitle' => 'এক্সপার্ট মেন্টরশিপে আপনার ক্যারিয়ার গড়ুন',
            'slide3_image' => '/images/slide-campus.png',
            // Banner Section
            'banner_image' => '/images/marketing-post-1.png',
            'banner_title' => 'Dhaka IT Institute',
            'banner_title_highlight' => '— Let’s Build Your Dream',
            'banner_subtitle' => 'স্কিল শিখুন, প্রজেক্ট করুন, ক্যারিয়ার গড়ুন',
            'banner_description' => 'ওয়েব ডেভেলপমেন্ট, Microsoft Office, digital marketing ও freelancing-এ হাতে-কলমে প্রশিক্ষণ। বাস্তব প্রজেক্ট, marketplace workflow এবং client communication-এর মাধ্যমে সফল ক্যারিয়ার শুরু করুন।',
            'banner_button' => 'কোর্সসমূহ দেখুন',
            // Courses Section
            'courses_section_title' => 'জনপ্রিয় কোর্সসমূহ',
            'courses_section_subtitle' => 'আমাদের সবচেয়ে জনপ্রিয় এবং চাহিদা সম্পন্ন কোর্সগুলি দেখুন',
            // Students Section
            'students_section_title' => 'আমাদের সেরা শিক্ষার্থীরা',
            'students_section_subtitle' => 'যারা এক্সেলেন্স এবং ডেডিকেশনের সাথে তাদের শিক্ষাজীবন অতিবাহিত করছেন',
            // Random Students Section
            'random_students_title' => 'আমাদের শিক্ষার্থীরা',
            'random_students_subtitle' => 'আমাদের প্রতিষ্ঠানের মেধাবী ও পরিশ্রমী শিক্ষার্থীদের সাথে পরিচিত হন',
            // About Section
            'about_section_image' => '/images/course-laravel-lg.jpg',
            'about_section_title' => 'প্রতিষ্ঠান সম্পর্কে',
            'about_section_text1' => 'Dhaka IT Institute মিরপুর-১০-এ অবস্থিত একটি প্র্যাকটিক্যাল IT ও freelancing training center।',
            'about_section_text2' => 'আমাদের লক্ষ্য শিক্ষার্থীদের বাস্তব প্রজেক্ট, marketplace workflow এবং সফল কাজ delivery-এর জন্য প্রস্তুত করা।',
            'about_section_button' => 'বিস্তারিত পড়ুন',
            // Notice Section
            'notice_title' => 'নোটিশ বোর্ড',
            'notice_1' => 'নতুন ব্যাচের ভর্তি কার্যক্রম শুরু হয়েছে — আসন সীমিত!',
            'notice_view_all' => 'সকল নোটিশ',
        ];
    }

    protected function getDefaultAboutContent(): array
    {
        return [
            'page_title' => 'প্রতিষ্ঠান পরিচিতি',
            'about_image' => '/images/team-hafez.jpg',
            'about_title' => 'প্রতিষ্ঠান সম্পর্কে',
            'about_text' => 'Dhaka IT Institute একটি বেসরকারি IT ও freelancing training center, যেখানে অনলাইন ও অফলাইন প্র্যাকটিক্যাল প্রশিক্ষণ দেওয়া হয়।',
            'stats_students' => '৫২০',
            'stats_teachers' => '২০',
            'stats_staff' => '৮',
            'stats_rooms' => '১৫',
            'stats_buildings' => '৬',
            'mission_title' => 'প্রতিষ্ঠানের মিশন',
            'mission_text' => 'শিক্ষার্থীদের বাস্তব প্রজেক্ট, marketplace workflow, client communication এবং সফল কাজ delivery-এর জন্য প্রস্তুত করা।',
            'vision_image' => '/images/team-galib.jpg',
            'vision_title' => 'প্রতিষ্ঠানের ভিশন',
            'vision_text' => 'প্র্যাকটিক্যাল IT দক্ষতা ও পেশাদার মানসিকতার মাধ্যমে কর্মসংস্থান এবং freelancing-এর জন্য আত্মবিশ্বাসী মানুষ তৈরি করা।',
        ];
    }

    protected function getDefaultContactContent(): array
    {
        return [
            'page_title' => 'যোগাযোগ করুন',
            'page_subtitle' => 'আমাদের সাথে যোগাযোগ করার বিভিন্ন মাধ্যম',
            'form_title' => 'বার্তা পাঠান',
            'address' => 'House #5 (2nd floor), Road #8, Block-C, Section-10, Mirpur-10, Dhaka-1216',
            'phone' => '+880 1682-715570',
            'email' => 'dhakaitinstitute@gmail.com',
            'map_embed' => 'https://www.google.com/maps?q=House%205%20Road%208%20Block%20C%20Section%2010%20Mirpur%2010%20Dhaka%201216&output=embed',
        ];
    }

    protected function getDefaultCoursesContent(): array
    {
        return [
            'page_title' => 'Explore Our Courses',
            'page_subtitle' => 'Enhance your skills with our expert-led programs designed for the modern world.',
            'search_placeholder' => 'কোর্সের নাম লিখুন...',
            'all_categories' => 'সকল ক্যাটাগরি',
            'search_button' => 'খুঁজুন',
        ];
    }

    protected function getDefaultServicesContent(): array
    {
        return [
            'page_title' => 'Our Services',
            'page_subtitle' => 'Training, digital solutions and practical support for students, freelancers and growing businesses.',
            'cta_title' => 'আজই শুরু করুন আপনার যাত্রা',
            'cta_text' => 'আমাদের সাথে যোগাযোগ করুন এবং সঠিক কোর্স বেছে নিন — আপনার ক্যারিয়ারের পরবর্তী ধাপ শুরু হোক এখানেই।',
            'cta_button' => 'যোগাযোগ করুন',
        ];
    }

    protected function getDefaultTeamContent(): array
    {
        return [
            'page_title' => 'Meet Our Team',
            'page_subtitle' => 'Expert instructors and support professionals committed to hands-on mentorship and student outcomes.',
            'team_intro' => 'আমাদের অভিজ্ঞ প্রশিক্ষকরা আপনার প্রতিটি ধাপে পাশে আছেন — ক্লাসরুম থেকে মার্কেটপ্লেস পর্যন্ত।',
        ];
    }

    public function editTeachers()
    {
        $page = Page::firstOrCreate(
            ['slug' => 'teachers'],
            [
                'title' => 'Teachers Page',
                'content' => $this->getDefaultTeachersContent(),
                'sections' => [],
            ]
        );
        return view('dashboard.cms.edit-teachers', compact('page'));
    }

    public function editStudents()
    {
        $page = Page::firstOrCreate(
            ['slug' => 'students'],
            [
                'title' => 'Students Page',
                'content' => $this->getDefaultStudentsContent(),
                'sections' => [],
            ]
        );
        return view('dashboard.cms.edit-students', compact('page'));
    }

    public function editResults()
    {
        $page = Page::firstOrCreate(
            ['slug' => 'results'],
            [
                'title' => 'Results Page',
                'content' => $this->getDefaultResultsContent(),
                'sections' => [],
            ]
        );
        return view('dashboard.cms.edit-results', compact('page'));
    }

    protected function getDefaultTeachersContent(): array
    {
        return [
            'page_title' => 'আমাদের শিক্ষকমণ্ডলী',
            'page_subtitle' => 'অভিজ্ঞ ও দক্ষ শিক্ষকদের তালিকা',
        ];
    }

    protected function getDefaultStudentsContent(): array
    {
        return [
            'page_title' => 'শিক্ষার্থী তথ্য',
            'page_subtitle' => 'আমাদের শিক্ষার্থীদের সম্পর্কিত তথ্য ও পরিসংখ্যান',
            'stats_title' => 'শিক্ষার্থী পরিসংখ্যান',
            'total_students' => '২৫০০+',
            'total_students_label' => 'মোট শিক্ষার্থী',
            'male_students' => '১২০০',
            'male_students_label' => 'ছাত্র',
            'female_students' => '১৩০০',
            'female_students_label' => 'ছাত্রী',
            'attendance_rate' => '৯৫%',
            'attendance_rate_label' => 'উপস্থিতি হার',
            'class_distribution_title' => 'শ্রেণীভিত্তিক শিক্ষার্থী সংখ্যা',
            'activities_title' => 'শিক্ষার্থীদের কার্যক্রম',
            'activity1_title' => 'একাডেমিক কার্যক্রম',
            'activity1_text' => 'নিয়মিত ক্লাস, পরীক্ষা, এবং শিক্ষা সহায়ক কার্যক্রম।',
            'activity2_title' => 'সাংস্কৃতিক কার্যক্রম',
            'activity2_text' => 'বিতর্ক, আবৃত্তি, নাটক, সংগীত ইত্যাদি।',
            'activity3_title' => 'ক্রীড়া কার্যক্রম',
            'activity3_text' => 'ফুটবল, ক্রিকেট, ব্যাডমিন্টন এবং অন্যান্য খেলা।',
        ];
    }

    protected function getDefaultResultsContent(): array
    {
        return [
            'page_title' => 'পরীক্ষার ফলাফল',
            'page_subtitle' => 'আপনার ফলাফল অনুসন্ধান করুন',
            'search_title' => 'ফলাফল অনুসন্ধান',
            'exam_type_label' => 'পরীক্ষা নির্বাচন করুন',
            'reg_label' => 'রেজিস্ট্রেশন নম্বর',
            'reg_placeholder' => 'রেজিস্ট্রেশন নম্বর লিখুন (যেমন: 2026-STU-0001)',
            'search_button' => 'ফলাফল দেখুন',
            'recent_results_title' => 'সাম্প্রতিক ফলাফল',
            'achievements_title' => 'আমাদের অর্জন',
            'avg_pass_rate_label' => 'গড় পাসের হার',
            'gpa5_label' => 'GPA 5 প্রাপ্ত শিক্ষার্থী',
            'aplus_label' => 'A+ গ্রেড প্রাপ্ত',
            'total_exams_label' => 'মোট পরীক্ষা',
        ];
    }
}
