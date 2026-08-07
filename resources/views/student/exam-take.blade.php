@extends('layouts.admin')

@section('title', 'Take Exam')

@section('content')
<div class="min-h-screen bg-background py-6">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            
            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Timer -->
                <div class="bg-card rounded-lg shadow p-6 mb-4 sticky top-4 border border-border">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-primary rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-8 h-8 text-primary-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div id="timer" class="text-3xl font-bold text-primary mb-1">--:--</div>
                        <p class="text-sm text-muted-foreground">Time Remaining</p>
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-border">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-muted-foreground">Progress</span>
                            <span id="progress-text" class="font-semibold text-foreground">0/{{ $total_questions }}</span>
                        </div>
                        <div class="w-full bg-secondary rounded-full h-2">
                            <div id="progress-bar" class="bg-primary h-2 rounded-full transition-all" style="width: 0%"></div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-2 mt-3">
                            <div class="bg-success/10 border border-success/20 rounded p-2 text-center">
                                <div id="answered-count" class="text-xl font-bold text-success">0</div>
                                <div class="text-xs text-muted-foreground">Answered</div>
                            </div>
                            <div class="bg-warning/10 border border-warning/20 rounded p-2 text-center">
                                <div id="unanswered-count" class="text-xl font-bold text-warning">{{ $total_questions }}</div>
                                <div class="text-xs text-muted-foreground">Remaining</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Question Grid -->
                <div class="bg-card rounded-lg shadow p-6 border border-border">
                    <h3 class="font-semibold text-foreground mb-3 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        Questions
                    </h3>
                    <div class="grid grid-cols-5 gap-2">
                        @foreach($questions as $index => $question)
                        <button type="button" onclick="goToQuestion({{ $index }})" 
                                id="nav-{{ $index }}"
                                class="w-full aspect-square rounded text-sm font-bold transition
                                       {{ isset($answers[$question->id]) ? 'bg-success text-success-foreground' : 'bg-secondary text-secondary-foreground hover:bg-accent border border-border' }}">
                            {{ $index + 1 }}
                        </button>
                        @endforeach
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-border space-y-2 text-xs">
                        <div class="flex items-center text-muted-foreground">
                            <div class="w-4 h-4 bg-success rounded mr-2"></div>
                            Answered
                        </div>
                        <div class="flex items-center text-muted-foreground">
                            <div class="w-4 h-4 bg-secondary border border-border rounded mr-2"></div>
                            Not Answered
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="lg:col-span-3">
                <!-- Header -->
                <div class="bg-card rounded-lg shadow p-6 mb-6 border border-border">
                    <div class="flex justify-between items-start">
                        <div>
                            <h1 class="text-2xl font-bold text-foreground mb-1">{{ $exam->title }}</h1>
                            <p class="text-muted-foreground">
                                {{ $exam->type === 'mcq' ? 'Multiple Choice Questions' : 'Exam' }} • {{ $total_questions }} Questions
                            </p>
                        </div>
                        <div class="bg-primary/10 border border-primary/20 rounded-lg px-4 py-3 text-center">
                            <div class="text-2xl font-bold text-primary">{{ $exam->total_marks }}</div>
                            <div class="text-xs text-muted-foreground">Total Marks</div>
                        </div>
                    </div>
                </div>

                <!-- Questions -->
                <form id="exam-form" method="POST" action="{{ route('student.exams.submit', $exam) }}">
                    @csrf
                    
                    @foreach($questions as $index => $question)
                    <div id="question-{{ $index }}" class="question-panel bg-card rounded-lg shadow p-6 mb-6 border border-border {{ $index > 0 ? 'hidden' : '' }}">
                        
                        <!-- Question Header -->
                        <div class="flex justify-between items-center mb-6 pb-4 border-b border-border">
                            <div class="flex items-center gap-3">
                                <span class="bg-primary text-primary-foreground px-4 py-2 rounded-lg font-bold">
                                    Question {{ $index + 1 }}
                                </span>
                                <span class="text-muted-foreground">of {{ $total_questions }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-warning" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                <span class="font-semibold text-warning">{{ $question->marks ?? 1 }} marks</span>
                            </div>
                        </div>
                        
                        <!-- Question Text -->
                        <div class="mb-6">
                            <h2 class="text-xl font-semibold text-foreground leading-relaxed">
                                {{ $question->question_text ?? 'Question text not available' }}
                            </h2>
                        </div>
                        
                        <!-- Options -->
                        @if($question->type === 'mcq' && $question->options)
                        <div class="space-y-3">
                            @foreach(['A', 'B', 'C', 'D'] as $optKey)
                                @php 
                                    $optionValue = $question->options[$optKey] ?? null;
                                @endphp
                                
                                @if($optionValue)
                                <label class="flex items-start p-4 bg-secondary rounded-lg cursor-pointer hover:bg-accent border-2 border-border hover:border-primary transition option-label">
                                    <input type="radio" 
                                           name="answers[{{ $question->id }}]" 
                                           value="{{ $optKey }}"
                                           class="mt-1 w-5 h-5 text-primary focus:ring-primary answer-input"
                                           data-question-id="{{ $question->id }}"
                                           data-question-index="{{ $index }}"
                                           {{ ($answers[$question->id] ?? '') === $optKey ? 'checked' : '' }}>
                                    <div class="ml-3 flex-1">
                                        <div class="flex items-start">
                                            <span class="inline-flex items-center justify-center w-8 h-8 bg-primary text-primary-foreground font-bold rounded-lg mr-3 flex-shrink-0">
                                                {{ $optKey }}
                                            </span>
                                            <span class="text-foreground text-base leading-relaxed">
                                                {{ $optionValue }}
                                            </span>
                                        </div>
                                    </div>
                                </label>
                                @endif
                            @endforeach
                        </div>
                        @else
                        <div class="bg-warning/10 border border-warning/20 rounded-lg p-4">
                            <p class="text-warning-foreground">This question type is not supported in this view.</p>
                        </div>
                        @endif

                        <!-- Navigation Buttons -->
                        <div class="flex justify-between items-center mt-8 pt-6 border-t border-border">
                            <button type="button" 
                                    onclick="goToQuestion({{ $index - 1 }})" 
                                    class="flex items-center px-6 py-3 bg-secondary text-secondary-foreground rounded-lg hover:bg-accent font-medium transition {{ $index === 0 ? 'invisible' : '' }}">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                Previous
                            </button>
                            
                            @if($index === $total_questions - 1)
                            <button type="submit" 
                                    class="flex items-center px-8 py-3 bg-success text-success-foreground rounded-lg hover:opacity-90 font-bold shadow transition">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Submit Exam
                            </button>
                            @else
                            <button type="button" 
                                    onclick="goToQuestion({{ $index + 1 }})" 
                                    class="flex items-center px-6 py-3 bg-primary text-primary-foreground rounded-lg hover:opacity-90 font-medium shadow transition">
                                Next
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let remainingTime = {{ $remaining_time ?? 0 }};
let currentQuestion = 0;
const totalQuestions = {{ $total_questions }};
const saveAnswerUrl = '{{ route("student.exams.save-answer", $attempt) }}';
const csrfToken = '{{ csrf_token() }}';
let answeredCount = {{ count($answers) }};

updateCounts();

function updateTimer() {
    if (remainingTime <= 0) {
        alert('Time is up! Submitting exam...');
        document.getElementById('exam-form').submit();
        return;
    }
    
    remainingTime--;
    const minutes = Math.floor(remainingTime / 60);
    const seconds = remainingTime % 60;
    const timerEl = document.getElementById('timer');
    timerEl.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
    
    if (remainingTime <= 60) {
        timerEl.className = 'text-3xl font-bold text-destructive mb-1';
    } else if (remainingTime <= 300) {
        timerEl.className = 'text-3xl font-bold text-warning mb-1';
    }
}

setInterval(updateTimer, 1000);
updateTimer();

function goToQuestion(index) {
    if (index < 0 || index >= totalQuestions) return;
    
    document.querySelectorAll('.question-panel').forEach((el, i) => {
        if (i === index) {
            el.classList.remove('hidden');
        } else {
            el.classList.add('hidden');
        }
    });
    
    currentQuestion = index;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function updateCounts() {
    answeredCount = document.querySelectorAll('.answer-input:checked').length;
    const unanswered = totalQuestions - answeredCount;
    
    document.getElementById('answered-count').textContent = answeredCount;
    document.getElementById('unanswered-count').textContent = unanswered;
    document.getElementById('progress-text').textContent = answeredCount + '/' + totalQuestions;
    
    const percentage = (answeredCount / totalQuestions) * 100;
    document.getElementById('progress-bar').style.width = percentage + '%';
}

document.querySelectorAll('.answer-input').forEach(input => {
    input.addEventListener('change', function() {
        const questionId = this.dataset.questionId;
        const questionIndex = this.dataset.questionIndex;
        const answer = this.value;
        
        const navBtn = document.getElementById('nav-' + questionIndex);
        navBtn.className = 'w-full aspect-square rounded text-sm font-bold transition bg-success text-success-foreground';
        
        updateCounts();
        
        const label = this.closest('.option-label');
        if (label) {
            label.style.borderColor = 'hsl(var(--primary))';
            label.style.backgroundColor = 'hsl(var(--accent))';
        }
        
        fetch(saveAnswerUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                question_id: questionId,
                answer: answer
            })
        }).then(res => res.json()).then(data => {
            if (data.remaining_time) {
                remainingTime = data.remaining_time;
            }
        }).catch(err => console.error('Save error:', err));
    });
});

window.onbeforeunload = function() {
    return "Are you sure you want to leave? Your progress will be saved.";
};

document.getElementById('exam-form').onsubmit = function() {
    window.onbeforeunload = null;
};

document.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowLeft') {
        goToQuestion(currentQuestion - 1);
    } else if (e.key === 'ArrowRight') {
        goToQuestion(currentQuestion + 1);
    }
});
</script>
@endsection
