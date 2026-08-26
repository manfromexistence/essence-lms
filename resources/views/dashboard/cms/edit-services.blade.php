@extends('layouts.admin')

@section('title', 'Edit Services Page')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold tracking-tight">Edit Services Page</h2>
            <p class="text-muted-foreground">Customize your services page content</p>
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

        <!-- Call To Action Section -->
        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>Call To Action Section</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content>
                <div class="space-y-4">
                    <div class="space-y-2">
                        <x-ui.label>CTA Title</x-ui.label>
                        <x-ui.input name="content[cta_title]" :value="$page->getContent('cta_title')" />
                    </div>
                    <div class="space-y-2">
                        <x-ui.label>CTA Text</x-ui.label>
                        <x-ui.textarea name="content[cta_text]" :value="$page->getContent('cta_text')" rows="3" />
                    </div>
                    <div class="space-y-2">
                        <x-ui.label>CTA Button Text</x-ui.label>
                        <x-ui.input name="content[cta_button]" :value="$page->getContent('cta_button')" />
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