<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Course;
use App\Models\CqSubmission;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Question;
use App\Services\ExamService;
use Illuminate\Http\Request;

class OnlineExamController extends Controller
{
    public function __construct(protected ExamService $examService) {}

    public function index()
    {
        $exams = Exam::with(['batch', 'course'])->latest()->paginate(15);

        return view('dashboard.exams.index', compact('exams'));
    }

    public function mcq()
    {
        $exams = Exam::where('type', 'mcq')->with(['batch', 'course'])->latest()->paginate(15);
        $batches = Batch::active()->get();
        $courses = Course::active()->get();

        return view('dashboard.exams.mcq', compact('exams', 'batches', 'courses'));
    }

    public function cq()
    {
        $exams = Exam::where('type', 'cq')->with(['batch', 'course'])->latest()->paginate(15);
        $batches = Batch::active()->get();
        $courses = Course::active()->get();

        return view('dashboard.exams.cq', compact('exams', 'batches', 'courses'));
    }

    public function live()
    {
        $exams = Exam::where('status', 'live')->with(['batch', 'course'])->get();

        return view('dashboard.exams.live', compact('exams'));
    }

    public function results(Request $request)
    {
        $query = ExamResult::with(['student.user', 'exam']);

        if ($request->exam_id) {
            $query->where('exam_id', $request->exam_id);
        }

        $results = $query->latest()->paginate(20);
        $exams = Exam::all();

        return view('dashboard.exams.results', compact('results', 'exams'));
    }

    public function leaderboard(Request $request)
    {
        $examId = $request->get('exam_id');
        $leaderboard = collect();

        if ($examId) {
            $leaderboard = ExamResult::with(['student.user', 'exam'])
                ->where('exam_id', $examId)
                ->orderByDesc('obtained_marks')
                ->get();
        }

        $exams = Exam::whereHas('results')->withCount('results')->with('results')->get();

        return view('dashboard.exams.leaderboard', compact('leaderboard', 'exams', 'examId'));
    }

    public function create()
    {
        $batches = Batch::active()->get();
        $courses = Course::active()->get();

        return view('dashboard.exams.create', compact('batches', 'courses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:mcq,cq',
            'batch_id' => 'required|exists:batches,id',
            'course_id' => 'nullable|exists:courses,id',
            'total_marks' => 'required|integer|min:1',
            'pass_marks' => 'required|integer|min:0',
            'duration_minutes' => 'required|integer|min:1',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
            'instructions' => 'nullable|string',
        ]);

        $exam = $request->type === 'mcq'
            ? $this->examService->createMcqExam($request->all())
            : $this->examService->createCqExam($request->all());

        return redirect()->route('dashboard.exams.show', $exam)
            ->with('success', 'Exam created successfully.');
    }

    public function show(Exam $exam)
    {
        $exam->load(['batch', 'course', 'questions', 'results.student']);

        return view('dashboard.exams.show', compact('exam'));
    }

    public function edit(Exam $exam)
    {
        $batches = Batch::active()->get();
        $courses = Course::active()->get();

        return view('dashboard.exams.edit', compact('exam', 'batches', 'courses'));
    }

    public function update(Request $request, Exam $exam)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'total_marks' => 'required|integer|min:1',
            'pass_marks' => 'required|integer|min:0',
            'duration_minutes' => 'required|integer|min:1',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
            'instructions' => 'nullable|string',
            'status' => 'required|in:draft,scheduled,live,completed',
        ]);

        $exam->update($request->only([
            'title', 'total_marks', 'pass_marks', 'duration_minutes',
            'start_time', 'end_time', 'instructions', 'status',
        ]));

        return redirect()->route('dashboard.exams.show', $exam)
            ->with('success', 'Exam updated successfully.');
    }

    public function destroy(Exam $exam)
    {
        $exam->questions()->delete();
        $exam->results()->delete();
        $exam->delete();

        return redirect()->route('dashboard.exams.index')
            ->with('success', 'Exam deleted successfully.');
    }

    public function addQuestion(Request $request, Exam $exam)
    {
        $request->validate([
            'question_text' => 'required|string',
            'type' => 'required|in:mcq,cq,true_false,short_answer',
            'options' => 'required_if:type,mcq|array',
            'correct_answer' => 'required_if:type,mcq',
            'marks' => 'required|integer|min:1',
        ]);

        $this->examService->addQuestion($exam->id, $request->all());

        return back()->with('success', 'Question added successfully.');
    }

    public function storeQuestion(Request $request, Exam $exam)
    {
        $request->validate([
            'question_text' => 'required|string',
            'type' => 'required|in:mcq,cq,true_false,short_answer',
            'options' => 'required_if:type,mcq|array|min:2',
            'options.*' => 'required_if:type,mcq|string',
            'correct_answer' => 'required_if:type,mcq|string',
            'marks' => 'required|integer|min:1',
        ]);

        $data = $request->only(['question_text', 'type', 'marks']);

        if ($request->type === 'mcq') {
            // Filter out empty options
            $options = array_filter($request->options, fn ($opt) => ! empty(trim($opt)));
            $data['options'] = array_values($options);
            $data['correct_answer'] = $request->correct_answer;
        }

        $exam->questions()->create($data);

        return redirect()->route('dashboard.exams.show', $exam)
            ->with('success', 'Question added successfully.');
    }

    public function getQuestion(Exam $exam, Question $question)
    {
        try {
            // Ensure the question belongs to this exam
            if ($question->exam_id !== $exam->id) {
                return response()->json(['error' => 'Question not found'], 404);
            }

            return response()->json($question);
        } catch (\Exception $e) {
            \Log::error('Error fetching question: '.$e->getMessage());

            return response()->json(['error' => 'Failed to load question: '.$e->getMessage()], 500);
        }
    }

    public function updateQuestion(Request $request, Exam $exam, Question $question)
    {
        $request->validate([
            'question_text' => 'required|string',
            'type' => 'required|in:mcq,cq,true_false,short_answer',
            'options' => 'required_if:type,mcq|array|min:2',
            'options.*' => 'required_if:type,mcq|string',
            'correct_answer' => 'required_if:type,mcq|string',
            'marks' => 'required|integer|min:1',
        ]);

        // Ensure the question belongs to this exam
        if ($question->exam_id !== $exam->id) {
            abort(404);
        }

        $data = $request->only(['question_text', 'type', 'marks']);

        if ($request->type === 'mcq') {
            // Filter out empty options
            $options = array_filter($request->options, fn ($opt) => ! empty(trim($opt)));
            $data['options'] = array_values($options);
            $data['correct_answer'] = $request->correct_answer;
        } else {
            $data['options'] = null;
            $data['correct_answer'] = null;
        }

        $question->update($data);

        return redirect()->route('dashboard.exams.show', $exam)
            ->with('success', 'Question updated successfully.');
    }

    public function destroyQuestion(Exam $exam, Question $question)
    {
        // Ensure the question belongs to this exam
        if ($question->exam_id !== $exam->id) {
            abort(404);
        }

        $question->delete();

        return redirect()->route('dashboard.exams.show', $exam)
            ->with('success', 'Question deleted successfully.');
    }

    // Export Results
    public function exportResults(Request $request, $examId)
    {
        $exam = Exam::with('results.student')->findOrFail($examId);
        $format = $request->get('format', 'excel');
        $filename = 'results-'.$exam->id.'-'.date('Y-m-d');

        if ($format === 'json') {
            $results = $exam->results->map(function ($r) use ($exam) {
                return [
                    'student_name' => $r->student->name_bn ?? $r->student->user->name ?? 'N/A',
                    'student_id' => $r->student->registration_no ?? 'N/A',
                    'obtained_marks' => $r->obtained_marks,
                    'total_marks' => $r->total_marks,
                    'percentage' => ($r->obtained_marks / $r->total_marks) * 100,
                    'grade' => $r->grade,
                    'status' => $r->obtained_marks >= $exam->pass_marks ? 'Passed' : 'Failed',
                ];
            });

            return response()->json($results)
                ->header('Content-Type', 'application/json')
                ->header('Content-Disposition', 'attachment; filename="'.$filename.'.json"');
        }

        // For Excel/CSV, we'll create a simple array export
        $data = [];
        $data[] = ['Student Name', 'Student ID', 'Obtained Marks', 'Total Marks', 'Percentage', 'Grade', 'Status'];

        foreach ($exam->results as $result) {
            $data[] = [
                $result->student->name_bn ?? $result->student->user->name ?? 'N/A',
                $result->student->registration_no ?? 'N/A',
                $result->obtained_marks,
                $result->total_marks,
                number_format(($result->obtained_marks / $result->total_marks) * 100, 2).'%',
                $result->grade,
                $result->obtained_marks >= $exam->pass_marks ? 'Passed' : 'Failed',
            ];
        }

        if ($format === 'csv') {
            $callback = function () use ($data) {
                $file = fopen('php://output', 'w');
                foreach ($data as $row) {
                    fputcsv($file, $row);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="'.$filename.'.csv"',
            ]);
        }

        // Simple Excel export using HTML table
        $html = '<table><thead><tr>';
        foreach ($data[0] as $header) {
            $html .= '<th>'.htmlspecialchars($header).'</th>';
        }
        $html .= '</tr></thead><tbody>';

        for ($i = 1; $i < count($data); $i++) {
            $html .= '<tr>';
            foreach ($data[$i] as $cell) {
                $html .= '<td>'.htmlspecialchars($cell).'</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.xls"',
        ]);
    }

    /**
     * Confirm a nested resource really belongs to the exam in the URL.
     *
     * Route model binding resolves {exam} and {result} independently, so without
     * this any result in the database could be read, edited or deleted through
     * any exam's URL — and updateResult() would validate the mark against the
     * wrong exam's total_marks, defeating that clamp entirely.
     *
     * The sibling methods (getQuestion, updateQuestion, destroyQuestion,
     * reviewSubmission, saveReview) already did this; these four did not.
     */
    private function assertResultBelongsToExam(Exam $exam, ExamResult $result): void
    {
        abort_if($result->exam_id !== $exam->id, 404, 'Result not found for this exam.');
    }

    // View Result
    public function viewResult(Exam $exam, ExamResult $result)
    {
        $this->assertResultBelongsToExam($exam, $result);

        $result->load('student', 'exam');

        return view('dashboard.exams.view-result', compact('exam', 'result'));
    }

    // Edit Result
    public function editResult(Exam $exam, ExamResult $result)
    {
        $this->assertResultBelongsToExam($exam, $result);

        $result->load('student', 'exam');

        return view('dashboard.exams.edit-result', compact('exam', 'result'));
    }

    // Update Result
    public function updateResult(Request $request, Exam $exam, ExamResult $result)
    {
        $this->assertResultBelongsToExam($exam, $result);

        $request->validate([
            'obtained_marks' => 'required|numeric|min:0|max:'.$exam->total_marks,
            'grade' => 'nullable|string',
        ]);

        // Auto-calculate grade if not provided, using the institute's single
        // scale so a manual edit agrees with the auto-scored rows.
        $grade = $request->grade;
        if (empty($grade)) {
            $percentage = $exam->total_marks > 0
                ? ((float) $request->obtained_marks / $exam->total_marks) * 100
                : 0.0;

            $grade = ExamResult::gradeForPercentage($percentage);
        }

        // `marks` is the legacy column the downloadable mark sheet prints.
        // Updating only obtained_marks left the PDF showing the original number
        // after an admin corrected the mark in the UI.
        $result->update([
            'obtained_marks' => $request->obtained_marks,
            'marks' => $request->obtained_marks,
            'total_marks' => $exam->total_marks,
            'grade' => $grade,
        ]);

        return redirect()->route('dashboard.exams.show', $exam)
            ->with('success', 'Result updated successfully!');
    }

    // Delete Result
    public function deleteResult(Exam $exam, ExamResult $result)
    {
        $this->assertResultBelongsToExam($exam, $result);

        $result->delete();

        return redirect()->route('dashboard.exams.show', $exam)
            ->with('success', 'Result deleted successfully!');
    }

    // Import Questions
    public function importQuestions(Exam $exam)
    {
        return view('dashboard.exams.import-questions', compact('exam'));
    }

    // Process Import
    public function processImport(Request $request, Exam $exam)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,json|max:10240',
            'format' => 'required|in:excel,csv,json',
        ]);

        try {
            $file = $request->file('file');
            $format = $request->format;

            if ($format === 'json') {
                $content = file_get_contents($file->getRealPath());
                $questions = json_decode($content, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Invalid JSON format');
                }

                foreach ($questions as $questionData) {
                    $exam->questions()->create([
                        'question_text' => $questionData['question_text'],
                        'type' => $questionData['type'],
                        'marks' => $questionData['marks'] ?? 1,
                        'options' => $questionData['options'] ?? null,
                        'correct_answer' => $questionData['correct_answer'] ?? null,
                    ]);
                }
            } else {
                // CSV/Excel import
                $handle = fopen($file->getRealPath(), 'r');
                $header = fgetcsv($handle); // Skip header row

                while (($row = fgetcsv($handle)) !== false) {
                    if (count($row) < 3) {
                        continue;
                    } // Skip invalid rows

                    $options = null;
                    if ($row[1] === 'mcq' && isset($row[3])) {
                        $options = array_filter([
                            $row[3] ?? null,
                            $row[4] ?? null,
                            $row[5] ?? null,
                            $row[6] ?? null,
                        ]);
                    }

                    $exam->questions()->create([
                        'question_text' => $row[0],
                        'type' => $row[1],
                        'marks' => $row[2] ?? 1,
                        'options' => $options,
                        'correct_answer' => $row[7] ?? null,
                    ]);
                }
                fclose($handle);
            }

            return redirect()->route('dashboard.exams.show', $exam)
                ->with('success', 'Questions imported successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Import failed: '.$e->getMessage()]);
        }
    }

    // Export Questions
    public function exportQuestions(Request $request, Exam $exam)
    {
        $format = $request->get('format', 'excel');
        $filename = 'questions-'.$exam->id.'-'.date('Y-m-d');

        if ($format === 'json') {
            $questions = $exam->questions->map(function ($q) {
                return [
                    'question_text' => $q->question_text,
                    'type' => $q->type,
                    'marks' => $q->marks,
                    'options' => $q->options,
                    'correct_answer' => $q->correct_answer,
                ];
            });

            return response()->json($questions)
                ->header('Content-Type', 'application/json')
                ->header('Content-Disposition', 'attachment; filename="'.$filename.'.json"');
        }

        // Prepare data
        $data = [];
        $data[] = ['Question Text', 'Type', 'Marks', 'Option A', 'Option B', 'Option C', 'Option D', 'Correct Answer'];

        foreach ($exam->questions as $question) {
            $options = $question->options ?? [];
            $data[] = [
                $question->question_text,
                $question->type,
                $question->marks,
                $options[0] ?? '',
                $options[1] ?? '',
                $options[2] ?? '',
                $options[3] ?? '',
                $question->correct_answer ?? '',
            ];
        }

        if ($format === 'csv') {
            $callback = function () use ($data) {
                $file = fopen('php://output', 'w');
                foreach ($data as $row) {
                    fputcsv($file, $row);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="'.$filename.'.csv"',
            ]);
        }

        // Simple Excel export
        $html = '<table><thead><tr>';
        foreach ($data[0] as $header) {
            $html .= '<th>'.htmlspecialchars($header).'</th>';
        }
        $html .= '</tr></thead><tbody>';

        for ($i = 1; $i < count($data); $i++) {
            $html .= '<tr>';
            foreach ($data[$i] as $cell) {
                $html .= '<td>'.htmlspecialchars($cell).'</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.xls"',
        ]);
    }

    // Download Template
    public function downloadTemplate(Request $request)
    {
        $format = $request->get('format', 'excel');
        $filename = 'questions-template';

        $sampleData = [
            ['Question Text', 'Type', 'Marks', 'Option A', 'Option B', 'Option C', 'Option D', 'Correct Answer'],
            ['What is 2+2?', 'mcq', '1', '3', '4', '5', '6', '4'],
            ['Explain photosynthesis', 'long', '5', '', '', '', '', ''],
        ];

        if ($format === 'json') {
            $jsonData = [
                [
                    'question_text' => 'What is 2+2?',
                    'type' => 'mcq',
                    'marks' => 1,
                    'options' => ['3', '4', '5', '6'],
                    'correct_answer' => '4',
                ],
                [
                    'question_text' => 'Explain photosynthesis',
                    'type' => 'long',
                    'marks' => 5,
                ],
            ];

            return response()->json($jsonData)
                ->header('Content-Type', 'application/json')
                ->header('Content-Disposition', 'attachment; filename="'.$filename.'.json"');
        }

        if ($format === 'csv') {
            $callback = function () use ($sampleData) {
                $file = fopen('php://output', 'w');
                foreach ($sampleData as $row) {
                    fputcsv($file, $row);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="'.$filename.'.csv"',
            ]);
        }

        // Excel template
        $html = '<table><thead><tr>';
        foreach ($sampleData[0] as $header) {
            $html .= '<th>'.htmlspecialchars($header).'</th>';
        }
        $html .= '</tr></thead><tbody>';

        for ($i = 1; $i < count($sampleData); $i++) {
            $html .= '<tr>';
            foreach ($sampleData[$i] as $cell) {
                $html .= '<td>'.htmlspecialchars($cell).'</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.xls"',
        ]);
    }

    // Review Submissions List
    public function reviewSubmissions(Exam $exam)
    {
        $base = CqSubmission::where('exam_id', $exam->id);

        // Paginated: the view renders ->total() and ->links(), which only exist
        // on a paginator (a plain get() collection made the page 500).
        $submissions = (clone $base)
            ->with(['student.user'])
            ->latest('submitted_at')
            ->paginate(20);

        // Summary tiles. These were never passed, so the view died with
        // "Undefined variable $pendingCount".
        $pendingCount = (clone $base)->whereNull('evaluated_at')->count();
        $reviewedCount = (clone $base)->whereNotNull('evaluated_at')->count();

        // The tile renders this as a percentage, so express it against the exam's
        // total marks rather than as a raw average.
        $averageScore = 0.0;
        $averageMarks = (clone $base)->whereNotNull('marks')->avg('marks');
        if ($averageMarks !== null && (float) $exam->total_marks > 0) {
            $averageScore = round((float) $averageMarks / (float) $exam->total_marks * 100, 1);
        }

        return view('dashboard.exams.review-submissions', compact(
            'exam',
            'submissions',
            'pendingCount',
            'reviewedCount',
            'averageScore'
        ));
    }

    // Review Single Submission
    public function reviewSubmission(Exam $exam, $submission)
    {
        $submission = CqSubmission::findOrFail($submission);

        if ($submission->exam_id !== $exam->id) {
            abort(404);
        }

        return view('dashboard.exams.review-single', compact('exam', 'submission'));
    }

    // Save Review
    public function saveReview(Request $request, Exam $exam, $submission)
    {
        $submission = CqSubmission::findOrFail($submission);

        if ($submission->exam_id !== $exam->id) {
            abort(404);
        }

        $request->validate([
            'marks' => 'required|numeric|min:0|max:'.$exam->total_marks,
            'feedback' => 'nullable|string',
            'teacher_notes' => 'nullable|string',
            'annotated_files' => 'nullable|string',
        ]);

        $submission->update([
            'marks' => $request->marks,
            'feedback' => $request->feedback,
            'teacher_notes' => $request->teacher_notes,
            'annotated_files' => $request->annotated_files ? json_decode($request->annotated_files, true) : null,
            'evaluated_at' => now(),
            'evaluated_by' => auth()->id(),
        ]);

        return redirect()->route('dashboard.exams.review-submissions', $exam)
            ->with('success', 'Review saved successfully!');
    }
}
