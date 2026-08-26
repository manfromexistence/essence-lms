@extends('layouts.admin')

@section('title', 'My Courses')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold tracking-tight">My Courses</h2>
            <p class="text-sm text-muted-foreground">{{ $student ? 'Access your learning resources' : 'View all course materials' }}</p>
        </div>
    </div>

    @if($materials->isEmpty())
    <x-ui.card>
        <x-ui.card-content class="pt-6">
            <div class="text-center py-12">
                <i class="fas fa-book-open text-6xl text-muted-foreground mb-4"></i>
                <h3 class="text-xl font-semibold mb-2">No materials available</h3>
                <p class="text-muted-foreground">Course materials will appear here when uploaded.</p>
            </div>
        </x-ui.card-content>
    </x-ui.card>
    @else
    
    @foreach($materials as $type => $items)
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-800 mb-4 capitalize flex items-center">
            @if($type === 'pdf')
            <i class="fas fa-file-pdf text-red-500 mr-2"></i>
            @elseif($type === 'video')
            <i class="fas fa-video text-blue-500 mr-2"></i>
            @elseif($type === 'link')
            <i class="fas fa-link text-green-500 mr-2"></i>
            @else
            <i class="fas fa-file text-gray-500 mr-2"></i>
            @endif
            {{ ucfirst($type) }} Materials
        </h2>
        
        <x-ui.card>
            <x-ui.card-content class="p-0">
                <ul class="divide-y divide-gray-200">
                    @foreach($items as $material)
                    <li class="px-6 py-4 flex items-center justify-between hover:bg-gray-50">
                        <div class="flex items-center flex-1">
                            <div class="flex-shrink-0">
                                @if($type === 'pdf')
                                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-file-pdf text-red-600"></i>
                                </div>
                                @elseif($type === 'video')
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-play-circle text-blue-600"></i>
                                </div>
                                @else
                                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-file text-gray-600"></i>
                                </div>
                                @endif
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-900">{{ $material->title }}</p>
                                @if($material->description)
                                <p class="text-sm text-gray-500">{{ Str::limit($material->description, 60) }}</p>
                                @endif
                                <p class="text-xs text-gray-400">
                                    @if(!$student && $material->course)
                                    Course: {{ $material->course->name }} • 
                                    @endif
                                    Uploaded {{ $material->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                        <div>
                            @if($student)
                                @if($type === 'link')
                                <x-ui.button variant="outline" size="sm" as="a" href="{{ route('student.materials.download', $material) }}" target="_blank" rel="noopener">
                                    <i class="fas fa-external-link-alt mr-1"></i> Open
                                </x-ui.button>
                                @else
                                <x-ui.button variant="outline" size="sm" as="a" href="{{ route('student.materials.download', $material) }}">
                                    <i class="fas fa-download mr-1"></i> Download
                                </x-ui.button>
                                @endif
                            @else
                            <span class="text-gray-400 text-xs">View Only</span>
                            @endif
                        </div>
                    </li>
                    @endforeach
                </ul>
            </x-ui.card-content>
        </x-ui.card>
    </div>
    @endforeach
    
    @endif
    
    <div class="mt-6">
        @if($student)
        <x-ui.button variant="ghost" as="a" href="{{ route('student.dashboard') }}">
            <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
        </x-ui.button>
        @else
        <x-ui.button variant="ghost" as="a" href="{{ route('dashboard') }}">
            <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
        </x-ui.button>
        @endif
    </div>
</div>
@endsection
