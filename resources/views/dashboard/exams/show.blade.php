@extends('layouts.admin')

@section('title', 'Exam Details')
@section('page-title', 'পরীক্ষার বিবরণ')
@section('page-description', 'View exam details, questions, and results')

@section('content')
    <div class="space-y-6">
        <!-- Exam Header -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">{{ $exam->title }}</h2>
                    <p class="text-gray-500 mt-1">{{ $exam->course->name ?? 'N/A' }} - {{ $exam->batch->name ?? 'N/A' }}</p>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('dashboard.exams.edit', $exam) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit Exam
                    </a>
                    <a href="{{ route('dashboard.exams.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Back to List
                    </a>
                </div>
            </div>

            <!-- Exam Stats -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mt-6">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-sm text-blue-600 font-medium">Type</div>
                    <div class="text-2xl font-bold text-blue-900 mt-1">{{ strtoupper($exam->type) }}</div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-sm text-green-600 font-medium">Total Marks</div>
                    <div class="text-2xl font-bold text-green-900 mt-1">{{ $exam->total_marks }}</div>
                </div>
                <div class="bg-yellow-50 p-4 rounded-lg">
                    <div class="text-sm text-yellow-600 font-medium">Pass Marks</div>
                    <div class="text-2xl font-bold text-yellow-900 mt-1">{{ $exam->pass_marks }}</div>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg">
                    <div class="text-sm text-purple-600 font-medium">Duration</div>
                    <div class="text-2xl font-bold text-purple-900 mt-1">{{ $exam->duration_minutes }} min</div>
                </div>
                <div class="bg-indigo-50 p-4 rounded-lg">
                    <div class="text-sm text-indigo-600 font-medium">Questions</div>
                    <div class="text-2xl font-bold text-indigo-900 mt-1">{{ $exam->questions->count() }}</div>
                </div>
            </div>

            <!-- Exam Info -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <span class="text-sm font-medium text-gray-500">Start Time:</span>
                    <span class="text-sm text-gray-900 ml-2">{{ $exam->start_time ? $exam->start_time->format('M d, Y h:i A') : 'Not set' }}</span>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-500">End Time:</span>
                    <span class="text-sm text-gray-900 ml-2">{{ $exam->end_time ? $exam->end_time->format('M d, Y h:i A') : 'Not set' }}</span>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-500">Status:</span>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full ml-2
                        @if($exam->status === 'active') bg-green-100 text-green-800
                        @elseif($exam->status === 'draft') bg-gray-100 text-gray-800
                        @else bg-blue-100 text-blue-800
                        @endif">
                        {{ ucfirst($exam->status) }}
                    </span>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-500">Students Attempted:</span>
                    <span class="text-sm text-gray-900 ml-2">{{ $exam->results->count() }}</span>
                </div>
            </div>

            @if($exam->instructions)
                <div class="mt-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Instructions:</h3>
                    <p class="text-sm text-gray-700 bg-gray-50 p-4 rounded-lg">{{ $exam->instructions }}</p>
                </div>
            @endif
        </div>

        <!-- Questions Section -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Questions ({{ $exam->questions->count() }})</h3>
                <button onclick="openAddQuestionModal()" class="inline-flex items-center px-4 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Question
                </button>
            </div>

            @if($exam->questions->count() > 0)
                <div class="space-y-4">
                    @foreach($exam->questions as $index => $question)
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 mb-2">
                                        <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-1 rounded">Q{{ $index + 1 }}</span>
                                        <span class="bg-gray-100 text-gray-800 text-xs font-semibold px-2 py-1 rounded">{{ $question->marks }} marks</span>
                                        <span class="bg-purple-100 text-purple-800 text-xs font-semibold px-2 py-1 rounded">{{ strtoupper($question->type) }}</span>
                                    </div>
                                    <p class="text-gray-900 font-medium">{{ $question->question_text }}</p>
                                    
                                    @if($question->type === 'mcq' && $question->options)
                                        <div class="mt-3 space-y-2">
                                            @foreach($question->options as $optIndex => $option)
                                                <div class="flex items-center space-x-2">
                                                    <span class="w-6 h-6 flex items-center justify-center rounded-full text-xs font-medium
                                                        @if($question->correct_answer === $option) bg-green-100 text-green-800
                                                        @else bg-gray-100 text-gray-600
                                                        @endif">
                                                        {{ chr(65 + $optIndex) }}
                                                    </span>
                                                    <span class="text-sm text-gray-700">{{ $option }}</span>
                                                    @if($question->correct_answer === $option)
                                                        <span class="text-xs text-green-600 font-medium">(Correct)</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div class="flex space-x-2 ml-4">
                                    <button onclick="openEditQuestionModal({{ $question->id }})" class="text-indigo-600 hover:text-indigo-800">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <form action="{{ route('dashboard.exams.questions.destroy', [$exam, $question]) }}" method="POST" onsubmit="return confirmDelete(this, 'Are you sure you want to delete this question?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    <p>No questions added yet.</p>
                </div>
            @endif
        </div>

        <!-- Add Question Modal -->
        <div id="addQuestionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-900">Add Question</h3>
                        <button onclick="closeAddQuestionModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form action="{{ route('dashboard.exams.questions.store', $exam) }}" method="POST">
                        @csrf
                        
                        <!-- Question Type -->
                        <div class="mb-4">
                            <x-ui.select name="type" label="Question Type" :required="true" id="question_type">
                                <option value="mcq">Multiple Choice (MCQ)</option>
                                <option value="short">Short Answer</option>
                                <option value="long">Long Answer</option>
                            </x-ui.select>
                        </div>

                        <!-- Question Text -->
                        <div class="mb-4">
                            <label for="question_text" class="block text-sm font-semibold text-gray-700 mb-2">Question Text *</label>
                            <textarea name="question_text" id="question_text" rows="3" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bd-green focus:border-transparent"></textarea>
                        </div>

                        <!-- Marks -->
                        <div class="mb-4">
                            <label for="marks" class="block text-sm font-semibold text-gray-700 mb-2">Marks *</label>
                            <input type="number" name="marks" id="marks" required min="1"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bd-green focus:border-transparent">
                        </div>

                        <!-- MCQ Options (shown only for MCQ type) -->
                        <div id="mcq_options" class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Options *</label>
                            <div class="space-y-2">
                                <input type="text" name="options[]" placeholder="Option A" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bd-green focus:border-transparent">
                                <input type="text" name="options[]" placeholder="Option B" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bd-green focus:border-transparent">
                                <input type="text" name="options[]" placeholder="Option C" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bd-green focus:border-transparent">
                                <input type="text" name="options[]" placeholder="Option D" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bd-green focus:border-transparent">
                            </div>
                        </div>

                        <!-- Correct Answer (shown only for MCQ type) -->
                        <div id="correct_answer_field" class="mb-4">
                            <label for="correct_answer" class="block text-sm font-semibold text-gray-700 mb-2">Correct Answer *</label>
                            <input type="text" name="correct_answer" id="correct_answer" placeholder="Enter the correct option text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bd-green focus:border-transparent">
                        </div>

                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                            <button type="button" onclick="closeAddQuestionModal()" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" class="px-6 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors">
                                Add Question
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Question Modal -->
        <div id="editQuestionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-900">Edit Question</h3>
                        <button onclick="closeEditQuestionModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form id="editQuestionForm" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <!-- Question Type -->
                        <div class="mb-4">
                            <x-ui.select name="type" label="Question Type" :required="true" id="edit_question_type">
                                <option value="mcq">Multiple Choice (MCQ)</option>
                                <option value="short">Short Answer</option>
                                <option value="long">Long Answer</option>
                            </x-ui.select>
                        </div>

                        <!-- Question Text -->
                        <div class="mb-4">
                            <label for="edit_question_text" class="block text-sm font-semibold text-gray-700 mb-2">Question Text *</label>
                            <textarea name="question_text" id="edit_question_text" rows="3" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bd-green focus:border-transparent"></textarea>
                        </div>

                        <!-- Marks -->
                        <div class="mb-4">
                            <label for="edit_marks" class="block text-sm font-semibold text-gray-700 mb-2">Marks *</label>
                            <input type="number" name="marks" id="edit_marks" required min="1"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bd-green focus:border-transparent">
                        </div>

                        <!-- MCQ Options (shown only for MCQ type) -->
                        <div id="edit_mcq_options" class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Options *</label>
                            <div class="space-y-2" id="edit_options_container">
                                <!-- Options will be populated dynamically -->
                            </div>
                        </div>

                        <!-- Correct Answer (shown only for MCQ type) -->
                        <div id="edit_correct_answer_field" class="mb-4">
                            <label for="edit_correct_answer" class="block text-sm font-semibold text-gray-700 mb-2">Correct Answer *</label>
                            <input type="text" name="correct_answer" id="edit_correct_answer" placeholder="Enter the correct option text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bd-green focus:border-transparent">
                        </div>

                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                            <button type="button" onclick="closeEditQuestionModal()" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" class="px-6 py-2 bg-bd-green text-white rounded-lg hover:bg-bd-green-dark transition-colors">
                                Update Question
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            // Add Question Modal
            function openAddQuestionModal() {
                document.getElementById('addQuestionModal').classList.remove('hidden');
                toggleMcqFields('question_type', 'mcq_options', 'correct_answer_field');
            }

            function closeAddQuestionModal() {
                document.getElementById('addQuestionModal').classList.add('hidden');
            }

            // Edit Question Modal
            function openEditQuestionModal(questionId) {
                const fetchUrl = "{{ route('dashboard.exams.questions.show', [$exam, '__QUESTION_ID__']) }}".replace('__QUESTION_ID__', questionId);
                const updateUrl = "{{ route('dashboard.exams.questions.update', [$exam, '__QUESTION_ID__']) }}".replace('__QUESTION_ID__', questionId);

                // Fetch question data
                fetch(fetchUrl)
                    .then(response => {
                        if (!response.ok) {
                            return response.text().then(() => {
                                throw new Error('Failed to fetch question: ' + response.status);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        document.getElementById('editQuestionForm').action = updateUrl;
                        document.getElementById('edit_question_text').value = data.question_text;
                        document.getElementById('edit_marks').value = data.marks;
                        
                        // Set question type
                        const editTypeSelect = document.getElementById('edit_question_type');
                        if (editTypeSelect) {
                            editTypeSelect.value = data.type;
                            editTypeSelect.dispatchEvent(new Event('change', { bubbles: true }));
                            
                            // Update custom select UI if it exists
                            if (typeof selectOption === 'function') {
                                setTimeout(() => {
                                    selectOption('edit_question_type', data.type, null);
                                }, 100);
                            }
                        }
                        
                        // Handle MCQ options
                        if (data.type === 'mcq' && data.options) {
                            const container = document.getElementById('edit_options_container');
                            if (container) {
                                container.innerHTML = '';
                                data.options.forEach((option, index) => {
                                    const input = document.createElement('input');
                                    input.type = 'text';
                                    input.name = 'options[]';
                                    input.value = option;
                                    input.placeholder = `Option ${String.fromCharCode(65 + index)}`;
                                    input.className = 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bd-green focus:border-transparent';
                                    container.appendChild(input);
                                });
                            }
                            
                            const correctAnswerInput = document.getElementById('edit_correct_answer');
                            if (correctAnswerInput) {
                                correctAnswerInput.value = data.correct_answer || '';
                            }
                        }
                        
                        // Update field visibility
                        updateMcqFieldsVisibility('edit_question_type', 'edit_mcq_options', 'edit_correct_answer_field');
                        
                        document.getElementById('editQuestionModal').classList.remove('hidden');
                    })
                    .catch(() => {
                        alert('Failed to load question data. Please try again.');
                    });
            }

            function closeEditQuestionModal() {
                document.getElementById('editQuestionModal').classList.add('hidden');
            }

            // Toggle MCQ fields based on question type
            function updateMcqFieldsVisibility(typeSelectId, optionsId, correctAnswerId) {
                const typeSelect = document.getElementById(typeSelectId);
                const optionsDiv = document.getElementById(optionsId);
                const correctAnswerDiv = document.getElementById(correctAnswerId);
                
                if (!typeSelect || !optionsDiv || !correctAnswerDiv) {
                    return;
                }
                
                const isMcq = typeSelect.value === 'mcq';
                optionsDiv.style.display = isMcq ? 'block' : 'none';
                correctAnswerDiv.style.display = isMcq ? 'block' : 'none';
                
                // Set required attributes
                const optionInputs = optionsDiv.querySelectorAll('input');
                const correctAnswerInput = correctAnswerDiv.querySelector('input');
                
                optionInputs.forEach(input => {
                    input.required = isMcq;
                });
                if (correctAnswerInput) {
                    correctAnswerInput.required = isMcq;
                }
            }
            
            function toggleMcqFields(typeSelectId, optionsId, correctAnswerId) {
                const typeSelect = document.getElementById(typeSelectId);
                
                if (!typeSelect) {
                    return;
                }
                
                typeSelect.addEventListener('change', () => {
                    updateMcqFieldsVisibility(typeSelectId, optionsId, correctAnswerId);
                });
                
                updateMcqFieldsVisibility(typeSelectId, optionsId, correctAnswerId);
            }

            // Initialize on page load
            document.addEventListener('DOMContentLoaded', function() {
                toggleMcqFields('question_type', 'mcq_options', 'correct_answer_field');
            });
        </script>

        <!-- Results Section -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Student Results ({{ $exam->results->count() }})</h3>
                <div class="flex space-x-2">
                    <a href="{{ route('dashboard.exams.export-results', ['exam' => $exam->id, 'format' => 'excel']) }}" 
                       class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export Excel
                    </a>
                    <a href="{{ route('dashboard.exams.export-results', ['exam' => $exam->id, 'format' => 'csv']) }}" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export CSV
                    </a>
                </div>
            </div>

            @if($exam->results->count() > 0)
                <x-ui.data-table 
                    :headers="[
                        ['key' => 'student', 'label' => 'Student'],
                        ['key' => 'obtained_marks', 'label' => 'Obtained Marks'],
                        ['key' => 'total_marks', 'label' => 'Total Marks'],
                        ['key' => 'percentage', 'label' => 'Percentage'],
                        ['key' => 'grade', 'label' => 'Grade'],
                        ['key' => 'status', 'label' => 'Status'],
                        ['key' => 'actions', 'label' => 'Actions'],
                    ]"
                    :rows="$exam->results"
                    :searchable="true"
                    :sortable="true"
                    route="{{ route('dashboard.exams.show', $exam) }}"
                >
                    @foreach($exam->results as $result)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                            <span class="text-indigo-600 font-semibold text-sm">
                                                {{ substr($result->student->name_bn ?? $result->student->user->name ?? 'S', 0, 2) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $result->student->user->name ?? $result->student->name_bn ?? 'N/A' }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            ID: {{ $result->student->registration_no ?? 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                {{ $result->obtained_marks }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $result->total_marks }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <div class="flex items-center">
                                    <span class="font-semibold">{{ number_format(($result->obtained_marks / $result->total_marks) * 100, 2) }}%</span>
                                    <div class="ml-2 w-16 bg-gray-200 rounded-full h-2">
                                        <div class="bg-bd-green h-2 rounded-full" style="width: {{ ($result->obtained_marks / $result->total_marks) * 100 }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                    @if($result->grade === 'A+') bg-green-100 text-green-800
                                    @elseif($result->grade === 'A') bg-blue-100 text-blue-800
                                    @elseif($result->grade === 'B') bg-indigo-100 text-indigo-800
                                    @elseif($result->grade === 'C') bg-yellow-100 text-yellow-800
                                    @elseif($result->grade === 'D') bg-orange-100 text-orange-800
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ $result->grade }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($result->obtained_marks >= $exam->pass_marks)
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        Passed
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                        <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                        Failed
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <a href="{{ route('dashboard.exams.view-result', [$exam, $result]) }}" 
                                       class="text-indigo-600 hover:text-indigo-900" title="View">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('dashboard.exams.edit-result', [$exam, $result]) }}" 
                                       class="text-green-600 hover:text-green-900" title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <form action="{{ route('dashboard.exams.delete-result', [$exam, $result]) }}" method="POST" 
                                          onsubmit="return confirmDelete(this, 'Are you sure you want to delete this result? This action cannot be undone.')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-ui.data-table>
            @else
                <div class="text-center py-8 text-gray-500">
                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p>No students have attempted this exam yet.</p>
                </div>
            @endif
        </div>
    </div>
@endsection
