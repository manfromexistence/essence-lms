@extends('layouts.frontend')

@section('title', ($page ? $page->getContent('page_title', 'কোর্সসমূহ') : 'কোর্সসমূহ') . ' - Dhaka IT Institute')

@push('styles')
    <style>
        .course-card:hover {
            transform: translateY(-5px);
        }
    </style>
@endpush

@section('content')
    <div>
    <section class="hero hero--solid hero--dark">
        <div class="hero-inner max-w-7xl mx-auto text-center">
            <h1 class="text-4xl md:text-5xl font-extrabold text-white mb-6">{{ $page ? $page->getContent('page_title', 'Explore Our Courses') : 'Explore Our Courses' }}</h1>
            <p class="text-xl text-emerald-50 max-w-2xl mx-auto">{{ $page ? $page->getContent('page_subtitle', 'Enhance your skills with our expert-led programs.') : 'Enhance your skills with our expert-led programs.' }}</p>
        </div>
    </section>

    <section class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4">
            <form action="{{ route('courses') }}" method="GET" class="bg-white p-6 rounded-2xl shadow-lg mb-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2">
                        <x-ui.input 
                            type="text" 
                            name="search" 
                            value="{{ request('search') }}" 
                            placeholder="{{ $page ? $page->getContent('search_placeholder', 'কোর্সের নাম লিখুন...') : 'কোর্সের নাম লিখুন...' }}"
                        />
                    </div>
                    <div>
                        <x-ui.select 
                            name="category" 
                            :options="array_merge(['' => ($page ? $page->getContent('all_categories', 'সকল ক্যাটাগরি') : 'সকল ক্যাটাগরি')], array_combine($categories->toArray(), array_map('ucfirst', $categories->toArray())))" 
                            :selected="request('category')"
                        />
                    </div>
                    <div>
                        <x-ui.button type="submit" class="w-full bg-primary text-white font-bold py-3 rounded-xl hover:bg-emerald-700 transition">
                            {{ $page ? $page->getContent('search_button', 'খুঁজুন') : 'খুঁজুন' }}
                        </x-ui.button>
                    </div>
                </div>
            </form>

            <!-- Course Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @php
                    $colors = ['emerald', 'blue', 'purple', 'red', 'indigo', 'pink', 'yellow', 'green'];
                @endphp

                @forelse($courses as $index => $course)
                    @php
                        $color = $colors[$index % count($colors)];
                        $category = $course->category ?? 'general';
                        $price = $course->price > 0 ? '৳' . number_format($course->price, 0) : 'ফ্রি';
                    @endphp
                    <div class="course-card bg-white rounded-xl shadow-md overflow-hidden transition-all cursor-pointer" onclick="openCourseModal({{ $course->id }})">
                        <img src="{{ $course->image_url }}" alt="{{ $course->name }}"
                            loading="lazy" decoding="async"
                            onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1200&q=82';"
                            class="w-full h-48 object-cover">
                        <div class="p-5">
                            <h3 class="text-lg font-bold text-gray-900 mb-2 line-clamp-2">{{ $course->name }}</h3>
                            <p class="text-sm text-gray-500 mb-2 uppercase">{{ $category }}</p>
                            @if($course->class)
                                <p class="text-xs text-gray-400 mb-3">শ্রেণী: {{ $course->class }}</p>
                            @endif
                            <div class="flex items-center justify-between text-sm text-gray-600 mb-3 pb-3 border-b border-gray-100">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    {{ $course->batches->sum(function($batch) { return $batch->students->count(); }) }}
                                </span>
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    {{ $course->videos_count ?? 0 }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xl font-bold text-primary">{{ $price }}</span>
                                <button type="button" class="bg-primary text-white px-4 py-2 rounded-lg text-sm hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-[{{ $primaryColor ?? '#3d59f9' }}] transition">বিস্তারিত</button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">কোনো কোর্স পাওয়া যায়নি</h3>
                        <p class="mt-1 text-sm text-gray-500">অনুগ্রহ করে অন্য ফিল্টার ব্যবহার করুন</p>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($courses->hasPages())
                <div class="mt-8">
                    {{ $courses->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-4 pt-6">
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Course fees, syllabus and batch schedules may change. Please confirm the latest details with Dhaka IT Institute at
            <a class="font-semibold underline" href="tel:+8801682715576">01682715576</a>.
        </div>
    </div>

    </div>
    <!-- Course Details Modal -->
    <div id="courseModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 p-6 flex items-center justify-between z-10">
                <h3 class="text-2xl font-bold text-gray-900" id="modalTitle">Course Details</h3>
                <button onclick="closeCourseModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div id="modalContent" class="p-6">
                <!-- Content will be loaded here -->
                <div class="text-center py-12">
                    <svg class="animate-spin h-8 w-8 text-primary mx-auto" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="mt-4 text-gray-500">Loading...</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const courses = @json($courses->items());

        function openCourseModal(courseId) {
            const modal = document.getElementById('courseModal');
            const course = courses.find(c => c.id === courseId);
            
            if (!course) return;

            document.getElementById('modalTitle').textContent = course.name;
            
            const isAuthenticated = {{ auth()->check() ? 'true' : 'false' }};
            const isStudent = {{ auth()->check() && auth()->user()->hasRole('student') ? 'true' : 'false' }};
            
            let enrollButton = '';
            if (!isAuthenticated) {
                enrollButton = `<a href="{{ route('login') }}" class="w-full bg-primary text-white px-6 py-3 rounded-lg hover:bg-emerald-700 transition font-semibold text-center block">লগইন করে ভর্তি হন</a>`;
            } else if (isStudent) {
                enrollButton = `<button onclick="enrollCourse(${course.id})" class="w-full bg-primary text-white px-6 py-3 rounded-lg hover:bg-emerald-700 transition font-semibold">এখনই ভর্তি হন</button>`;
            } else {
                enrollButton = `<p class="text-center text-gray-500 py-3">শুধুমাত্র শিক্ষার্থীরা কোর্সে ভর্তি হতে পারবেন</p>`;
            }

            const imageUrl = course.image_url || 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1200&q=82';
            const imageHtml = `<img src="${imageUrl}" alt="${course.name}" class="w-full h-64 object-cover rounded-lg mb-6">`;

            const content = `
                ${imageHtml}
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-blue-50 p-4 rounded-lg text-center">
                        <p class="text-sm text-gray-600">মূল্য</p>
                        <p class="text-2xl font-bold text-primary">৳${course.price ? Number(course.price).toLocaleString() : '0'}</p>
                    </div>
                    <div class="bg-purple-50 p-4 rounded-lg text-center">
                        <p class="text-sm text-gray-600">শিক্ষার্থী</p>
                        <p class="text-2xl font-bold text-purple-600">${course.batches?.reduce((sum, batch) => sum + (batch.students?.length || 0), 0) || 0}</p>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg text-center">
                        <p class="text-sm text-gray-600">ভিডিও</p>
                        <p class="text-2xl font-bold text-green-600">${course.videos_count || 0}</p>
                    </div>
                </div>

                <div class="mb-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-3">কোর্স বিবরণ</h4>
                    <p class="text-gray-600 leading-relaxed">${course.description || 'কোর্সের বিস্তারিত বিবরণ শীঘ্রই যুক্ত করা হবে।'}</p>
                </div>

                ${course.class ? `
                <div class="mb-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-3">শ্রেণী</h4>
                    <p class="text-gray-600">Class ${course.class}</p>
                </div>
                ` : ''}

                ${course.duration ? `
                <div class="mb-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-3">সময়কাল</h4>
                    <p class="text-gray-600">${course.duration} ${course.duration_unit || 'months'}</p>
                </div>
                ` : ''}

                <div class="border-t border-gray-200 pt-6">
                    ${enrollButton}
                </div>
            `;

            document.getElementById('modalContent').innerHTML = content;
            modal.classList.remove('hidden');
        }

        function closeCourseModal() {
            document.getElementById('courseModal').classList.add('hidden');
        }

        function enrollCourse(courseId) {
            if (confirm('আপনি কি এই কোর্সে ভর্তি হতে চান?')) {
                // Here you can add AJAX call to enroll
                alert('ভর্তি প্রক্রিয়া শীঘ্রই চালু হবে। অনুগ্রহ করে প্রশাসনের সাথে যোগাযোগ করুন।');
            }
        }

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeCourseModal();
            }
        });

        // Close modal on background click
        document.getElementById('courseModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCourseModal();
            }
        });
    </script>
@endpush

