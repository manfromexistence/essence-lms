@extends('layouts.admin')

@section('title', 'Edit Results Page')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold tracking-tight">Edit Results Page</h2>
            <p class="text-muted-foreground">Customize your results page content</p>
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

        <!-- Page Header -->
        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>Page Header</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <x-ui.label>Page Title</x-ui.label>
                        <x-ui.input name="content[page_title]" :value="$page->getContent('page_title')" />
                    </div>
                    <div class="space-y-2">
                        <x-ui.label>Page Subtitle</x-ui.label>
                        <x-ui.input name="content[page_subtitle]" :value="$page->getContent('page_subtitle')" />
                    </div>
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <!-- Search Form Section -->
        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>Search Form</x-ui.card-title>
            </x-ui.card-header>
             <x-ui.card-content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                     <div class="space-y-2">
                        <x-ui.label>Search Title</x-ui.label>
                        <x-ui.input name="content[search_title]" :value="$page->getContent('search_title')" />
                    </div>
                     <div class="space-y-2">
                        <x-ui.label>Exam Type Label</x-ui.label>
                        <x-ui.input name="content[exam_type_label]" :value="$page->getContent('exam_type_label')" />
                    </div>
                     <div class="space-y-2">
                        <x-ui.label>Roll Number Label</x-ui.label>
                        <x-ui.input name="content[roll_label]" :value="$page->getContent('roll_label')" />
                    </div>
                     <div class="space-y-2">
                        <x-ui.label>Roll Placeholder</x-ui.label>
                        <x-ui.input name="content[roll_placeholder]" :value="$page->getContent('roll_placeholder')" />
                    </div>
                     <div class="space-y-2">
                        <x-ui.label>Registration Label</x-ui.label>
                        <x-ui.input name="content[reg_label]" :value="$page->getContent('reg_label')" />
                    </div>
                     <div class="space-y-2">
                        <x-ui.label>Search Button Text</x-ui.label>
                        <x-ui.input name="content[search_button]" :value="$page->getContent('search_button')" />
                    </div>
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <!-- Recent Results Section -->
        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>Recent Results Section</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content>
                <div class="space-y-2">
                    <x-ui.label>Recent Results Title</x-ui.label>
                    <x-ui.input name="content[recent_results_title]" :value="$page->getContent('recent_results_title')" />
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <!-- Achievements Section -->
        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>Achievements Section</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content>
                <div class="mb-4">
                     <div class="space-y-2">
                        <x-ui.label>Achievements Title</x-ui.label>
                        <x-ui.input name="content[achievements_title]" :value="$page->getContent('achievements_title')" />
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                         <div class="space-y-2">
                            <x-ui.label>Avg Pass Rate Label</x-ui.label>
                            <x-ui.input name="content[avg_pass_rate_label]" placeholder="Label" :value="$page->getContent('avg_pass_rate_label')" />
                        </div>
                    </div>
                    <div>
                        <div class="space-y-2">
                            <x-ui.label>GPA 5 Label</x-ui.label>
                             <x-ui.input name="content[gpa5_label]" placeholder="Label" :value="$page->getContent('gpa5_label')" />
                        </div>
                    </div>
                    <div>
                        <div class="space-y-2">
                            <x-ui.label>A+ Label</x-ui.label>
                            <x-ui.input name="content[aplus_label]" placeholder="Label" :value="$page->getContent('aplus_label')" />
                        </div>
                    </div>
                    <div>
                         <div class="space-y-2">
                            <x-ui.label>Total Exams Label</x-ui.label>
                            <x-ui.input name="content[total_exams_label]" placeholder="Label" :value="$page->getContent('total_exams_label')" />
                        </div>
                    </div>
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <!-- Meta & Settings -->
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
