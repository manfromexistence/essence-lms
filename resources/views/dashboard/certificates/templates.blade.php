@extends('layouts.admin')

@section('title', 'Certificate Templates')
@section('page-title', 'Certificate Templates')
@section('page-description', 'Create and manage certificate templates')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Create Template Form -->
        <div class="lg:col-span-1">
            <x-ui.card>
                <x-ui.card-header>
                    <x-ui.card-title>Create Template</x-ui.card-title>
                    <x-ui.card-description>Upload background, logo and signature</x-ui.card-description>
                </x-ui.card-header>
                <x-ui.card-content>
                    <form action="{{ route('dashboard.certificates.templates.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <x-ui.label for="name">Template Name</x-ui.label>
                            <x-ui.input type="text" name="name" id="name" required placeholder="e.g. Course Completion" />
                        </div>
                        <div>
                            <x-ui.label for="type">Type</x-ui.label>
                            <x-ui.select name="type" id="type">
                                <option value="course_completion">Course Completion</option>
                                <option value="achievement">Achievement</option>
                                <option value="participation">Participation</option>
                            </x-ui.select>
                        </div>
                        <div>
                            <x-ui.label for="background_image">Background Image</x-ui.label>
                            <input type="file" name="background_image" id="background_image" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-bd-green file:text-white hover:file:bg-bd-green-dark">
                        </div>
                        <div>
                            <x-ui.label for="logo_image">Logo Image</x-ui.label>
                            <input type="file" name="logo_image" id="logo_image" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-bd-green file:text-white hover:file:bg-bd-green-dark">
                        </div>
                        <div>
                            <x-ui.label for="signature_image">Signature Image</x-ui.label>
                            <input type="file" name="signature_image" id="signature_image" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-bd-green file:text-white hover:file:bg-bd-green-dark">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <x-ui.label for="width">Width (px)</x-ui.label>
                                <x-ui.input type="number" name="width" id="width" value="1200" min="600" max="3000" />
                            </div>
                            <div>
                                <x-ui.label for="height">Height (px)</x-ui.label>
                                <x-ui.input type="number" name="height" id="height" value="900" min="400" max="2000" />
                            </div>
                        </div>
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" name="is_default" value="1" class="h-4 w-4 rounded border-gray-300 accent-primary">
                            <span class="text-sm text-gray-700">Set as default template</span>
                        </label>
                        <x-ui.button type="submit" class="w-full">Create Template</x-ui.button>
                    </form>
                </x-ui.card-content>
            </x-ui.card>
        </div>

        <!-- Templates List -->
        <div class="lg:col-span-2 space-y-4">
            @forelse($templates as $template)
                <a href="{{ route('dashboard.certificates.templates.edit', $template) }}"
                   class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex items-start gap-5 hover:border-bd-green hover:shadow-md transition group">
                    <div class="w-44 h-32 rounded-lg overflow-hidden bg-gray-100 shrink-0 border border-gray-200 relative">
                        @if($template->background_image)
                            <img src="{{ asset('storage/' . $template->background_image) }}" class="absolute inset-0 w-full h-full object-cover" style="opacity: 0.6;">
                        @endif
                        {{-- Live preview of the layout, scaled down --}}
                        <div class="absolute inset-0 overflow-hidden" style="transform: scale(0.17); transform-origin: 0 0; width: {{ $template->width ?? 1200 }}px; height: {{ $template->height ?? 900 }}px;">
                            @include('dashboard.certificates.partials.render-elements', ['template' => $template])
                        </div>
                        <div class="absolute inset-0 flex items-center justify-center bg-black/0 group-hover:bg-black/30 transition">
                            <span class="opacity-0 group-hover:opacity-100 text-white text-xs font-bold bg-bd-green px-3 py-1.5 rounded-lg"><i class="fa-solid fa-pen mr-1"></i> Design</span>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="font-semibold text-gray-900 group-hover:text-bd-green">{{ $template->name }}</h3>
                            @if($template->is_default)
                                <span class="inline-flex items-center rounded-full bg-primary px-2.5 py-0.5 text-xs font-bold text-primary-foreground">Default</span>
                            @endif
                            @if(!$template->is_active)
                                <span class="inline-flex items-center rounded-full bg-gray-200 px-2.5 py-0.5 text-xs font-medium text-gray-600">Inactive</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-500 mt-1">Type: {{ str_replace('_', ' ', ucfirst($template->type)) }} • {{ $template->width }}×{{ $template->height }}px</p>
                        <p class="text-xs text-gray-400 mt-1">{{ $template->certificates()->count() }} certificate(s) issued</p>
                        <div class="flex flex-wrap gap-3 mt-3" onclick="event.stopPropagation(); event.preventDefault();">
                            @if(!$template->is_default)
                                <form action="{{ route('dashboard.certificates.templates.default', $template) }}" method="POST">@csrf
                                    <button type="submit" class="text-xs font-semibold text-primary hover:underline">Set as Default</button>
                                </form>
                            @endif
                            <button type="button" onclick="openEditModal({{ $template->id }})" class="text-xs font-semibold text-gray-600 hover:underline">Settings</button>
                            <form action="{{ route('dashboard.certificates.templates.destroy', $template) }}" method="POST" onsubmit="return confirm('Delete this template?')">@csrf @method('DELETE')
                                <button type="submit" class="text-xs font-semibold text-red-600 hover:underline">Delete</button>
                            </form>
                        </div>
                    </div>
                </a>
            @empty
                <div class="bg-white rounded-xl border border-dashed border-gray-300 p-12 text-center text-gray-500">
                    <i class="fa-solid fa-certificate text-4xl text-gray-300 mb-3"></i>
                    <p>No templates yet. Create your first certificate template.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editTemplateModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75" onclick="closeEditModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900">Edit Template</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form method="POST" id="editTemplateForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    <div>
                        <x-ui.label for="edit_name">Template Name</x-ui.label>
                        <x-ui.input type="text" name="name" id="edit_name" required />
                    </div>
                    <div>
                        <x-ui.label for="edit_type">Type</x-ui.label>
                        <select name="type" id="edit_type" class="w-full rounded-xl border border-gray-300 px-4 py-2.5">
                            <option value="course_completion">Course Completion</option>
                            <option value="achievement">Achievement</option>
                            <option value="participation">Participation</option>
                        </select>
                    </div>
                    <div>
                        <x-ui.label for="edit_background">Background Image (optional)</x-ui.label>
                        <input type="file" name="background_image" id="edit_background" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-bd-green file:text-white">
                    </div>
                    <div>
                        <x-ui.label for="edit_logo">Logo Image (optional)</x-ui.label>
                        <input type="file" name="logo_image" id="edit_logo" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-bd-green file:text-white">
                    </div>
                    <div>
                        <x-ui.label for="edit_signature">Signature Image (optional)</x-ui.label>
                        <input type="file" name="signature_image" id="edit_signature" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-bd-green file:text-white">
                    </div>
                    <div class="flex items-center space-x-4">
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" name="is_active" value="1" id="edit_active" class="h-4 w-4 rounded border-gray-300 accent-primary">
                            <span class="text-sm text-gray-700">Active</span>
                        </label>
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" name="is_default" value="1" id="edit_default" class="h-4 w-4 rounded border-gray-300 accent-primary">
                            <span class="text-sm text-gray-700">Default</span>
                        </label>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" onclick="closeEditModal()" class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white hover:opacity-90">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@php
    $templatesJson = $templates->map(function ($t) {
        return [
            'id' => $t->id,
            'name' => $t->name,
            'type' => $t->type,
            'is_active' => $t->is_active,
            'is_default' => $t->is_default,
        ];
    })->values();
@endphp

@push('scripts')
<script>
    const templates = @json($templatesJson);

    function openEditModal(id) {
        const t = templates.find(x => x.id === id);
        if (!t) return;
        document.getElementById('edit_name').value = t.name;
        document.getElementById('edit_type').value = t.type;
        document.getElementById('edit_active').checked = !!t.is_active;
        document.getElementById('edit_default').checked = !!t.is_default;
        document.getElementById('editTemplateForm').action = `/dashboard/certificates/templates/${id}`;
        document.getElementById('editTemplateModal').classList.remove('hidden');
    }
    function closeEditModal() {
        document.getElementById('editTemplateModal').classList.add('hidden');
    }
</script>
@endpush
@endsection
