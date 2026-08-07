@extends('layouts.admin')

@section('title', 'Design Template: ' . $template->name)
@section('page-title', 'Certificate Designer')
@section('page-description', 'Drag elements, click to select, edit text and colors — design your certificate')

@section('content')
@php
    $layout = $template->layout_config ?? [];
    $elements = $layout['elements'] ?? (is_array($layout) && isset($layout[0]) ? $layout : []);
    $bgOpacity = $layout['background_opacity'] ?? 0.6;
    $templateW = $template->width ?? 1200;
    $templateH = $template->height ?? 900;

    $studentVariables = [
        'student_name' => 'Student Name', 'student_id' => 'Student ID', 'student_phone' => 'Phone',
        'student_email' => 'Email', 'course_name' => 'Course Name', 'course_code' => 'Course Code',
        'course_duration' => 'Course Duration', 'certificate_number' => 'Certificate No',
        'verification_code' => 'Verify Code', 'issued_at' => 'Issue Date', 'grade' => 'Grade',
        'institution_name' => 'Institute Name', 'institution_phone' => 'Institute Phone',
        'institution_address' => 'Institute Address',
    ];
@endphp

<div class="space-y-4">
    @if(session('success'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <form id="templateEditorForm" action="{{ route('dashboard.certificates.templates.update', $template) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <input type="hidden" name="layout_config" id="layoutConfigInput">

        <div class="grid grid-cols-1 xl:grid-cols-[280px_1fr] gap-4">
            <!-- LEFT: Elements + variables -->
            <div class="space-y-4">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                    <h3 class="font-semibold text-gray-900 mb-3 flex items-center justify-between">
                        Elements
                        <span class="text-xs text-gray-400">{{ count($elements) }}</span>
                    </h3>
                    <div id="elementList" class="space-y-1.5 max-h-64 overflow-y-auto pr-1">
                        @foreach($elements as $i => $el)
                            <div data-index="{{ $i }}" data-type="{{ $el['type'] ?? 'text' }}"
                                 class="element-item flex items-center justify-between p-2 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-50 transition {{ $i === 0 ? 'bg-emerald-50 border-emerald-300' : '' }}"
                                 onclick="selectElement({{ $i }})">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="w-7 h-7 shrink-0 rounded-lg flex items-center justify-center {{ ($el['type'] ?? 'text') === 'text' ? 'bg-blue-100 text-blue-600' : 'bg-purple-100 text-purple-600' }}">
                                        <i class="fa-solid {{ ($el['type'] ?? 'text') === 'text' ? 'fa-font' : 'fa-image' }} text-xs"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-xs font-medium text-gray-900 truncate">{{ $el['type'] === 'text' ? (str_contains($el['content'] ?? '', '{') ? 'Variable text' : Str::limit(strip_tags($el['content'] ?? ''), 18)) : 'Image: ' . ucfirst($el['imageField'] ?? 'logo') }}</p>
                                        <p class="text-[10px] text-gray-400">x:{{ $el['x'] ?? 50 }} y:{{ $el['y'] ?? 50 }}</p>
                                    </div>
                                </div>
                                <button type="button" onclick="removeElement(event, {{ $i }})" class="text-red-400 hover:text-red-600 p-1 shrink-0"><i class="fa-solid fa-trash-can text-xs"></i></button>
                            </div>
                        @endforeach
                    </div>
                    <div class="grid grid-cols-2 gap-2 mt-3">
                        <button type="button" draggable="true" id="dragAddText" onclick="addElement('text')" class="rounded-lg border border-blue-200 bg-blue-50 px-2 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100 cursor-grab active:cursor-grabbing"><i class="fa-solid fa-font mr-1"></i> Text</button>
                        <button type="button" draggable="true" id="dragAddImage" onclick="addElement('image')" class="rounded-lg border border-purple-200 bg-purple-50 px-2 py-2 text-xs font-semibold text-purple-700 hover:bg-purple-100 cursor-grab active:cursor-grabbing"><i class="fa-solid fa-image mr-1"></i> Image</button>
                    </div>
                    <p class="text-[10px] text-gray-400 mt-1.5">Tip: drag a button onto the canvas, or click it to add at a default spot.</p>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                    <h3 class="font-semibold text-gray-900 mb-2">Student Details</h3>
                    <p class="text-[11px] text-gray-500 mb-3">Click a variable to insert into the selected text — the real student's value is used on the actual certificate.</p>
                    <div class="flex flex-wrap gap-1.5 max-h-56 overflow-y-auto">
                        @foreach($studentVariables as $var => $label)
                            <button type="button" onclick="insertVariable('{{ $var }}')"
                                class="rounded-md border border-gray-200 bg-gray-50 px-2 py-1 text-[11px] font-mono text-gray-700 hover:border-bd-green hover:text-bd-green transition">
                                {{ $var }}
                                <span class="block text-[9px] font-sans text-gray-400">{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- CENTER: Canvas -->
            <div class="space-y-3">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-3">
                    <div class="flex items-center justify-between mb-3 px-1">
                        <h3 class="font-semibold text-gray-900">Live Preview</h3>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="zoomPreview(-0.05)" class="w-8 h-8 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50"><i class="fa-solid fa-minus text-xs"></i></button>
                            <span id="zoomLabel" class="text-xs text-gray-500 w-10 text-center">55%</span>
                            <button type="button" onclick="zoomPreview(0.05)" class="w-8 h-8 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50"><i class="fa-solid fa-plus text-xs"></i></button>
                            <button type="button" onclick="resetZoom()" class="w-8 h-8 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50" title="Reset zoom"><i class="fa-solid fa-expand text-xs"></i></button>
                        </div>
                    </div>
                    <div class="bg-gray-200/70 rounded-xl p-4 flex justify-center overflow-auto" style="min-height: 420px;">
                        <div id="canvasOuter" class="relative bg-white shadow-2xl border-4 border-green-900 overflow-hidden" style="width: {{ $templateW }}px; height: {{ $templateH }}px; transform: scale(0.55); transform-origin: top center; flex-shrink: 0;">
                            {{-- Background layer --}}
                            <div id="bgLayer" class="absolute inset-0" style="opacity: {{ $bgOpacity }};">
                                @if($template->background_image)
                                    <img src="{{ asset('storage/' . $template->background_image) }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full bg-gradient-to-br from-green-100 via-white to-emerald-100"></div>
                                @endif
                            </div>
                            {{-- Elements layer (draggable) --}}
                            <div id="canvas" class="absolute inset-0" style="width: {{ $templateW }}px; height: {{ $templateH }}px;">
                                @include('dashboard.certificates.partials.render-elements', ['template' => $template])
                            </div>
                        </div>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-2">Drag elements to move them. Click an element to select and edit. Use the handles to resize text/image.</p>
                </div>

                <!-- Properties -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                    <h3 class="font-semibold text-gray-900 mb-3" id="propHeader">Element Properties — select an element</h3>

                    <div id="textProps" class="hidden">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div class="col-span-2 md:col-span-4">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Content</label>
                                <input type="text" id="el_content" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Text or click a student variable below">
                            </div>
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">Font Size</label><input type="number" id="el_fontSize" min="6" max="200" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">Letter Spacing</label><input type="number" id="el_letterSpacing" min="0" max="50" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">Color</label><input type="color" id="el_color" class="h-9 w-full rounded-lg border border-gray-300 cursor-pointer"></div>
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">Font</label>
                                <select id="el_fontFamily" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-sm">
                                    <option value="Georgia, serif">Serif (Georgia)</option>
                                    <option value="Arial, sans-serif">Sans (Arial)</option>
                                    <option value="Courier New, monospace">Mono (Courier)</option>
                                    <option value='"Brush Script MT", cursive'>Cursive</option>
                                </select>
                            </div>
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">Align</label>
                                <select id="el_align" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-sm">
                                    <option value="left">Left</option><option value="center">Center</option><option value="right">Right</option>
                                </select>
                            </div>
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">Rotation</label><input type="number" id="el_rotation" min="-180" max="180" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
                            <div class="flex items-end gap-3 pb-1">
                                <label class="flex items-center gap-1.5 text-xs text-gray-600"><input type="checkbox" id="el_bold" class="accent-primary"> Bold</label>
                                <label class="flex items-center gap-1.5 text-xs text-gray-600"><input type="checkbox" id="el_italic" class="accent-primary"> Italic</label>
                                <label class="flex items-center gap-1.5 text-xs text-gray-600"><input type="checkbox" id="el_underline" class="accent-primary"> Underline</label>
                            </div>
                        </div>
                    </div>

                    <div id="imageProps" class="hidden">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">Image Source</label>
                                <select id="el_imageField" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-sm">
                                    <option value="logo">Logo</option><option value="signature">Signature</option>
                                    <option value="background">Background</option><option value="custom">Custom URL</option>
                                </select>
                            </div>
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">Width</label><input type="number" id="el_width" min="20" max="1000" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">Height</label><input type="number" id="el_height" min="20" max="600" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">Rotation</label><input type="number" id="el_rotation" min="-180" max="180" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
                            <div class="col-span-2"><label class="block text-xs font-medium text-gray-600 mb-1">Custom Image URL</label><input type="text" id="el_imageUrl" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="https://..."></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mt-4 pt-3 border-t border-gray-100">
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">X</label><input type="number" id="el_x" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">Y</label><input type="number" id="el_y" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">Box Width</label><input type="number" id="el_boxWidth" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
                        <div class="col-span-2"><label class="block text-xs font-medium text-gray-600 mb-1">Element Opacity: <span id="opacityVal">100%</span></label><input type="range" id="el_opacity" min="0.05" max="1" step="0.05" value="1" class="w-full accent-primary"></div>
                    </div>
                </div>

                <!-- Template + background settings -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                    <h3 class="font-semibold text-gray-900 mb-3">Template & Background</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="col-span-2"><x-ui.label for="name">Template Name</x-ui.label><x-ui.input type="text" name="name" id="name" value="{{ $template->name }}" required /></div>
                        <div><x-ui.label for="type">Type</x-ui.label>
                            <x-ui.select name="type" id="type">
                                <option value="course_completion" @selected($template->type === 'course_completion')>Course Completion</option>
                                <option value="achievement" @selected($template->type === 'achievement')>Achievement</option>
                                <option value="participation" @selected($template->type === 'participation')>Participation</option>
                            </x-ui.select>
                        </div>
                        <div><x-ui.label for="width">Width (px)</x-ui.label><x-ui.input type="number" name="width" id="width" value="{{ $templateW }}" min="600" max="3000" /></div>
                        <div><x-ui.label for="height">Height (px)</x-ui.label><x-ui.input type="number" name="height" id="height" value="{{ $templateH }}" min="400" max="2000" /></div>
                        <div class="col-span-2"><label class="block text-xs font-medium text-gray-600 mb-1">Background Opacity: <span id="bgOpacityVal">{{ round($bgOpacity * 100) }}%</span></label><input type="range" id="bgOpacity" min="0.05" max="1" step="0.05" value="{{ $bgOpacity }}" class="w-full accent-primary"></div>
                        <div><x-ui.label for="background_image">Background</x-ui.label><input type="file" name="background_image" id="background_image" accept="image/*" class="block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-bd-green file:text-white"></div>
                        <div><x-ui.label for="logo_image">Logo</x-ui.label><input type="file" name="logo_image" id="logo_image" accept="image/*" class="block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-bd-green file:text-white"></div>
                        <div><x-ui.label for="signature_image">Signature</x-ui.label><input type="file" name="signature_image" id="signature_image" accept="image/*" class="block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-bd-green file:text-white"></div>
                        <div class="flex items-end gap-4 pb-1">
                            <label class="flex items-center gap-1.5 text-xs text-gray-600"><input type="checkbox" name="is_active" value="1" @checked($template->is_active) class="accent-primary"> Active</label>
                            <label class="flex items-center gap-1.5 text-xs text-gray-600"><input type="checkbox" name="is_default" value="1" @checked($template->is_default) class="accent-primary"> Default</label>
                        </div>
                    </div>
                    <div class="flex gap-3 mt-4">
                        <a href="{{ route('dashboard.certificates.templates.index') }}" class="flex-1 rounded-xl border border-gray-200 px-4 py-2.5 text-center text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</a>
                        <button type="submit" class="flex-1 rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-white hover:opacity-90">Save Template</button>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

@push('scripts')
<script>
    let elements = @json($elements);
    let selectedIndex = -1;
    let zoom = 0.55;

    // ---------- Render preview from JS state ----------
    function renderPreview() {
        const canvas = document.getElementById('canvas');
        canvas.innerHTML = '';
        const logoUrl = @json($template->logo_image ? asset('storage/' . $template->logo_image) : app(\App\Services\SettingsService::class)->getLogo());
        const sigUrl = @json($template->signature_image ? asset('storage/' . $template->signature_image) : '');
        const bgUrl = @json($template->background_image ? asset('storage/' . $template->background_image) : '');
        const values = {
            institution_name: 'Dhaka IT Institute', student_name: 'Rafiqul Islam',
            course_name: 'Full Stack Web Development', certificate_number: 'DII-202608-00001-001',
            verification_code: 'ABC123XYZ789', issued_at: '07 Aug 2026', grade: 'A+',
            student_id: 'STU-0001', student_phone: '01712345678', student_email: 'student@example.com',
            course_code: 'DIT-WD-01', course_duration: '12 months',
            institution_phone: '+880 1682-71557', institution_address: 'Mirpur-10, Dhaka',
        };

        elements.forEach((el, i) => {
            const node = document.createElement(el.type === 'text' ? 'div' : 'img');
            node.dataset.index = i;
            node.dataset.type = el.type;
            node.classList.add('cert-elem');
            let style = `position:absolute;left:${el.x||50}px;top:${el.y||50}px;opacity:${el.opacity ?? 1};cursor:move;`;
            if (el.rotation) style += `transform:rotate(${el.rotation}deg);transform-origin:center;`;
            if (el.type === 'text') {
                let content = (el.content||'');
                Object.keys(values).forEach(k => content = content.replace(new RegExp('\\{' + k + '\\}', 'g'), values[k]));
                node.textContent = content;
                style += `width:${el.width||700}px;font-size:${el.fontSize||20}px;font-family:${el.fontFamily||'Georgia, serif'};color:${el.color||'#111827'};text-align:${el.align||'center'};`;
                if (el.bold) style += 'font-weight:bold;';
                if (el.italic) style += 'font-style:italic;';
                if (el.underline) style += 'text-decoration:underline;';
                if (el.letterSpacing) style += `letter-spacing:${el.letterSpacing}px;`;
            } else {
                let src = el.imageField === 'logo' ? logoUrl : el.imageField === 'signature' ? sigUrl : el.imageField === 'background' ? bgUrl : el.imageUrl || '';
                if (!src) return;
                node.src = src;
                style += `width:${el.width||160}px;height:${el.height||60}px;object-fit:contain;`;
            }
            node.style.cssText = style;
            node.addEventListener('dblclick', (e) => { e.stopPropagation(); editTextInline(i); });
            canvas.appendChild(node);
        });
        updateSelection();
    }

    // ---------- Selection ----------
    function updateSelection() {
        const nodes = document.querySelectorAll('#canvas .cert-elem');
        nodes.forEach(n => n.classList.remove('ring-2', 'ring-bd-green'));
        if (selectedIndex < 0) return;
        const node = nodes[selectedIndex];
        if (!node) return;
        node.classList.add('ring-2', 'ring-bd-green');
    }

    // ---------- Drag & drop ----------
    function initDrag() {
        const canvas = document.getElementById('canvas');
        let dragging = null, startX = 0, startY = 0, origX = 0, origY = 0;

        canvas.addEventListener('mousedown', (e) => {
            const node = e.target.closest('.cert-elem');
            if (!node) { selectedIndex = -1; updateSelection(); return; }
            const idx = parseInt(node.dataset.index);
            selectElement(idx);
            dragging = idx;
            startX = e.clientX; startY = e.clientY;
            origX = elements[idx].x || 50; origY = elements[idx].y || 50;
            e.preventDefault();
        });

        document.addEventListener('mousemove', (e) => {
            if (dragging === null) return;
            const scale = canvas.getBoundingClientRect().width / ({{ $templateW }});
            const dx = (e.clientX - startX) / scale;
            const dy = (e.clientY - startY) / scale;
            elements[dragging].x = Math.max(0, Math.round(origX + dx));
            elements[dragging].y = Math.max(0, Math.round(origY + dy));
            renderPreview();
            document.getElementById('el_x').value = elements[dragging].x;
            document.getElementById('el_y').value = elements[dragging].y;
        });

        document.addEventListener('mouseup', () => { dragging = null; });
    }

    // ---------- Double-click inline text editing ----------
    function editTextInline(index) {
        const el = elements[index];
        if (!el || el.type !== 'text') return;
        selectElement(index);
        const canvas = document.getElementById('canvas');
        const node = canvas.querySelector('.cert-elem[data-index="' + index + '"]');
        if (!node) return;

        // Replace with editable input positioned exactly over the element
        const input = document.createElement('input');
        input.type = 'text';
        input.value = el.content || '';
        input.className = 'absolute z-50 px-1 rounded border border-blue-400 bg-white/95 shadow';
        input.style.cssText = `left:${el.x||50}px;top:${el.y||50}px;width:${el.width||700}px;font-size:${el.fontSize||20}px;font-family:${el.fontFamily||'Georgia, serif'};color:${el.color||'#111827'};text-align:${el.align||'center'};line-height:1.2;`;
        if (el.bold) input.style.fontWeight = 'bold';
        if (el.italic) input.style.fontStyle = 'italic';
        node.style.display = 'none';
        canvas.appendChild(input);
        input.focus();
        input.select();

        let done = false;
        const commit = () => {
            if (done) return; done = true;
            elements[index].content = input.value;
            input.remove();
            node.style.display = '';
            renderPreview();
        };
        input.addEventListener('blur', commit);
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); commit(); }
            if (e.key === 'Escape') { input.value = elements[index].content || ''; commit(); }
            // Stop arrow keys from moving the canvas-drag, and stop page scroll
            e.stopPropagation();
        });
    }

    // ---------- Element selection + props ----------
    function selectElement(index) {
        selectedIndex = index;
        const el = elements[index];
        if (!el) return;
        document.querySelectorAll('.element-item').forEach((item, i) => {
            item.classList.toggle('bg-emerald-50', i === index);
            item.classList.toggle('border-emerald-300', i === index);
        });
        document.getElementById('propHeader').textContent = el.type === 'text' ? 'Text Element Properties' : 'Image Element Properties';

        if (el.type === 'text') {
            document.getElementById('textProps').classList.remove('hidden');
            document.getElementById('imageProps').classList.add('hidden');
            document.getElementById('el_content').value = el.content || '';
            document.getElementById('el_fontSize').value = el.fontSize || 20;
            document.getElementById('el_letterSpacing').value = el.letterSpacing || 0;
            document.getElementById('el_color').value = el.color || '#111827';
            document.getElementById('el_fontFamily').value = el.fontFamily || 'Georgia, serif';
            document.getElementById('el_align').value = el.align || 'center';
            document.getElementById('el_rotation').value = el.rotation || 0;
            document.getElementById('el_bold').checked = !!el.bold;
            document.getElementById('el_italic').checked = !!el.italic;
            document.getElementById('el_underline').checked = !!el.underline;
        } else {
            document.getElementById('textProps').classList.add('hidden');
            document.getElementById('imageProps').classList.remove('hidden');
            document.getElementById('el_imageField').value = el.imageField || 'logo';
            document.getElementById('el_width').value = el.width || 160;
            document.getElementById('el_height').value = el.height || 60;
            document.getElementById('el_rotation').value = el.rotation || 0;
            document.getElementById('el_imageUrl').value = el.imageUrl || '';
        }
        document.getElementById('el_x').value = el.x || 50;
        document.getElementById('el_y').value = el.y || 50;
        document.getElementById('el_boxWidth').value = el.width || 700;
        document.getElementById('el_opacity').value = el.opacity ?? 1;
        document.getElementById('opacityVal').textContent = Math.round((el.opacity ?? 1) * 100) + '%';
        renderPreview();
    }

    // ---------- Add / remove / move ----------
    function addElement(type) {
        const el = type === 'text'
            ? { type: 'text', content: 'New Text', x: 100, y: 100, width: 700, fontSize: 24, fontFamily: 'Georgia, serif', color: '#111827', bold: false, italic: false, align: 'center', letterSpacing: 0, opacity: 1, rotation: 0 }
            : { type: 'image', imageField: 'logo', x: 100, y: 100, width: 160, height: 60, opacity: 1, rotation: 0 };
        elements.push(el);
        renderElementList();
        renderPreview();
        selectElement(elements.length - 1);
    }

    function removeElement(e, index) {
        e.stopPropagation();
        elements.splice(index, 1);
        selectedIndex = -1;
        renderElementList();
        renderPreview();
    }

    function insertVariable(variable) {
        if (selectedIndex < 0 || elements[selectedIndex].type !== 'text') { alert('Select a text element first.'); return; }
        const input = document.getElementById('el_content');
        input.value += '{' + variable + '}';
        elements[selectedIndex].content = input.value;
        renderPreview();
    }

    function bringForward() { if (selectedIndex < 0 || selectedIndex >= elements.length - 1) return; [elements[selectedIndex], elements[selectedIndex+1]] = [elements[selectedIndex+1], elements[selectedIndex]]; renderElementList(); selectElement(selectedIndex + 1); }
    function sendBackward() { if (selectedIndex <= 0) return; [elements[selectedIndex], elements[selectedIndex-1]] = [elements[selectedIndex-1], elements[selectedIndex]]; renderElementList(); selectElement(selectedIndex - 1); }

    function renderElementList() {
        const list = document.getElementById('elementList');
        list.innerHTML = '';
        elements.forEach((el, i) => {
            const div = document.createElement('div');
            div.className = 'element-item flex items-center justify-between p-2 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-50 transition' + (i === selectedIndex ? ' bg-emerald-50 border-emerald-300' : '');
            div.onclick = () => selectElement(i);
            const label = el.type === 'text' ? (String(el.content || '').includes('{') ? 'Variable text' : (el.content || '').substring(0, 18)) : 'Image: ' + (el.imageField || 'logo');
            div.innerHTML = `<div class="flex items-center gap-2 min-w-0">
                <span class="w-7 h-7 shrink-0 rounded-lg flex items-center justify-center ${el.type === 'text' ? 'bg-blue-100 text-blue-600' : 'bg-purple-100 text-purple-600'}"><i class="fa-solid ${el.type === 'text' ? 'fa-font' : 'fa-image'} text-xs"></i></span>
                <div class="min-w-0"><p class="text-xs font-medium text-gray-900 truncate">${label}</p><p class="text-[10px] text-gray-400">x:${el.x||50} y:${el.y||50}</p></div>
            </div><button type="button" onclick="removeElement(event, ${i})" class="text-red-400 hover:text-red-600 p-1 shrink-0"><i class="fa-solid fa-trash-can text-xs"></i></button>`;
            list.appendChild(div);
        });
    }

    // ---------- Zoom ----------
    function applyZoom() {
        document.getElementById('canvasOuter').style.transform = `scale(${zoom})`;
        document.getElementById('canvasOuter').style.marginBottom = `-${(1 - zoom) * ({{ $templateH }})}px`;
        document.getElementById('zoomLabel').textContent = Math.round(zoom * 100) + '%';
    }
    function zoomPreview(delta) { zoom = Math.min(1.2, Math.max(0.25, zoom + delta)); applyZoom(); }
    function resetZoom() { zoom = 0.55; applyZoom(); }

    // ---------- Wire inputs ----------
    document.addEventListener('DOMContentLoaded', function() {
        initDrag();
        applyZoom();

        const map = {
            el_content:'content', el_fontSize:'fontSize', el_letterSpacing:'letterSpacing', el_color:'color',
            el_fontFamily:'fontFamily', el_align:'align', el_bold:'bold', el_italic:'italic', el_underline:'underline', el_rotation:'rotation',
            el_imageField:'imageField', el_width:'width', el_height:'height', el_imageUrl:'imageUrl',
            el_x:'x', el_y:'y', el_boxWidth:'width', el_opacity:'opacity'
        };
        Object.keys(map).forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            const evt = el.type === 'checkbox' ? 'change' : 'input';
            el.addEventListener(evt, function() {
                if (selectedIndex < 0) return;
                const key = map[id];
                let val = this.value;
                if (this.type === 'checkbox') val = this.checked;
                if (['el_fontSize','el_letterSpacing','el_width','el_height','el_x','el_y','el_boxWidth','el_rotation'].includes(id)) val = parseInt(val) || 0;
                if (id === 'el_opacity') { val = parseFloat(val); document.getElementById('opacityVal').textContent = Math.round(val * 100) + '%'; }
                elements[selectedIndex][key] = val;
                if (id === 'el_boxWidth') elements[selectedIndex].width = val;
                renderPreview();
            });
        });

        // Background opacity
        document.getElementById('bgOpacity').addEventListener('input', function() {
            document.getElementById('bgLayer').style.opacity = this.value;
            document.getElementById('bgOpacityVal').textContent = Math.round(this.value * 100) + '%';
        });

        // Keyboard delete
        document.addEventListener('keydown', function(e) {
            if ((e.key === 'Delete' || e.key === 'Backspace') && selectedIndex >= 0) {
                const active = document.activeElement;
                if (active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA')) return;
                e.preventDefault();
                elements.splice(selectedIndex, 1);
                selectedIndex = -1;
                renderElementList();
                renderPreview();
            }
        });

        // Submit: serialize elements + background opacity
        document.getElementById('templateEditorForm').addEventListener('submit', function() {
            const layout = {
                elements: elements,
                background_opacity: parseFloat(document.getElementById('bgOpacity').value),
            };
            document.getElementById('layoutConfigInput').value = JSON.stringify(layout);
        });

        // Click on empty canvas deselects
        document.getElementById('canvas').addEventListener('click', (e) => {
            if (!e.target.closest('.cert-elem')) { selectedIndex = -1; updateSelection(); }
        });

        // ---------- Drag from sidebar onto canvas ----------
        const canvasEl = document.getElementById('canvas');
        const addTextBtn = document.getElementById('dragAddText');
        const addImageBtn = document.getElementById('dragAddImage');

        ['dragAddText', 'dragAddImage'].forEach(id => {
            const btn = document.getElementById(id);
            btn.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('text/plain', id === 'dragAddText' ? 'text' : 'image');
                e.dataTransfer.effectAllowed = 'copy';
            });
        });

        canvasEl.addEventListener('dragover', (e) => { e.preventDefault(); e.dataTransfer.dropEffect = 'copy'; });
        canvasEl.addEventListener('drop', (e) => {
            e.preventDefault();
            const type = e.dataTransfer.getData('text/plain');
            if (type !== 'text' && type !== 'image') return;
            // Convert mouse position to canvas coordinates (accounting for zoom + scroll)
            const rect = canvasEl.getBoundingClientRect();
            const scale = rect.width / ({{ $templateW }});
            const x = Math.max(0, Math.round((e.clientX - rect.left) / scale));
            const y = Math.max(0, Math.round((e.clientY - rect.top) / scale));
            const el = type === 'text'
                ? { type: 'text', content: 'New Text', x, y, width: 700, fontSize: 24, fontFamily: 'Georgia, serif', color: '#111827', bold: false, italic: false, align: 'center', letterSpacing: 0, opacity: 1, rotation: 0 }
                : { type: 'image', imageField: 'logo', x, y, width: 160, height: 60, opacity: 1, rotation: 0 };
            elements.push(el);
            renderElementList();
            renderPreview();
            selectElement(elements.length - 1);
        });

        renderElementList();
        renderPreview();
    });
</script>
@endpush
@endsection
