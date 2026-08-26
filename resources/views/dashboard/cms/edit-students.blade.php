@extends('layouts.admin')

@section('title', 'Edit Students Page')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold tracking-tight">Edit Students Page</h2>
            <p class="text-muted-foreground">Customize your students page content</p>
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

        <!-- Statistics Section -->
        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>Statistics Section</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                    <div class="space-y-2">
                        <x-ui.label>Statistics Title</x-ui.label>
                        <x-ui.input name="content[stats_title]" :value="$page->getContent('stats_title')" />
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="space-y-2">
                        <x-ui.label>Total Students</x-ui.label>
                        <x-ui.input name="content[total_students]" :value="$page->getContent('total_students')" />
                    </div>
                    <div class="space-y-2">
                        <x-ui.label>Male Students</x-ui.label>
                        <x-ui.input name="content[male_students]" :value="$page->getContent('male_students')" />
                    </div>
                    <div class="space-y-2">
                        <x-ui.label>Female Students</x-ui.label>
                        <x-ui.input name="content[female_students]" :value="$page->getContent('female_students')" />
                    </div>
                    <div class="space-y-2">
                        <x-ui.label>Attendance Rate</x-ui.label>
                        <x-ui.input name="content[attendance_rate]" :value="$page->getContent('attendance_rate')" />
                    </div>
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <!-- Activities Section -->
        <x-ui.card>
             <x-ui.card-header>
                <x-ui.card-title>Activities Section</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content>
                <div class="mb-6">
                    <div class="space-y-2">
                        <x-ui.label>Activities Title</x-ui.label>
                        <x-ui.input name="content[activities_title]" :value="$page->getContent('activities_title')" />
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-2">
                        <x-ui.label>Activity 1 Title</x-ui.label>
                        <x-ui.input name="content[activity1_title]" :value="$page->getContent('activity1_title')" />
                        <x-ui.textarea name="content[activity1_text]" class="mt-2" :value="$page->getContent('activity1_text')" rows="2" />
                    </div>
                    <div class="space-y-2">
                        <x-ui.label>Activity 2 Title</x-ui.label>
                        <x-ui.input name="content[activity2_title]" :value="$page->getContent('activity2_title')" />
                         <x-ui.textarea name="content[activity2_text]" class="mt-2" :value="$page->getContent('activity2_text')" rows="2" />
                    </div>
                     <div class="space-y-2">
                        <x-ui.label>Activity 3 Title</x-ui.label>
                        <x-ui.input name="content[activity3_title]" :value="$page->getContent('activity3_title')" />
                        <x-ui.textarea name="content[activity3_text]" class="mt-2" :value="$page->getContent('activity3_text')" rows="2" />
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
