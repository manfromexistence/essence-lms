@extends('layouts.admin')

@section('title', 'Review Submission')
@section('page-title', 'উত্তর পর্যালোচনা')
@section('page-description', 'Review and annotate student answer')

@push('styles')
<style>
    .canvas-container {
        border: 2px solid #e5e7eb;
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    .tool-btn {
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        transition: all 0.2s;
        border: 2px solid transparent;
    }
    .tool-btn:hover {
        background-color: #f3f4f6;
    }
    .tool-btn.active {
        background-color: #10b981;
        color: white;
        border-color: #059669;
    }
    .color-btn {
        width: 2rem;
        height: 2rem;
        border-radius: 0.375rem;
        border: 2px solid #e5e7eb;
        cursor: pointer;
        transition: all 0.2s;
    }
    .color-btn:hover {
        transform: scale(1.1);
    }
    .color-btn.active {
        border-color: #3d59f9;
        border-width: 3px;
        transform: scale(1.15);
    }
</style>
@endpush

@section('content')
<div class="space-y-6">
    <!-- Student Info -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center">
                    <span class="text-2xl text-indigo-600 font-bold">{{ substr($submission->student->name_bn ?? 'S', 0, 1) }}</span>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">{{ $submission->student->name_bn ?? $submission->student->user->name ?? 'N/A' }}</h2>
                    <p class="text-gray-500">ID: {{ $submission->student->registration_no ?? 'N/A' }}</p>
                    <p class="text-sm text-gray-500">Submitted: {{ $submission->submitted_at->format('M d, Y h:i A') }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500 mb-1">Exam</p>
                <p class="text-lg font-semibold text-gray-900">{{ $exam->title }}</p>
                <p class="text-sm text-gray-500">Total Marks: {{ $exam->total_marks }}</p>
            </div>
        </div>
    </div>

    <!-- Annotation Tools -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Annotation Tools</h3>
        
        <div class="flex flex-wrap gap-4 items-center">
            <!-- Drawing Tools -->
            <div class="flex gap-2">
                <button onclick="setTool('pen')" id="tool-pen" class="tool-btn active">
                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    Pen
                </button>
                <button onclick="setTool('highlighter')" id="tool-highlighter" class="tool-btn">
                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    Highlighter
                </button>
                <button onclick="setTool('text')" id="tool-text" class="tool-btn">
                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Text
                </button>
                <button onclick="setTool('eraser')" id="tool-eraser" class="tool-btn">
                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Eraser
                </button>
            </div>

            <div class="h-8 w-px bg-gray-300"></div>

            <!-- Colors -->
            <div class="flex gap-2 items-center">
                <span class="text-sm text-gray-600 mr-2">Color:</span>
                <button onclick="setColor('#ef4444')" class="color-btn active" style="background-color: #ef4444;" data-color="#ef4444"></button>
                <button onclick="setColor('#10b981')" class="color-btn" style="background-color: #10b981;" data-color="#10b981"></button>
                <button onclick="setColor('#3d59f9')" class="color-btn" style="background-color: #3d59f9;" data-color="#3d59f9"></button>
                <button onclick="setColor('#f59e0b')" class="color-btn" style="background-color: #f59e0b;" data-color="#f59e0b"></button>
                <button onclick="setColor('#8b5cf6')" class="color-btn" style="background-color: #8b5cf6;" data-color="#8b5cf6"></button>
                <button onclick="setColor('#000000')" class="color-btn" style="background-color: #000000;" data-color="#000000"></button>
            </div>

            <div class="h-8 w-px bg-gray-300"></div>

            <!-- Brush Size -->
            <div class="flex gap-2 items-center">
                <span class="text-sm text-gray-600 mr-2">Size:</span>
                <input type="range" id="brush-size" min="1" max="20" value="3" onchange="setBrushSize(this.value)" class="w-32">
                <span id="size-display" class="text-sm text-gray-600 w-8">3</span>
            </div>

            <div class="h-8 w-px bg-gray-300"></div>

            <!-- Actions -->
            <button onclick="clearCanvas()" class="px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200">
                Clear All
            </button>
            <button onclick="saveAnnotations()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                Save Annotations
            </button>
        </div>
    </div>

    <!-- Answer Sheets -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Answer Sheets ({{ count($submission->files) }})</h3>
        
        <div class="space-y-6" id="answer-sheets">
            @foreach($submission->files as $index => $file)
            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="font-semibold text-gray-900">Page {{ $index + 1 }}</h4>
                    <div class="flex gap-2">
                        <button onclick="prevPage()" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300" {{ $index === 0 ? 'disabled' : '' }}>
                            ← Prev
                        </button>
                        <button onclick="nextPage()" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300" {{ $index === count($submission->files) - 1 ? 'disabled' : '' }}>
                            Next →
                        </button>
                    </div>
                </div>
                
                <div class="relative">
                    <canvas id="canvas-{{ $index }}" class="mx-auto" data-index="{{ $index }}" data-file="{{ $file['path'] }}"></canvas>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Grading Form -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Grading</h3>
        
        <form action="{{ route('dashboard.exams.save-review', [$exam, $submission]) }}" method="POST" id="grading-form">
            @csrf
            <input type="hidden" name="annotated_files" id="annotated-files-input">
            
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label for="marks" class="block text-sm font-semibold text-gray-700 mb-2">Marks Obtained *</label>
                    <input type="number" name="marks" id="marks" 
                           value="{{ old('marks', $submission->marks) }}" 
                           min="0" max="{{ $exam->total_marks }}" step="0.5" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bd-green focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Out of {{ $exam->total_marks }}</p>
                </div>

                <div>
                    <label for="grade" class="block text-sm font-semibold text-gray-700 mb-2">Grade</label>
                    <select name="grade" id="grade" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bd-green focus:border-transparent">
                        <option value="A+" {{ old('grade', $submission->grade ?? '') === 'A+' ? 'selected' : '' }}>A+ (80-100)</option>
                        <option value="A" {{ old('grade', $submission->grade ?? '') === 'A' ? 'selected' : '' }}>A (70-79)</option>
                        <option value="B" {{ old('grade', $submission->grade ?? '') === 'B' ? 'selected' : '' }}>B (60-69)</option>
                        <option value="C" {{ old('grade', $submission->grade ?? '') === 'C' ? 'selected' : '' }}>C (50-59)</option>
                        <option value="D" {{ old('grade', $submission->grade ?? '') === 'D' ? 'selected' : '' }}>D (40-49)</option>
                        <option value="F" {{ old('grade', $submission->grade ?? '') === 'F' ? 'selected' : '' }}>F (0-39)</option>
                    </select>
                </div>
            </div>

            <div class="mt-4">
                <label for="feedback" class="block text-sm font-semibold text-gray-700 mb-2">Feedback for Student</label>
                <textarea name="feedback" id="feedback" rows="4" 
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bd-green focus:border-transparent">{{ old('feedback', $submission->feedback) }}</textarea>
            </div>

            <div class="mt-4">
                <label for="teacher_notes" class="block text-sm font-semibold text-gray-700 mb-2">Private Notes (Not visible to student)</label>
                <textarea name="teacher_notes" id="teacher_notes" rows="3" 
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bd-green focus:border-transparent">{{ old('teacher_notes', $submission->teacher_notes) }}</textarea>
            </div>

            <div class="flex justify-between items-center mt-6 pt-6 border-t border-gray-200">
                <a href="{{ route('dashboard.exams.review-submissions', $exam) }}" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Back to List
                </a>
                <button type="submit" class="px-6 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark">
                    Save Review & Grade
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Fabric.js ships in resources/js/admin.js (bundled by Vite), exposed as
     window.fabric. This page uses the Fabric v7 API. --}}
<script>
    let canvases = [];
    let currentCanvas = null;
    let currentTool = 'pen';
    let currentColor = '#ef4444';
    let brushSize = 3;
    let currentPage = 0;

    // Initialize canvases
    document.addEventListener('DOMContentLoaded', function() {
        @foreach($submission->files as $index => $file)
        initCanvas({{ $index }}, '{{ Storage::url($file['path']) }}');
        @endforeach
        
        // Show first page
        showPage(0);
    });

    function initCanvas(index, imagePath) {
        const canvasEl = document.getElementById('canvas-' + index);
        const canvas = new fabric.Canvas(canvasEl, {
            isDrawingMode: true,
            width: 800,
            height: 1000
        });

        // Load background image.
        // Fabric v7: fromURL() returns a Promise, and the background is assigned
        // through the `backgroundImage` property because setBackgroundImage()
        // was removed in v6.
        fabric.FabricImage.fromURL(imagePath)
            .then(function(img) {
                const scale = Math.min(800 / img.width, 1000 / img.height);
                img.scale(scale);
                canvas.backgroundImage = img;
                canvas.setWidth(img.width * scale);
                canvas.setHeight(img.height * scale);
                canvas.requestRenderAll();
            })
            .catch(function(err) {
                console.error('Could not load answer sheet image', imagePath, err);
            });

        // Set initial brush.
        // Fabric v7 does not always materialise a default free-drawing brush when
        // isDrawingMode is enabled through the constructor options, so create one
        // explicitly before touching its properties (the v5 code assumed it
        // already existed and would throw a TypeError here otherwise).
        if (!canvas.freeDrawingBrush) {
            canvas.freeDrawingBrush = new fabric.PencilBrush(canvas);
        }
        canvas.freeDrawingBrush.color = currentColor;
        canvas.freeDrawingBrush.width = brushSize;

        canvases[index] = canvas;
        if (index === 0) currentCanvas = canvas;

        @if($submission->annotated_files && isset($submission->annotated_files[$index]))
        // Load existing annotations if any.
        // Fabric v7: loadFromJSON() returns a Promise; the v5 trailing-callback
        // form is gone (the 2nd argument is now a reviver, not a callback).
        canvas.loadFromJSON(@json($submission->annotated_files[$index]))
            .then(function() {
                canvas.requestRenderAll();
            })
            .catch(function(err) {
                console.error('Could not restore saved annotations', err);
            });
        @endif
    }

    function showPage(index) {
        document.querySelectorAll('[id^="canvas-"]').forEach((el, i) => {
            el.closest('.border').style.display = i === index ? 'block' : 'none';
        });
        currentPage = index;
        currentCanvas = canvases[index];
    }

    function nextPage() {
        if (currentPage < canvases.length - 1) {
            showPage(currentPage + 1);
        }
    }

    function prevPage() {
        if (currentPage > 0) {
            showPage(currentPage - 1);
        }
    }

    function setTool(tool) {
        currentTool = tool;
        
        // Update button states
        document.querySelectorAll('.tool-btn').forEach(btn => btn.classList.remove('active'));
        document.getElementById('tool-' + tool).classList.add('active');

        canvases.forEach(canvas => {
            if (tool === 'pen') {
                canvas.isDrawingMode = true;
                canvas.freeDrawingBrush = new fabric.PencilBrush(canvas);
                canvas.freeDrawingBrush.color = currentColor;
                canvas.freeDrawingBrush.width = brushSize;
            } else if (tool === 'highlighter') {
                canvas.isDrawingMode = true;
                canvas.freeDrawingBrush = new fabric.PencilBrush(canvas);
                canvas.freeDrawingBrush.color = currentColor;
                canvas.freeDrawingBrush.width = brushSize * 3;
                canvas.freeDrawingBrush.opacity = 0.3;
            } else if (tool === 'text') {
                canvas.isDrawingMode = false;
                canvas.on('mouse:down', function(options) {
                    if (options.target) return;
                    // Fabric v7 renamed getPointer() to getScenePoint().
                    const pointer = canvas.getScenePoint(options.e);
                    const text = new fabric.IText('Type here', {
                        left: pointer.x,
                        top: pointer.y,
                        fill: currentColor,
                        fontSize: brushSize * 5
                    });
                    canvas.add(text);
                    canvas.setActiveObject(text);
                    text.enterEditing();
                });
            } else if (tool === 'eraser') {
                canvas.isDrawingMode = false;
                canvas.on('mouse:down', function(options) {
                    if (options.target && options.target !== canvas.backgroundImage) {
                        canvas.remove(options.target);
                    }
                });
            }
        });
    }

    function setColor(color) {
        currentColor = color;
        
        // Update button states
        document.querySelectorAll('.color-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelector(`[data-color="${color}"]`).classList.add('active');

        canvases.forEach(canvas => {
            if (canvas.freeDrawingBrush) {
                canvas.freeDrawingBrush.color = color;
            }
        });
    }

    function setBrushSize(size) {
        brushSize = parseInt(size);
        document.getElementById('size-display').textContent = size;

        canvases.forEach(canvas => {
            if (canvas.freeDrawingBrush) {
                canvas.freeDrawingBrush.width = currentTool === 'highlighter' ? brushSize * 3 : brushSize;
            }
        });
    }

    function clearCanvas() {
        if (confirm('Are you sure you want to clear all annotations on this page?')) {
            currentCanvas.getObjects().forEach(obj => {
                if (obj !== currentCanvas.backgroundImage) {
                    currentCanvas.remove(obj);
                }
            });
            currentCanvas.renderAll();
        }
    }

    function saveAnnotations() {
        const annotatedFiles = {};
        canvases.forEach((canvas, index) => {
            annotatedFiles[index] = canvas.toJSON();
        });
        
        document.getElementById('annotated-files-input').value = JSON.stringify(annotatedFiles);
        alert('Annotations saved! Don\'t forget to submit the grading form.');
    }

    // Auto-save annotations before form submit
    document.getElementById('grading-form').addEventListener('submit', function(e) {
        const annotatedFiles = {};
        canvases.forEach((canvas, index) => {
            annotatedFiles[index] = canvas.toJSON();
        });
        document.getElementById('annotated-files-input').value = JSON.stringify(annotatedFiles);
    });

    // Auto-calculate grade based on marks
    document.getElementById('marks').addEventListener('input', function() {
        const marks = parseFloat(this.value);
        const total = {{ $exam->total_marks }};
        const percentage = (marks / total) * 100;
        
        let grade = 'F';
        if (percentage >= 80) grade = 'A+';
        else if (percentage >= 70) grade = 'A';
        else if (percentage >= 60) grade = 'B';
        else if (percentage >= 50) grade = 'C';
        else if (percentage >= 40) grade = 'D';
        
        document.getElementById('grade').value = grade;
    });
</script>
@endsection

