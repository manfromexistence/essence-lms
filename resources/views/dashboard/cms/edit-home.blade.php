@extends('layouts.admin')

@section('title', 'Edit Home Page')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold tracking-tight">Edit Home Page</h2>
            <p class="text-muted-foreground">Customize your homepage content</p>
        </div>
        <x-ui.button variant="outline" as="a" href="{{ route('dashboard.cms.index') }}">
            <i class="fas fa-arrow-left mr-2"></i> Back to Pages
        </x-ui.button>
    </div>

    @if(session('success'))
        <x-ui.alert variant="default" class="mb-6 bg-emerald-50 text-emerald-800 border-emerald-200">
            <x-ui.alert-title>Success</x-ui.alert-title>
            <x-ui.alert-description>{{ session('success') }}</x-ui.alert-description>
        </x-ui.alert>
    @endif

    <form action="{{ route('dashboard.cms.update', $page) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Hero Slider Section -->
        @foreach(['slide1', 'slide2', 'slide3'] as $slide)
        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title class="flex items-center gap-2">
                    <i class="fas fa-images text-primary"></i>
                    Hero Slider - {{ ucfirst($slide) }}
                </x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-2">
                        <x-ui.label>Title</x-ui.label>
                        <x-ui.input name="content[{{ $slide }}_title]" :value="$page->getContent($slide . '_title')" />
                    </div>
                    <div class="space-y-2">
                        <x-ui.label>Subtitle</x-ui.label>
                        <x-ui.input name="content[{{ $slide }}_subtitle]" :value="$page->getContent($slide . '_subtitle')" />
                    </div>
                    <div class="space-y-2">
                        <x-ui.label>Image URL</x-ui.label>
                        <x-ui.image-input name="content[{{ $slide }}_image]" :value="$page->getContent($slide . '_image')" />
                    </div>
                </div>
            </x-ui.card-content>
        </x-ui.card>
        @endforeach

        <!-- Banner Section -->
        <x-ui.card>
             <x-ui.card-header>
                <x-ui.card-title>Banner Section</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="space-y-2">
                        <x-ui.label>Banner Image URL</x-ui.label>
                        <x-ui.image-input name="content[banner_image]" :value="$page->getContent('banner_image')" />
                    </div>
                    <div class="space-y-2">
                        <x-ui.label>Button Text</x-ui.label>
                        <x-ui.input name="content[banner_button]" :value="$page->getContent('banner_button')" />
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="space-y-2">
                        <x-ui.label>Title</x-ui.label>
                        <x-ui.input name="content[banner_title]" :value="$page->getContent('banner_title')" />
                    </div>
                    <div class="space-y-2">
                        <x-ui.label>Title Highlight</x-ui.label>
                        <x-ui.input name="content[banner_title_highlight]" :value="$page->getContent('banner_title_highlight')" />
                    </div>
                    <div class="space-y-2">
                         <x-ui.label>Subtitle</x-ui.label>
                        <x-ui.input name="content[banner_subtitle]" :value="$page->getContent('banner_subtitle')" />
                    </div>
                </div>
                <div class="space-y-2">
                    <x-ui.label>Description</x-ui.label>
                    <x-ui.textarea name="content[banner_description]" :value="$page->getContent('banner_description')" rows="2" />
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <!-- Section Titles -->
        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>Section Titles</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content>
                 <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <x-ui.label>Courses Section Title</x-ui.label>
                        <x-ui.input name="content[courses_section_title]" :value="$page->getContent('courses_section_title')" />
                    </div>
                    <div class="space-y-2">
                        <x-ui.label>Courses Section Subtitle</x-ui.label>
                        <x-ui.input name="content[courses_section_subtitle]" :value="$page->getContent('courses_section_subtitle')" />
                    </div>
                    <div class="space-y-2">
                        <x-ui.label>Students Section Title</x-ui.label>
                        <x-ui.input name="content[students_section_title]" :value="$page->getContent('students_section_title')" />
                    </div>
                    <div class="space-y-2">
                        <x-ui.label>Students Section Subtitle</x-ui.label>
                        <x-ui.input name="content[students_section_subtitle]" :value="$page->getContent('students_section_subtitle')" />
                    </div>
                    <div class="space-y-2">
                        <x-ui.label>Student Gallery Title</x-ui.label>
                        <x-ui.input name="content[random_students_title]" :value="$page->getContent('random_students_title')" />
                    </div>
                    <div class="space-y-2">
                        <x-ui.label>Student Gallery Subtitle</x-ui.label>
                        <x-ui.input name="content[random_students_subtitle]" :value="$page->getContent('random_students_subtitle')" />
                    </div>
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <!-- About Section -->
        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>About Section (on Home)</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="space-y-2">
                        <x-ui.label>Image URL</x-ui.label>
                         <x-ui.image-input name="content[about_section_image]" :value="$page->getContent('about_section_image')" />
                    </div>
                    <div class="space-y-2">
                        <x-ui.label>Title</x-ui.label>
                        <x-ui.input name="content[about_section_title]" :value="$page->getContent('about_section_title')" />
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="space-y-2">
                        <x-ui.label>Text Paragraph 1</x-ui.label>
                        <x-ui.textarea name="content[about_section_text1]" :value="$page->getContent('about_section_text1')" rows="2" />
                    </div>
                    <div class="space-y-2">
                        <x-ui.label>Text Paragraph 2</x-ui.label>
                        <x-ui.textarea name="content[about_section_text2]" :value="$page->getContent('about_section_text2')" rows="2" />
                    </div>
                </div>
                <div class="space-y-2">
                    <x-ui.label>Button Text</x-ui.label>
                    <x-ui.input name="content[about_section_button]" :value="$page->getContent('about_section_button')" />
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <!-- Notice Section -->
        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>Notice Board Section</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-2">
                        <x-ui.label>Notice Title</x-ui.label>
                        <x-ui.input name="content[notice_title]" :value="$page->getContent('notice_title')" />
                    </div>
                    <div class="space-y-2">
                        <x-ui.label>Notice 1 Text</x-ui.label>
                        <x-ui.input name="content[notice_1]" :value="$page->getContent('notice_1')" />
                    </div>
                    <div class="space-y-2">
                        <x-ui.label>View All Text</x-ui.label>
                        <x-ui.input name="content[notice_view_all]" :value="$page->getContent('notice_view_all')" />
                    </div>
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <!-- SEO & Settings -->
        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>SEO & Settings</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content>
                 <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                    <div class="space-y-2">
                        <x-ui.label>Meta Title</x-ui.label>
                        <x-ui.input name="meta_title" :value="$page->meta_title" />
                    </div>
                    <div class="space-y-2">
                        <x-ui.label>Meta Description</x-ui.label>
                        <x-ui.textarea name="meta_description" :value="$page->meta_description" rows="2" />
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <x-ui.checkbox name="is_active" id="is_active" value="1" :checked="$page->is_active" />
                    <x-ui.label for="is_active" class="font-normal">Page is active</x-ui.label>
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <input type="hidden" name="title" value="{{ $page->title }}">

        <div class="flex justify-end gap-4">
            <x-ui.button type="button" variant="outline" as="a" href="{{ route('dashboard.cms.index') }}">Cancel</x-ui.button>
            <x-ui.button type="submit">Save Changes</x-ui.button>
        </div>
    </form>
</div>
@endsection
