@extends('layouts.admin')

@section('title', 'CMS Pages')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold tracking-tight">CMS Pages</h2>
            <p class="text-muted-foreground">Manage your website pages and content</p>
        </div>
        <x-ui.button as="a" href="{{ route('dashboard.cms.create') }}">
            <i class="fas fa-plus mr-2"></i> Add New Page
        </x-ui.button>
    </div>

    <!-- Quick Edit Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <x-ui.cms-card title="Home Page" description="Edit hero, features, CTA" :route="route('dashboard.cms.home')" color="#006A4E">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </x-ui.cms-card>

        <x-ui.cms-card title="About Page" description="Edit about, mission, vision" :route="route('dashboard.cms.about')" color="#f59e0b">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </x-ui.cms-card>

        <x-ui.cms-card title="Contact Page" description="Edit contact info, map" :route="route('dashboard.cms.contact')" color="#3d59f9">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
        </x-ui.cms-card>

<x-ui.cms-card title="Courses Page" description="Edit courses page text" :route="route('dashboard.cms.courses')" color="#a855f7">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
        </x-ui.cms-card>

        <x-ui.cms-card title="Services Page" description="Edit services page text" :route="route('dashboard.cms.services')" color="#0ea5e9">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </x-ui.cms-card>

        <x-ui.cms-card title="Team Page" description="Edit team page text" :route="route('dashboard.cms.team')" color="#84cc16">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
        </x-ui.cms-card>

        <x-ui.cms-card title="Teachers Page" description="Edit teachers page text" :route="route('dashboard.cms.teachers')" color="#14b8a6">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
        </x-ui.cms-card>

        <x-ui.cms-card title="Students Page" description="Edit students page text" :route="route('dashboard.cms.students')" color="#6366f1">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
        </x-ui.cms-card>

        <x-ui.cms-card title="Results Page" description="Edit results page text" :route="route('dashboard.cms.results')" color="#f43f5e">
             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138z"/>
        </x-ui.cms-card>
    </div>

    <!-- All Pages Table -->
    <x-ui.card>
        <x-ui.card-header>
            <x-ui.card-title>All Pages</x-ui.card-title>
        </x-ui.card-header>
        <x-ui.card-content class="p-0">
            <x-ui.table>
                <x-ui.table-header>
                    <x-ui.table-row>
                        <x-ui.table-head>Title</x-ui.table-head>
                        <x-ui.table-head>Slug</x-ui.table-head>
                        <x-ui.table-head>Status</x-ui.table-head>
                        <x-ui.table-head>Updated</x-ui.table-head>
                        <x-ui.table-head class="text-right">Actions</x-ui.table-head>
                    </x-ui.table-row>
                </x-ui.table-header>
                <x-ui.table-body>
                    @forelse($pages as $page)
                    <x-ui.table-row>
                        <x-ui.table-cell class="font-medium">{{ $page->title }}</x-ui.table-cell>
                        <x-ui.table-cell>
                            <code class="text-xs bg-muted px-1.5 py-0.5 rounded">{{ $page->slug }}</code>
                        </x-ui.table-cell>
                        <x-ui.table-cell>
                            @if($page->is_active)
                                <x-ui.badge variant="secondary" class="bg-emerald-100 text-emerald-800 hover:bg-emerald-200">Active</x-ui.badge>
                            @else
                                <x-ui.badge variant="outline" class="text-muted-foreground">Inactive</x-ui.badge>
                            @endif
                        </x-ui.table-cell>
                        <x-ui.table-cell class="text-sm text-muted-foreground">
                            {{ $page->updated_at->diffForHumans() }}
                        </x-ui.table-cell>
                        <x-ui.table-cell class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <x-ui.button variant="ghost" size="icon" class="h-8 w-8" as="a" href="{{ route('dashboard.cms.edit', $page) }}">
                                    <i class="fas fa-edit text-xs"></i>
                                </x-ui.button>
                                <form action="{{ route('dashboard.cms.destroy', $page) }}" method="POST" class="inline" onsubmit="return confirmDelete(this, 'Are you sure you want to delete this page?');">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button variant="ghost" size="icon" type="submit" class="h-8 w-8 text-destructive hover:bg-destructive/10">
                                        <i class="fas fa-trash text-xs"></i>
                                    </x-ui.button>
                                </form>
                            </div>
                        </x-ui.table-cell>
                    </x-ui.table-row>
                    @empty
                    <x-ui.table-row>
                        <x-ui.table-cell colspan="5" class="text-center py-8 text-muted-foreground">
                            No pages found. Create your first page!
                        </x-ui.table-cell>
                    </x-ui.table-row>
                    @endforelse
                </x-ui.table-body>
            </x-ui.table>
        </x-ui.card-content>
    </x-ui.card>
</div>
@endsection

