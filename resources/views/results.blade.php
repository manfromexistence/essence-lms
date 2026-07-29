@extends('layouts.frontend')

@section('title', ($page ? $page->getContent('page_title', 'পরীক্ষার ফলাফল') : 'পরীক্ষার ফলাফল') . ' - Dhaka IT Institute')

@section('content')
    <!-- Page Header -->
    <section class="hero hero--solid hero--dark">
        <div class="hero-inner max-w-7xl mx-auto px-4">
            <h1 class="text-3xl md:text-4xl font-bold text-white text-center mb-2">{{ $page ? $page->getContent('page_title', 'পরীক্ষার ফলাফল') : 'পরীক্ষার ফলাফল' }}</h1>
            <p class="text-white text-center opacity-90">{{ $page ? $page->getContent('page_subtitle', 'আপনার ফলাফল অনুসন্ধান করুন') : 'আপনার ফলাফল অনুসন্ধান করুন' }}</p>
        </div>
    </section>

    <!-- Result Search Section -->
    <section class="py-16 bg-gray-50">
        <div class="max-w-4xl mx-auto px-4">
            <x-ui.card>
                <x-ui.card-header>
                    <x-ui.card-title class="text-center">
                        {{ $page ? $page->getContent('search_title', 'ফলাফল অনুসন্ধান') : 'ফলাফল অনুসন্ধান' }}
                    </x-ui.card-title>
                </x-ui.card-header>
                
                <x-ui.card-content>
                    <form method="GET" action="{{ route('results') }}" class="space-y-6">
                        <!-- Exam Selection -->
                        <div>
                            <x-ui.select 
                                name="exam_id" 
                                label="{{ $page ? $page->getContent('exam_type_label', 'পরীক্ষা নির্বাচন করুন') : 'পরীক্ষা নির্বাচন করুন' }}"
                                :selected="request('exam_id')"
                                required
                            >
                                <option value="">পরীক্ষা নির্বাচন করুন</option>
                                @foreach($exams as $exam)
                                    <option value="{{ $exam->id }}">
                                        {{ $exam->title }} - {{ $exam->batch?->name ?? 'N/A' }}
                                    </option>
                                @endforeach
                            </x-ui.select>
                        </div>

                        <!-- Registration Number -->
                        <div>
                            <x-ui.label for="registration_no" class="block text-gray-700 font-semibold mb-2">
                                {{ $page ? $page->getContent('reg_label', 'রেজিস্ট্রেশন নম্বর') : 'রেজিস্ট্রেশন নম্বর' }}
                            </x-ui.label>
                            <x-ui.input 
                                type="text" 
                                id="registration_no"
                                name="registration_no"
                                placeholder="{{ $page ? $page->getContent('reg_placeholder', 'রেজিস্ট্রেশন নম্বর লিখুন (যেমন: 2026-STU-0001)') : 'রেজিস্ট্রেশন নম্বর লিখুন (যেমন: 2026-STU-0001)' }}"
                                value="{{ request('registration_no') }}"
                                required
                            />
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="w-full bg-primary text-white px-4 py-2 rounded-md hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-[{{ $primaryColor ?? '#3d59f9' }}] transition">
                            {{ $page ? $page->getContent('search_button', 'ফলাফল দেখুন') : 'ফলাফল দেখুন' }}
                        </button>
                    </form>
                </x-ui.card-content>
            </x-ui.card>

            <!-- Search Result Display -->
            @if($searchResult)
                <div class="mt-8">
                    <x-ui.card>
                        <x-ui.card-header>
                            <x-ui.card-title>পরীক্ষার ফলাফল</x-ui.card-title>
                        </x-ui.card-header>
                        <x-ui.card-content>
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-600">শিক্ষার্থীর নাম</p>
                                        <p class="font-semibold">{{ $searchResult->student->user->name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">রেজিস্ট্রেশন নম্বর</p>
                                        <p class="font-semibold">{{ $searchResult->student->registration_no }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">পরীক্ষা</p>
                                        <p class="font-semibold">{{ $searchResult->exam->title }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">বিষয়</p>
                                        <p class="font-semibold">{{ $searchResult->subject_name ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">প্রাপ্ত নম্বর</p>
                                        <p class="font-semibold text-lg">{{ $searchResult->obtained_marks }} / {{ $searchResult->total_marks }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">শতাংশ</p>
                                        <p class="font-semibold text-lg">{{ $searchResult->percentage }}%</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">গ্রেড</p>
                                        <p class="font-semibold text-xl">{{ $searchResult->grade ?? $searchResult->calculateGrade() }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">ফলাফল</p>
                                        <x-ui.badge :class="$searchResult->hasPassed() ? 'bg-green-500 text-white' : 'bg-red-500 text-white'">
                                            {{ $searchResult->hasPassed() ? 'পাস' : 'ফেল' }}
                                        </x-ui.badge>
                                    </div>
                                </div>
                                @if($searchResult->rank)
                                    <div class="pt-4 border-t">
                                        <p class="text-sm text-gray-600">র‍্যাংক</p>
                                        <p class="font-semibold text-2xl text-primary">{{ $searchResult->rank }}</p>
                                    </div>
                                @endif
                            </div>
                        </x-ui.card-content>
                    </x-ui.card>
                </div>
            @elseif(request()->filled('exam_id') && request()->filled('registration_no'))
                <div class="mt-8">
                    <x-ui.alert class="border-yellow-500 bg-yellow-50">
                        <x-ui.alert-title>ফলাফল পাওয়া যায়নি</x-ui.alert-title>
                        <x-ui.alert-description>
                            এই রেজিস্ট্রেশন নম্বর এবং পরীক্ষার জন্য কোনো ফলাফল পাওয়া যায়নি। অনুগ্রহ করে তথ্য যাচাই করুন।
                        </x-ui.alert-description>
                    </x-ui.alert>
                </div>
            @endif
        </div>
    </section>

    <!-- Recent Results Section -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <h2 class="text-3xl font-bold text-center text-gray-800 mb-12">{{ $page ? $page->getContent('recent_results_title', 'সাম্প্রতিক ফলাফল') : 'সাম্প্রতিক ফলাফল' }}</h2>

            @if($recentExams->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach($recentExams as $index => $result)
                        @php
                            $colors = ['green', 'blue', 'purple', 'indigo', 'teal', 'cyan'];
                            $color = $colors[$index % count($colors)];
                        @endphp
                        <x-ui.card class="border-l-4 border-{{ $color }}-500 bg-gradient-to-br from-{{ $color }}-50 to-{{ $color }}-100 hover:shadow-xl transition-shadow">
                            <x-ui.card-header>
                                <div class="flex items-center justify-between">
                                    <x-ui.card-title class="text-lg">{{ $result['title'] }}</x-ui.card-title>
                                    <x-ui.badge class="bg-{{ $color }}-500 text-white">প্রকাশিত</x-ui.badge>
                                </div>
                            </x-ui.card-header>
                            <x-ui.card-content>
                                <div class="space-y-2 text-gray-700">
                                    <p><strong>পাসের হার:</strong> {{ $result['pass_rate'] }}%</p>
                                    <p><strong>GPA 5 (A+):</strong> {{ $result['gpa5_count'] }} জন</p>
                                    <p><strong>GPA 4+ (A):</strong> {{ $result['gpa4_count'] }} জন</p>
                                    <p><strong>মোট শিক্ষার্থী:</strong> {{ $result['total_students'] }} জন</p>
                                </div>
                                <x-ui.button 
                                    type="button"
                                    class="mt-4 w-full bg-{{ $color }}-600 hover:bg-{{ $color }}-700"
                                    onclick="document.getElementById('exam_id').value='{{ $result['id'] }}'; window.scrollTo({top: 0, behavior: 'smooth'});"
                                >
                                    বিস্তারিত দেখুন
                                </x-ui.button>
                            </x-ui.card-content>
                        </x-ui.card>
                    @endforeach
                </div>
            @else
                <x-ui.alert>
                    <x-ui.alert-title>কোনো ফলাফল নেই</x-ui.alert-title>
                    <x-ui.alert-description>
                        এখনও কোনো পরীক্ষার ফলাফল প্রকাশ করা হয়নি।
                    </x-ui.alert-description>
                </x-ui.alert>
            @endif
        </div>
    </section>

    <!-- Achievement Section -->
    @php
        $totalExams = \App\Models\Exam::has('results')->count();
        $totalResults = \App\Models\ExamResult::count();
        $avgPassRate = 0;
        $totalGpa5 = 0;
        $totalGpa4Plus = 0;
        
        if ($totalExams > 0) {
            $examsWithStats = \App\Models\Exam::with('results')->has('results')->get();
            $passRates = [];
            
            foreach ($examsWithStats as $exam) {
                $results = $exam->results;
                $total = $results->count();
                $passed = $results->filter(fn($r) => $r->hasPassed())->count();
                if ($total > 0) {
                    $passRates[] = ($passed / $total) * 100;
                }
                
                $totalGpa5 += $results->filter(fn($r) => $r->percentage >= 90)->count();
                $totalGpa4Plus += $results->filter(fn($r) => $r->percentage >= 80)->count();
            }
            
            if (count($passRates) > 0) {
                $avgPassRate = round(array_sum($passRates) / count($passRates), 0);
            }
        }
    @endphp
    
    <section class="py-16 bg-gradient-to-br from-yellow-50 to-orange-50">
        <div class="max-w-7xl mx-auto px-4">
            <h2 class="text-3xl font-bold text-center text-gray-800 mb-12">{{ $page ? $page->getContent('achievements_title', 'আমাদের অর্জন') : 'আমাদের অর্জন' }}</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <x-ui.card class="text-center hover:shadow-xl transition-shadow">
                    <x-ui.card-content class="pt-6">
                        <div class="text-5xl font-bold text-primary mb-2">{{ $avgPassRate }}%</div>
                        <p class="text-gray-600 font-semibold">{{ $page ? $page->getContent('avg_pass_rate_label', 'গড় পাসের হার') : 'গড় পাসের হার' }}</p>
                    </x-ui.card-content>
                </x-ui.card>
                
                <x-ui.card class="text-center hover:shadow-xl transition-shadow">
                    <x-ui.card-content class="pt-6">
                        <div class="text-5xl font-bold text-primary mb-2">{{ $totalGpa5 }}+</div>
                        <p class="text-gray-600 font-semibold">{{ $page ? $page->getContent('gpa5_label', 'GPA 5 প্রাপ্ত শিক্ষার্থী') : 'GPA 5 প্রাপ্ত শিক্ষার্থী' }}</p>
                    </x-ui.card-content>
                </x-ui.card>
                
                <x-ui.card class="text-center hover:shadow-xl transition-shadow">
                    <x-ui.card-content class="pt-6">
                        <div class="text-5xl font-bold text-primary mb-2">{{ $totalGpa4Plus }}+</div>
                        <p class="text-gray-600 font-semibold">{{ $page ? $page->getContent('aplus_label', 'A+ গ্রেড প্রাপ্ত') : 'A+ গ্রেড প্রাপ্ত' }}</p>
                    </x-ui.card-content>
                </x-ui.card>
                
                <x-ui.card class="text-center hover:shadow-xl transition-shadow">
                    <x-ui.card-content class="pt-6">
                        <div class="text-5xl font-bold text-primary mb-2">{{ $totalExams }}</div>
                        <p class="text-gray-600 font-semibold">{{ $page ? $page->getContent('total_exams_label', 'মোট পরীক্ষা') : 'মোট পরীক্ষা' }}</p>
                    </x-ui.card-content>
                </x-ui.card>
            </div>
        </div>
    </section>
@endsection

