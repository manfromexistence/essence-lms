<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dhaka IT Institute - Practical IT & Freelancing Training</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        body {
            font-family: 'Inter', 'Noto Sans Bengali', sans-serif;
        }
        
        .bengali-text {
            font-family: 'Noto Sans Bengali', sans-serif;
        }
        
        /* Bangladesh Flag Colors — use configured primary so all greens stay consistent */
        .bg-bd-green {
            background-color: var(--color-primary);
        }
        
        .bg-bd-red {
            background-color: #F42A41;
        }
        
        .text-bd-green {
            color: var(--color-primary);
        }
        
        .text-bd-red {
            color: #F42A41;
        }
        
        /* Gradient inspired by Bangladesh landscape — reuse primary for the green stop */
        .hero-gradient {
            background: linear-gradient(135deg, var(--color-primary) 0%, #00876B 50%, #FFB800 100%);
        }
        
        /* Subtle pattern background (inject primary hex into the data-uri; keep a safe fallback) */
        .pattern-bg {
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23{{ ltrim($primaryColor ?? '#006A4E', '#') }}' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        
        /* Animation */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .float-animation {
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.8s ease-out forwards;
        }
        
        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.2s; }
        .delay-300 { animation-delay: 0.3s; }
        .delay-400 { animation-delay: 0.4s; }
    </style>
</head>
<body class="bg-gradient-to-br from-emerald-50 via-white to-amber-50 pattern-bg">
    
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-lg border-b border-emerald-100 shadow-sm">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16 sm:h-20">
                <!-- Logo -->
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-bd-green rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-bd-green">Dhaka IT Institute</h1>
                        <p class="text-xs sm:text-sm text-emerald-600 bengali-text hidden sm:block">শেখার নতুন যাত্রা</p>
                    </div>
                </div>
                
                <!-- Navigation Links -->
                <div class="hidden md:flex items-center space-x-6 lg:space-x-8">
                    <a href="#features" class="text-gray-700 hover:text-bd-green font-medium transition-colors">বৈশিষ্ট্য</a>
                    <a href="#courses" class="text-gray-700 hover:text-bd-green font-medium transition-colors">কোর্স</a>
                    <a href="#about" class="text-gray-700 hover:text-bd-green font-medium transition-colors">সম্পর্কে</a>
                    <a href="{{ route('login') }}" class="text-gray-700 hover:text-bd-green font-medium transition-colors">লগইন</a>
                    <a href="#contact" class="px-6 py-2.5 bg-bd-green text-white rounded-lg hover:bg-emerald-700 transition-all font-medium shadow-md hover:shadow-lg">রেজিস্টার করুন</a>
                </div>
                
                <!-- Mobile Menu Button -->
                <button class="md:hidden p-2 text-gray-700 hover:text-bd-green">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-24 sm:pt-32 pb-16 sm:pb-20 px-4 sm:px-6 lg:px-8 overflow-hidden">
        <div class="container mx-auto">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Left Content -->
                <div class="space-y-6 sm:space-y-8">
                    <div class="inline-flex items-center bg-amber-100 text-amber-800 px-4 py-2 rounded-full text-sm font-medium opacity-0 fade-in-up">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        বাংলাদেশের সেরা অনলাইন লার্নিং প্ল্যাটফর্ম
                    </div>
                    
                    <h2 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 leading-tight bengali-text opacity-0 fade-in-up delay-100">
                        শেখার নতুন যাত্রা
                        <span class="block text-bd-green mt-2">আপনার স্বপ্ন পূরণে</span>
                    </h2>
                    
                    <p class="text-lg sm:text-xl text-gray-600 leading-relaxed bengali-text opacity-0 fade-in-up delay-200">
                        বাংলাদেশের শিক্ষার্থীদের জন্য বিশেষভাবে ডিজাইন করা অনলাইন লার্নিং প্ল্যাটফর্ম। 
                        বিশ্বমানের শিক্ষা এখন আপনার হাতের মুঠোয়।
                    </p>
                    
                    <div class="flex flex-col sm:flex-row gap-4 opacity-0 fade-in-up delay-300">
                        <a href="#courses" class="px-8 py-4 bg-bd-green text-white rounded-lg hover:bg-emerald-700 transition-all font-semibold text-lg shadow-lg hover:shadow-xl hover:scale-105 transform text-center">
                            বিনামূল্যে শুরু করুন
                        </a>
                        <a href="#courses" class="px-8 py-4 bg-white text-bd-green border-2 border-bd-green rounded-lg hover:bg-emerald-50 transition-all font-semibold text-lg shadow-md text-center">
                            কোর্স দেখুন
                        </a>
                    </div>
                    
                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-4 sm:gap-6 pt-8 opacity-0 fade-in-up delay-400">
                        <div class="text-center">
                            <div class="text-2xl sm:text-3xl font-bold text-bd-green">প্র্যাকটিক্যাল</div>
                            <div class="text-sm sm:text-base text-gray-600 bengali-text">শিক্ষার্থী</div>
                        </div>
                        <div class="text-center border-x border-gray-200">
                            <div class="text-2xl sm:text-3xl font-bold text-bd-green">অনলাইন</div>
                            <div class="text-sm sm:text-base text-gray-600 bengali-text">কোর্স</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl sm:text-3xl font-bold text-bd-green">অফলাইন</div>
                            <div class="text-sm sm:text-base text-gray-600 bengali-text">শিক্ষক</div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Image/Illustration -->
                <div class="relative opacity-0 fade-in-up delay-200">
                    <!-- Decorative Elements -->
                    <div class="absolute top-0 right-0 w-72 h-72 bg-bd-green opacity-10 rounded-full blur-3xl"></div>
                    <div class="absolute bottom-0 left-0 w-72 h-72 bg-amber-400 opacity-10 rounded-full blur-3xl"></div>
                    
                    <!-- Main Illustration Container -->
                    <div class="relative z-10 float-animation">
                        <div class="bg-white rounded-3xl shadow-2xl p-8 sm:p-12">
                            <!-- Book/Education Icon -->
                            <div class="w-full aspect-square bg-gradient-to-br from-emerald-100 to-amber-100 rounded-2xl flex items-center justify-center">
                                <svg class="w-32 h-32 sm:w-48 sm:h-48 text-bd-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14l9-5-9-5-9 5 9 5z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-16 sm:py-20 px-4 sm:px-6 lg:px-8 bg-white">
        <div class="container mx-auto">
            <div class="text-center mb-12 sm:mb-16">
                <h3 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-4 bengali-text">কেন Dhaka IT Institute?</h3>
                <p class="text-lg sm:text-xl text-gray-600 max-w-2xl mx-auto bengali-text">
                    আধুনিক প্রযুক্তি এবং বাংলাদেশী সংস্কৃতির নিখুঁত সমন্বয়
                </p>
            </div>
            
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                <!-- Feature 1 -->
                <div class="bg-gradient-to-br from-emerald-50 to-white p-6 sm:p-8 rounded-2xl shadow-lg hover:shadow-xl transition-all hover:-translate-y-2">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-bd-green rounded-xl flex items-center justify-center mb-4 sm:mb-6">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                        </svg>
                    </div>
                    <h4 class="text-xl sm:text-2xl font-bold text-gray-900 mb-3 bengali-text">বাংলায় শিখুন</h4>
                    <p class="text-gray-600 bengali-text">সম্পূর্ণ বাংলা ভাষায় কোর্স কন্টেন্ট এবং সহজ বোধগম্য শিক্ষাদান পদ্ধতি।</p>
                </div>
                
                <!-- Feature 2 -->
                <div class="bg-gradient-to-br from-amber-50 to-white p-6 sm:p-8 rounded-2xl shadow-lg hover:shadow-xl transition-all hover:-translate-y-2">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-bd-red rounded-xl flex items-center justify-center mb-4 sm:mb-6">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <h4 class="text-xl sm:text-2xl font-bold text-gray-900 mb-3 bengali-text">ইন্টারেক্টিভ শিক্ষা</h4>
                    <p class="text-gray-600 bengali-text">ভিডিও, কুইজ, অ্যাসাইনমেন্ট এবং লাইভ ক্লাসের মাধ্যমে সম্পূর্ণ ইন্টারেক্টিভ অভিজ্ঞতা।</p>
                </div>
                
                <!-- Feature 3 -->
                <div class="bg-gradient-to-br from-emerald-50 to-white p-6 sm:p-8 rounded-2xl shadow-lg hover:shadow-xl transition-all hover:-translate-y-2">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-bd-green rounded-xl flex items-center justify-center mb-4 sm:mb-6">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h4 class="text-xl sm:text-2xl font-bold text-gray-900 mb-3 bengali-text">নিজের গতিতে</h4>
                    <p class="text-gray-600 bengali-text">যেকোনো সময়, যেকোনো জায়গা থেকে আপনার সুবিধামতো সময়ে শিখুন।</p>
                </div>
                
                <!-- Feature 4 -->
                <div class="bg-gradient-to-br from-amber-50 to-white p-6 sm:p-8 rounded-2xl shadow-lg hover:shadow-xl transition-all hover:-translate-y-2">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-bd-red rounded-xl flex items-center justify-center mb-4 sm:mb-6">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                    </div>
                    <h4 class="text-xl sm:text-2xl font-bold text-gray-900 mb-3 bengali-text">সার্টিফিকেট</h4>
                    <p class="text-gray-600 bengali-text">কোর্স সম্পন্ন করে পান স্বীকৃত সার্টিফিকেট যা আপনার ক্যারিয়ারে সহায়ক।</p>
                </div>
                
                <!-- Feature 5 -->
                <div class="bg-gradient-to-br from-emerald-50 to-white p-6 sm:p-8 rounded-2xl shadow-lg hover:shadow-xl transition-all hover:-translate-y-2">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-bd-green rounded-xl flex items-center justify-center mb-4 sm:mb-6">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <h4 class="text-xl sm:text-2xl font-bold text-gray-900 mb-3 bengali-text">বিশেষজ্ঞ শিক্ষক</h4>
                    <p class="text-gray-600 bengali-text">দেশের সেরা এবং অভিজ্ঞ শিক্ষকদের কাছ থেকে সরাসরি শিখুন।</p>
                </div>
                
                <!-- Feature 6 -->
                <div class="bg-gradient-to-br from-amber-50 to-white p-6 sm:p-8 rounded-2xl shadow-lg hover:shadow-xl transition-all hover:-translate-y-2">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-bd-red rounded-xl flex items-center justify-center mb-4 sm:mb-6">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
                        </svg>
                    </div>
                    <h4 class="text-xl sm:text-2xl font-bold text-gray-900 mb-3 bengali-text">কমিউনিটি সাপোর্ট</h4>
                    <p class="text-gray-600 bengali-text">হাজারো শিক্ষার্থীর সাথে যুক্ত হয়ে একসাথে শিখুন এবং সাহায্য পান।</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 sm:py-20 px-4 sm:px-6 lg:px-8 hero-gradient">
        <div class="container mx-auto text-center">
            <h3 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-6 bengali-text">আজই শুরু করুন আপনার লার্নিং জার্নি</h3>
            <p class="text-lg sm:text-xl text-white/90 mb-8 max-w-2xl mx-auto bengali-text">
                বিনামূল্যে রেজিস্টার করুন এবং শুরু করুন আপনার স্বপ্ন পূরণের যাত্রা
            </p>
            <a href="#courses" class="inline-block px-10 py-4 bg-white text-bd-green rounded-lg hover:bg-gray-100 transition-all font-bold text-lg shadow-xl hover:shadow-2xl hover:scale-105 transform bengali-text">
                এখনই যোগ দিন - বিনামূল্যে
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12 px-4 sm:px-6 lg:px-8">
        <div class="container mx-auto">
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">
                <div>
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-10 h-10 bg-bd-green rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <h5 class="text-xl font-bold">Dhaka IT Institute</h5>
                    </div>
                    <p class="text-gray-400 bengali-text">বাংলাদেশের শিক্ষার্থীদের জন্য সেরা অনলাইন লার্নিং প্ল্যাটফর্ম</p>
                </div>
                
                <div>
                    <h6 class="font-bold mb-4 bengali-text">দ্রুত লিংক</h6>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors bengali-text">সম্পর্কে</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors bengali-text">কোর্স</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors bengali-text">শিক্ষক</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors bengali-text">যোগাযোগ</a></li>
                    </ul>
                </div>
                
                <div>
                    <h6 class="font-bold mb-4 bengali-text">সহায়তা</h6>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors bengali-text">সাহায্য কেন্দ্র</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors bengali-text">গোপনীয়তা নীতি</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors bengali-text">শর্তাবলী</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors bengali-text">FAQ</a></li>
                    </ul>
                </div>
                
                <div>
                    <h6 class="font-bold mb-4 bengali-text">যোগাযোগ করুন</h6>
                    <ul class="space-y-2 text-gray-400">
                        <li>ইমেইল: support@alphalms.com</li>
                        <li>ফোন: +880 1XXX-XXXXXX</li>
                        <li class="bengali-text">ঠিকানা: ঢাকা, বাংলাদেশ</li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 pt-8 text-center text-gray-400">
                <p class="bengali-text">&copy; {{ date('Y') }} Dhaka IT Institute. সর্বস্বত্ব সংরক্ষিত।</p>
            </div>
        </div>
    </footer>

</body>
</html>
