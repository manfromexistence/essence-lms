@extends('layouts.admin')

@section('title', 'Edit About Page')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold tracking-tight">Edit About Page</h2>
            <p class="text-muted-foreground">Customize your about page content</p>
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
                <div class="space-y-2">
                    <x-ui.label>Page Title</x-ui.label>
                    <x-ui.input name="content[page_title]" :value="$page->getContent('page_title')" />
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <!-- About Section -->
        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>About Section</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content>
                <div class="space-y-4">
                    <div class="space-y-2">
                        <x-ui.label>About Image URL</x-ui.label>
                        <x-ui.image-input name="content[about_image]" :value="$page->getContent('about_image')" placeholder="/images/..." />
                    </div>
                    <div class="space-y-2">
                        <x-ui.label>About Title</x-ui.label>
                         <x-ui.input name="content[about_title]" :value="$page->getContent('about_title')" />
                    </div>
                    <div class="space-y-2">
                        <x-ui.label>About Text</x-ui.label>
                        <x-ui.textarea name="content[about_text]" :value="$page->getContent('about_text')" rows="4" />
                    </div>
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <!-- Statistics -->
        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>Statistics</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div class="space-y-2">
                        <x-ui.label>Students</x-ui.label>
                        <x-ui.input name="content[stats_students]" :value="$page->getContent('stats_students')" />
                    </div>
                    <div class="space-y-2">
                        <x-ui.label>Teachers</x-ui.label>
                        <x-ui.input name="content[stats_teachers]" :value="$page->getContent('stats_teachers')" />
                    </div>
                    <div class="space-y-2">
                         <x-ui.label>Staff</x-ui.label>
                        <x-ui.input name="content[stats_staff]" :value="$page->getContent('stats_staff')" />
                    </div>
                    <div class="space-y-2">
                         <x-ui.label>Rooms</x-ui.label>
                        <x-ui.input name="content[stats_rooms]" :value="$page->getContent('stats_rooms')" />
                    </div>
                    <div class="space-y-2">
                        <x-ui.label>Buildings</x-ui.label>
                        <x-ui.input name="content[stats_buildings]" :value="$page->getContent('stats_buildings')" />
                    </div>
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <!-- Mission -->
        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>Mission</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content>
                <div class="space-y-4">
                     <div class="space-y-2">
                        <x-ui.label>Mission Title</x-ui.label>
                        <x-ui.input name="content[mission_title]" :value="$page->getContent('mission_title')" />
                    </div>
                     <div class="space-y-2">
                        <x-ui.label>Mission Text</x-ui.label>
                        <x-ui.textarea name="content[mission_text]" :value="$page->getContent('mission_text')" rows="4" />
                    </div>
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <!-- Vision -->
        <x-ui.card>
             <x-ui.card-header>
                <x-ui.card-title>Vision</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content>
                <div class="space-y-4">
                    <div class="space-y-2">
                        <x-ui.label>Vision Image URL</x-ui.label>
                        <x-ui.image-input name="content[vision_image]" :value="$page->getContent('vision_image')" placeholder="/images/..." />
                    </div>
                    <div class="space-y-2">
                        <x-ui.label>Vision Title</x-ui.label>
                        <x-ui.input name="content[vision_title]" :value="$page->getContent('vision_title')" />
                    </div>
                    <div class="space-y-2">
                        <x-ui.label>Vision Text</x-ui.label>
                         <x-ui.textarea name="content[vision_text]" :value="$page->getContent('vision_text')" rows="4" />
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
