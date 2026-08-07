@extends('layouts.admin')

@section('title', 'Edit Template: ' . $template->name)
@section('page-title', 'Certificate Template Editor')
@section('page-description', 'Design your certificate — add text, images, colors and user variables')

@section('content')
@php
    $elements = $template->layout_config ?? [];
    $variables = ['institution_name', 'student_name', 'course_name', 'certificate_number', 'verification_code', 'issued_at', 'grade'];
    $scale = 0.55;
@endphp

<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <form id="templateEditorForm" action="{{ route('dashboard.certificates.templates.update', $template) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <input type="hidden" name="layout_config" id="layoutConfigInput">

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <!-- LEFT: Element list + add -->
            <div class="space-y-6">
                <x-ui.card>
                    <x-ui.card-header>
                        <x-ui.card-title>Elements</x-ui.card-title>
                        <x-ui.card-description>Select an element to edit its properties</x-ui.card-description>
                    </x-ui.card-header>
                    <x-ui.card-content>
                        <div id="elementList" class="space-y-2 max-h-80 overflow-y-auto pr-1">
                            @foreach($elements as $i => $el)
                                <div data-index="{{ $i }}" data-type="{{ $el['type'] ?? 'text' }}"
                                     class="element-item flex items-center justify-between p-3 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-50 transition {{ $i === 0 ? 'bg-emerald-50 border-emerald-300' : '' }}"
                                     onclick="selectElement({{ $i }})">
                                    <div class="flex items-center gap-2">
                                        <span class="w-8 h-8 rounded-lg flex items-center justify-center {{ ($el['type'] ?? 'text') === 'text' ? 'bg-blue-100 text-blue-600' : 'bg-purple-100 text-purple-600' }}">
                                            <i class="fa-solid {{ ($el['type'] ?? 'text') === 'text' ? 'fa-font' : 'fa-image' }} text-sm"></i>
                                        </span>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $el['type'] === 'text' ? 'Text: ' . Str::limit(strip_tags($el['content'] ?? ''), 22) : 'Image: ' . ucfirst($el['imageField'] ?? 'logo') }}</p>
                                            <p class="text-xs text-gray-500">x:{{ $el['x'] ?? 50 }} y:{{ $el['y'] ?? 50 }}</p>
                                        </div>
                                    </div>
                                    <button type="button" onclick="removeElement(event, {{ $i }})" class="text-red-400 hover:text-red-600 p-1"><i class="fa-solid fa-trash-can text-sm"></i></button>
                                </div>
                            @endforeach
                        </div>
                        <div class="grid grid-cols-2 gap-2 mt-4">
                            <button type="button" onclick="addElement('text')" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100"><i class="fa-solid fa-font mr-1"></i> Add Text</button>
                            <button type="button" onclick="addElement('image')" class="rounded-lg border border-purple-200 bg-purple-50 px-3 py-2 text-sm font-semibold text-purple-700 hover:bg-purple-100"><i class="fa-solid fa-image mr-1"></i> Add Image</button>
                        </div>
                    </x-ui.card-content>
                </x-ui.card>

                <!-- Variables -->
                <x-ui.card>
                    <x-ui.card-header>
                        <x-ui.card-title>User Variables</x-ui.card-title>
                        <x-ui.card-description>Click to insert a dynamic value into the selected text</x-ui.card-description>
                    </x-ui.card-header>
                    <x-ui.card-content>
                        <div class="flex flex-wrap gap-2">
                            @foreach($variables as $var)
                                <button type="button" onclick="insertVariable('{{ $var }}')"
                                    class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-mono text-gray-700 hover:border-bd-green hover:text-bd-green transition">
                                    {'{ ' . $var . ' }'}
                                </button>
                            @endforeach
                        </div>
                    </x-ui.card-content>
                </x-ui.card>

                <!-- Template settings -->
                <x-ui.card>
                    <x-ui.card-header>
                        <x-ui.card-title>Template Settings</x-ui.card-title>
                    </x-ui.card-header>
                    <x-ui.card-content>
                        <div class="space-y-3">
                            <div>
                                <x-ui.label for="name">Template Name</x-ui.label>
                                <x-ui.input type="text" name="name" id="name" value="{{ $template->name }}" required />
                            </div>
                            <div>
                                <x-ui.label for="type">Type</x-ui.label>
                                <x-ui.select name="type" id="type">
                                    <option value="course_completion" @selected($template->type === 'course_completion')>Course Completion</option>
                                    <option value="achievement" @selected($template->type === 'achievement')>Achievement</option>
                                    <option value="participation" @selected($template->type === 'participation')>Participation</option>
                                </x-ui.select>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <x-ui.label for="width">Width (px)</x-ui.label>
                                    <x-ui.input type="number" name="width" id="width" value="{{ $template->width ?? 1200 }}" min="600" max="3000" />
                                </div>
                                <div>
                                    <x-ui.label for="height">Height (px)</x-ui.label>
                                    <x-ui.input type="number" name="height" id="height" value="{{ $template->height ?? 900 }}" min="400" max="2000" />
                                </div>
                            </div>
                            <div>
                                <x-ui.label for="background_image">Background Image</x-ui.label>
                                <input type="file" name="background_image" id="background_image" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-bd-green file:text-white">
                            </div>
                            <div>
                                <x-ui.label for="logo_image">Logo Image</x-ui.label>
                                <input type="file" name="logo_image" id="logo_image" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-bd-green file:text-white">
                            </div>
                            <div>
                                <x-ui.label for="signature_image">Signature Image</x-ui.label>
                                <input type="file" name="signature_image" id="signature_image" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-bd-green file:text-white">
                            </div>
                            <div class="flex items-center space-x-4">
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" name="is_active" value="1" @checked($template->is_active) class="h-4 w-4 rounded border-gray-300 accent-primary">
                                    <span class="text-sm text-gray-700">Active</span>
                                </label>
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" name="is_default" value="1" @checked($template->is_default) class="h-4 w-4 rounded border-gray-300 accent-primary">
                                    <span class="text-sm text-gray-700">Default</span>
                                </label>
                            </div>
                        </div>
                    </x-ui.card-content>
                </x-ui.card>

                <div class="flex gap-3">
                    <a href="{{ route('dashboard.certificates.templates.index') }}" class="flex-1 rounded-xl border border-gray-200 px-4 py-2.5 text-center text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="flex-1 rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-white hover:opacity-90">Save Template</button>
                </div>
            </div>

            <!-- CENTER: Live preview canvas -->
            <div class="xl:col-span-2 space-y-4">
                <x-ui.card>
                    <x-ui.card-header>
                        <x-ui.card-title>Live Preview</x-ui.card-title>
                        <x-ui.card-description>Sample student: Rafiqul Islam — Full Stack Web Development</x-ui.card-description>
                    </x-ui.card-header>
                    <x-ui.card-content>
                        <div class="bg-gray-200 rounded-xl p-4 flex justify-center overflow-x-auto">
                            <div class="relative bg-white shadow-xl border-4 border-green-800 overflow-hidden"
                                 style="width: {{ ($template->width ?? 1200) * $scale }}px; height: {{ ($template->height ?? 900) * $scale }}px;">
                                @if($template->background_image)
                                    <img src="{{ asset('storage/' . $template->background_image) }}" class="absolute inset-0 w-full h-full object-cover">
                                @endif
                                <div class="absolute inset-0" style="transform: scale({{ $scale }}); transform-origin: 0 0; width: {{ $template->width ?? 1200 }}px; height: {{ $template->height ?? 900 }}px;">
                                    @include('dashboard.certificates.partials.render-elements', ['template' => $template])
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Elements are positioned in template pixels. Click an element in the list to edit.</p>
                    </x-ui.card-content>
                </x-ui.card>

                <!-- Element properties -->
                <x-ui.card>
                    <x-ui.card-header>
                        <x-ui.card-title>Element Properties</x-ui.card-title>
                        <x-ui.card-description id="propHeader">Select an element to edit</x-ui.card-description>
                    </x-ui.card-header>
                    <x-ui.card-content>
                        <div id="textProps" class="hidden">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <div class="col-span-2">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Content / Variable</label>
                                    <input type="text" id="el_content" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Text or {variable}">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Font Size (px)</label>
                                    <input type="number" id="el_fontSize" min="8" max="200" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Letter Spacing</label>
                                    <input type="number" id="el_letterSpacing" min="0" max="50" value="0" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Color</label>
                                    <input type="color" id="el_color" class="h-9 w-full rounded-lg border border-gray-300 cursor-pointer">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Font</label>
                                    <select id="el_fontFamily" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-sm">
                                        <option value="Georgia, serif">Serif (Georgia)</option>
                                        <option value="Arial, sans-serif">Sans (Arial)</option>
                                        <option value="Courier New, monospace">Mono (Courier)</option>
                                        <option value='"Brush Script MT", cursive'>Cursive</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Align</label>
                                    <select id="el_align" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-sm">
                                        <option value="left">Left</option>
                                        <option value="center">Center</option>
                                        <option value="right">Right</option>
                                    </select>
                                </div>
                                <div class="flex items-end gap-3 pb-1">
                                    <label class="flex items-center gap-1.5 text-xs text-gray-600"><input type="checkbox" id="el_bold" class="accent-primary"> Bold</label>
                                    <label class="flex items-center gap-1.5 text-xs text-gray-600"><input type="checkbox" id="el_italic" class="accent-primary"> Italic</label>
                                    <label class="flex items-center gap-1.5 text-xs text-gray-600"><input type="checkbox" id="el_underline" class="accent-primary"> Underline</label>
                                </div>
                            </div>
                        </div>
                        <div id="imageProps" class="hidden">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Image Source</label>
                                    <select id="el_imageField" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-sm">
                                        <option value="logo">Logo</option>
                                        <option value="signature">Signature</option>
                                        <option value="background">Background</option>
                                        <option value="custom">Custom URL</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Width (px)</label>
                                    <input type="number" id="el_width" min="20" max="1000" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Height (px)</label>
                                    <input type="number" id="el_height" min="20" max="600" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Custom Image URL</label>
                                    <input type="text" id="el_imageUrl" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="https://...">
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4 pt-4 border-t border-gray-100">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">X Position</label>
                                <input type="number" id="el_x" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Y Position</label>
                                <input type="number" id="el_y" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Width (box)</label>
                                <input type="number" id="el_boxWidth" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Opacity</label>
                                <input type="range" id="el_opacity" min="0.1" max="1" step="0.05" value="1" class="w-full mt-3 accent-primary">
                            </div>
                        </div>
                    </x-ui.card-content>
                </x-ui.card>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    let elements = @json($elements);
    let selectedIndex = -1;

    function refreshPreview() {
        // Update the input and re-render via the preview container's innerHTML
        const container = document.getElementById('previewElements');
        // We re-render server-side by reloading with a ?preview param is heavy;
        // instead, build a lightweight DOM render here for speed.
        container.innerHTML = '';
        elements.forEach((el, i) => {
            const div = document.createElement('div');
            const style = `position:absolute;left:${el.x||50}px;top:${el.y||50}px;`;
            if (el.type === 'text') {
                let content = (el.content||'')
                    .replace(/\{institution_name\}/g,'Dhaka IT Institute')
                    .replace(/\{student_name\}/g,'Rafiqul Islam')
                    .replace(/\{course_name\}/g,'Full Stack Web Development')
                    .replace(/\{certificate_number\}/g,'DII-202608-00001-001')
                    .replace(/\{verification_code\}/g,'ABC123XYZ789')
                    .replace(/\{issued_at\}/g,'07 Aug 2026')
                    .replace(/\{grade\}/g,'A+');
                div.style.cssText = style + `width:${el.width||700}px;font-size:${el.fontSize||20}px;font-family:${el.fontFamily||'Georgia, serif'};color:${el.color||'#111827'};text-align:${el.align||'center'};` + (el.bold?'font-weight:bold;':'') + (el.italic?'font-style:italic;':'') + (el.underline?'text-decoration:underline;':'') + (el.letterSpacing?`letter-spacing:${el.letterSpacing}px;`:'') + (el.opacity?`opacity:${el.opacity};`:'');
                div.textContent = content;
            } else {
                let src = '';
                if (el.imageField === 'signature') src = @json($template->signature_image ? asset('storage/' . $template->signature_image) : '');
                else if (el.imageField === 'background') src = @json($template->background_image ? asset('storage/' . $template->background_image) : '');
                else if (el.imageField === 'logo') src = @json(($template->logo_image ? asset('storage/' . $template->logo_image) : app(\App\Services\SettingsService::class)->getLogo()));
                else if (el.imageField === 'custom') src = el.imageUrl || '';
                if (src) {
                    const img = document.createElement('img');
                    img.src = src;
                    img.style.cssText = style + `width:${el.width||160}px;height:${el.height||60}px;object-fit:contain;`;
                    div.appendChild(img);
                } else { div.textContent = ''; }
            }
            container.appendChild(div);
        });
    }

    function selectElement(index) {
        selectedIndex = index;
        const el = elements[index];
        if (!el) return;
        document.querySelectorAll('.element-item').forEach((item, i) => {
            item.classList.toggle('bg-emerald-50', i === index);
            item.classList.toggle('border-emerald-300', i === index);
        });
        document.getElementById('propHeader').textContent = 'Editing: ' + (el.type === 'text' ? 'Text element' : 'Image element');

        if (el.type === 'text') {
            document.getElementById('textProps').classList.remove('hidden');
            document.getElementById('imageProps').classList.add('hidden');
            document.getElementById('el_content').value = el.content || '';
            document.getElementById('el_fontSize').value = el.fontSize || 20;
            document.getElementById('el_letterSpacing').value = el.letterSpacing || 0;
            document.getElementById('el_color').value = el.color || '#111827';
            document.getElementById('el_fontFamily').value = el.fontFamily || 'Georgia, serif';
            document.getElementById('el_align').value = el.align || 'center';
            document.getElementById('el_bold').checked = !!el.bold;
            document.getElementById('el_italic').checked = !!el.italic;
            document.getElementById('el_underline').checked = !!el.underline;
        } else {
            document.getElementById('textProps').classList.add('hidden');
            document.getElementById('imageProps').classList.remove('hidden');
            document.getElementById('el_imageField').value = el.imageField || 'logo';
            document.getElementById('el_width').value = el.width || 160;
            document.getElementById('el_height').value = el.height || 60;
            document.getElementById('el_imageUrl').value = el.imageUrl || '';
        }
        document.getElementById('el_x').value = el.x || 50;
        document.getElementById('el_y').value = el.y || 50;
        document.getElementById('el_boxWidth').value = el.width || 700;
        document.getElementById('el_opacity').value = el.opacity ?? 1;
    }

    function addElement(type) {
        const el = type === 'text'
            ? { type: 'text', content: 'New Text', x: 50, y: 50, width: 700, fontSize: 20, fontFamily: 'Georgia, serif', color: '#111827', bold: false, italic: false, align: 'center', letterSpacing: 0, opacity: 1 }
            : { type: 'image', imageField: 'logo', x: 50, y: 50, width: 160, height: 60, opacity: 1 };
        elements.push(el);
        renderElementList();
        refreshPreview();
        selectElement(elements.length - 1);
    }

    function removeElement(e, index) {
        e.stopPropagation();
        elements.splice(index, 1);
        renderElementList();
        refreshPreview();
        if (selectedIndex >= elements.length) selectedIndex = -1;
        if (selectedIndex >= 0) selectElement(selectedIndex);
    }

    function insertVariable(variable) {
        if (selectedIndex < 0 || elements[selectedIndex].type !== 'text') {
            alert('Select a text element first.');
            return;
        }
        const input = document.getElementById('el_content');
        input.value += '{' + variable + '}';
        elements[selectedIndex].content = input.value;
        refreshPreview();
    }

    function renderElementList() {
        const list = document.getElementById('elementList');
        list.innerHTML = '';
        elements.forEach((el, i) => {
            const div = document.createElement('div');
            div.dataset.index = i;
            div.dataset.type = el.type;
            div.className = 'element-item flex items-center justify-between p-3 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-50 transition' + (i === selectedIndex ? ' bg-emerald-50 border-emerald-300' : '');
            div.onclick = () => selectElement(i);
            const label = el.type === 'text' ? 'Text: ' + (el.content || '').substring(0, 22) : 'Image: ' + (el.imageField || 'logo');
            div.innerHTML = `<div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg flex items-center justify-center ${el.type === 'text' ? 'bg-blue-100 text-blue-600' : 'bg-purple-100 text-purple-600'}"><i class="fa-solid ${el.type === 'text' ? 'fa-font' : 'fa-image'} text-sm"></i></span>
                <div><p class="text-sm font-medium text-gray-900">${label}</p><p class="text-xs text-gray-500">x:${el.x||50} y:${el.y||50}</p></div>
            </div>
            <button type="button" onclick="removeElement(event, ${i})" class="text-red-400 hover:text-red-600 p-1"><i class="fa-solid fa-trash-can text-sm"></i></button>`;
            list.appendChild(div);
        });
    }

    // Wire property inputs
    document.addEventListener('DOMContentLoaded', function() {
        const textFields = ['el_content','el_fontSize','el_letterSpacing','el_color','el_fontFamily','el_align','el_bold','el_italic','el_underline'];
        const imgFields = ['el_imageField','el_width','el_height','el_imageUrl'];
        const posFields = ['el_x','el_y','el_boxWidth','el_opacity'];
        const map = {
            el_content:'content', el_fontSize:'fontSize', el_letterSpacing:'letterSpacing', el_color:'color',
            el_fontFamily:'fontFamily', el_align:'align', el_bold:'bold', el_italic:'italic', el_underline:'underline',
            el_imageField:'imageField', el_width:'width', el_height:'height', el_imageUrl:'imageUrl',
            el_x:'x', el_y:'y', el_boxWidth:'width', el_opacity:'opacity'
        };
        const all = [...textFields, ...imgFields, ...posFields];
        all.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            const evt = el.type === 'checkbox' ? 'change' : 'input';
            el.addEventListener(evt, function() {
                if (selectedIndex < 0) return;
                const key = map[id];
                let val = this.value;
                if (this.type === 'checkbox') val = this.checked;
                if (id === 'el_fontSize' || id === 'el_letterSpacing' || id === 'el_width' || id === 'el_height' || id === 'el_x' || id === 'el_y' || id === 'el_boxWidth') val = parseInt(val) || 0;
                if (id === 'el_opacity') val = parseFloat(val);
                elements[selectedIndex][key] = val;
                // boxWidth sets width for text too
                if (id === 'el_boxWidth') elements[selectedIndex].width = val;
                refreshPreview();
            });
        });

        // Preview container for JS rendering
        const previewWrap = document.querySelector('.relative.bg-white.shadow-xl');
        const scaled = document.createElement('div');
        scaled.id = 'previewElements';
        scaled.style.cssText = `position:absolute;inset:0;transform:scale(@json($scale));transform-origin:0 0;width:@json($template->width ?? 1200)px;height:@json($template->height ?? 900)px;`;
        previewWrap.appendChild(scaled);

        refreshPreview();

        // On submit, write elements into hidden input
        document.getElementById('templateEditorForm').addEventListener('submit', function() {
            document.getElementById('layoutConfigInput').value = JSON.stringify(elements);
        });
    });
</script>
@endpush
@endsection
