<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Payment;
use App\Models\Student;
use App\Models\ExamResult;
use App\Models\Batch;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Exception;

class ReportService
{
    /**
     * Cache TTL in seconds (15 minutes).
     */
    protected const CACHE_TTL = 900;

    /**
     * Generate attendance report with filters.
     * 
     * @param array $filters Filter criteria
     * @return array Report data with summary and chart data
     */
    public function generateAttendanceReport(array $filters): array
    {
        $cacheKey = 'attendance_report_' . md5(json_encode($filters));

        try {
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters) {
                $query = Attendance::with(['student.user', 'batch']);

                if (!empty($filters['batch_id'])) {
                    $query->where('batch_id', $filters['batch_id']);
                }

                if (!empty($filters['student_id'])) {
                    $query->where('student_id', $filters['student_id']);
                }

                if (!empty($filters['start_date'])) {
                    $query->where('date', '>=', $filters['start_date']);
                }

                if (!empty($filters['end_date'])) {
                    $query->where('date', '<=', $filters['end_date']);
                }

                if (!empty($filters['status'])) {
                    $query->where('status', $filters['status']);
                }

                $attendances = $query->orderBy('date', 'desc')->get();

                // Transform to array format for the view
                $transformedData = $attendances->map(function($attendance) {
                    return [
                        'id' => $attendance->id,
                        'student_id' => $attendance->student_id,
                        'student_name' => $attendance->student ? $attendance->student->name : 'N/A',
                        'registration_no' => $attendance->student ? $attendance->student->registration_no : '',
                        'batch_id' => $attendance->batch_id,
                        'batch_name' => $attendance->batch ? $attendance->batch->name : 'N/A',
                        'date' => $attendance->date,
                        'status' => $attendance->status,
                        'check_in_time' => $attendance->check_in_time ?? '-',
                        'remarks' => $attendance->remarks ?? '-',
                    ];
                });

                // Calculate summary
                $summary = [
                    'total_records' => $attendances->count(),
                    'total_present' => $attendances->where('status', 'present')->count(),
                    'total_absent' => $attendances->where('status', 'absent')->count(),
                    'total_late' => $attendances->where('status', 'late')->count(),
                    'total_excused' => $attendances->where('status', 'excused')->count(),
                    'attendance_rate' => $attendances->count() > 0 
                        ? round(($attendances->where('status', 'present')->count() / $attendances->count()) * 100, 2)
                        : 0,
                ];

                // Group by date for chart data
                $byDate = $attendances->groupBy(fn($a) => $a->date->format('Y-m-d'))
                    ->map(fn($group) => [
                        'present' => $group->where('status', 'present')->count(),
                        'absent' => $group->where('status', 'absent')->count(),
                        'late' => $group->where('status', 'late')->count(),
                    ]);

                return [
                    'data' => $transformedData,
                    'summary' => $summary,
                    'chart_data' => $byDate,
                ];
            });
        } catch (QueryException $e) {
            Log::error('Database error generating attendance report', [
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);
            return [
                'data' => collect(),
                'summary' => [
                    'total_records' => 0,
                    'total_present' => 0,
                    'total_absent' => 0,
                    'total_late' => 0,
                    'total_excused' => 0,
                    'attendance_rate' => 0,
                ],
                'chart_data' => collect(),
                'error' => 'Unable to generate attendance report. Please try again.',
            ];
        } catch (Exception $e) {
            Log::error('Failed to generate attendance report', [
                'error' => $e->getMessage(),
            ]);
            return [
                'data' => collect(),
                'summary' => [
                    'total_records' => 0,
                    'total_present' => 0,
                    'total_absent' => 0,
                    'total_late' => 0,
                    'total_excused' => 0,
                    'attendance_rate' => 0,
                ],
                'chart_data' => collect(),
                'error' => 'Unable to generate attendance report. Please try again.',
            ];
        }
    }

    /**
     * Generate payment report with filters.
     * 
     * @param array $filters Filter criteria
     * @return array Report data with summary and chart data
     */
    public function generatePaymentReport(array $filters): array
    {
        $cacheKey = 'payment_report_' . md5(json_encode($filters));

        try {
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters) {
                $query = Payment::with(['student.user']);

                if (!empty($filters['student_id'])) {
                    $query->where('student_id', $filters['student_id']);
                }

                if (!empty($filters['batch_id'])) {
                    $query->whereHas('student', function ($q) use ($filters) {
                        $q->where('batch_id', $filters['batch_id']);
                    });
                }

                if (!empty($filters['start_date'])) {
                    $query->where('payment_date', '>=', $filters['start_date']);
                }

                if (!empty($filters['end_date'])) {
                    $query->where('payment_date', '<=', $filters['end_date']);
                }

                if (!empty($filters['status'])) {
                    $query->where('status', $filters['status']);
                }

                if (!empty($filters['payment_method'])) {
                    $query->where('payment_method', $filters['payment_method']);
                }

                $payments = $query->orderBy('payment_date', 'desc')->get();

                // Calculate summary
                $completed = $payments->whereIn('status', Payment::settledStatuses());
                $summary = [
                    'total_amount' => $completed->sum('amount'),
                    'total_count' => $completed->count(),
                    'average_amount' => $completed->count() > 0 ? $completed->avg('amount') : 0,
                    'by_method' => $completed->groupBy('payment_method')
                        ->map(fn($group) => [
                            'count' => $group->count(),
                            'amount' => $group->sum('amount'),
                        ]),
                ];

                // Group by date for chart data
                $byDate = $completed->groupBy(fn($p) => $p->payment_date->format('Y-m-d'))
                    ->map(fn($group) => $group->sum('amount'));

                return [
                    'data' => $payments,
                    'summary' => $summary,
                    'chart_data' => $byDate,
                ];
            });
        } catch (QueryException $e) {
            Log::error('Database error generating payment report', [
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);
            return [
                'data' => collect(),
                'summary' => [
                    'total_amount' => 0,
                    'total_count' => 0,
                    'average_amount' => 0,
                    'by_method' => collect(),
                ],
                'chart_data' => collect(),
                'error' => 'Unable to generate payment report. Please try again.',
            ];
        } catch (Exception $e) {
            Log::error('Failed to generate payment report', [
                'error' => $e->getMessage(),
            ]);
            return [
                'data' => collect(),
                'summary' => [
                    'total_amount' => 0,
                    'total_count' => 0,
                    'average_amount' => 0,
                    'by_method' => collect(),
                ],
                'chart_data' => collect(),
                'error' => 'Unable to generate payment report. Please try again.',
            ];
        }
    }

    /**
     * Generate performance report with filters.
     * 
     * @param array $filters Filter criteria
     * @return array Report data with summary
     */
    public function generatePerformanceReport(array $filters): array
    {
        $cacheKey = 'performance_report_' . md5(json_encode($filters));

        try {
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters) {
                $query = ExamResult::with(['student.user', 'exam']);

                if (!empty($filters['student_id'])) {
                    $query->where('student_id', $filters['student_id']);
                }

                if (!empty($filters['exam_id'])) {
                    $query->where('exam_id', $filters['exam_id']);
                }

                if (!empty($filters['batch_id'])) {
                    $query->whereHas('student', function ($q) use ($filters) {
                        $q->where('batch_id', $filters['batch_id']);
                    });
                }

                $results = $query->orderBy('created_at', 'desc')->get();

                // Calculate summary
                $summary = [
                    'total_exams' => $results->pluck('exam_id')->unique()->count(),
                    'total_students' => $results->pluck('student_id')->unique()->count(),
                    'average_score' => $results->count() > 0 ? round($results->avg('obtained_marks'), 2) : 0,
                    'pass_rate' => $this->calculatePassRate($results),
                    'grade_distribution' => $results->groupBy('grade')
                        ->map(fn($group) => $group->count()),
                ];

                return [
                    'data' => $results,
                    'summary' => $summary,
                ];
            });
        } catch (QueryException $e) {
            Log::error('Database error generating performance report', [
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);
            return [
                'data' => collect(),
                'summary' => [
                    'total_exams' => 0,
                    'total_students' => 0,
                    'average_score' => 0,
                    'pass_rate' => 0,
                    'grade_distribution' => collect(),
                ],
                'error' => 'Unable to generate performance report. Please try again.',
            ];
        } catch (Exception $e) {
            Log::error('Failed to generate performance report', [
                'error' => $e->getMessage(),
            ]);
            return [
                'data' => collect(),
                'summary' => [
                    'total_exams' => 0,
                    'total_students' => 0,
                    'average_score' => 0,
                    'pass_rate' => 0,
                    'grade_distribution' => collect(),
                ],
                'error' => 'Unable to generate performance report. Please try again.',
            ];
        }
    }

    /**
     * Calculate pass rate from results.
     */
    protected function calculatePassRate(Collection $results): float
    {
        if ($results->isEmpty()) {
            return 0;
        }

        $passed = $results->filter(function ($result) {
            return $result->exam && $result->obtained_marks >= $result->exam->pass_marks;
        })->count();

        return round(($passed / $results->count()) * 100, 2);
    }

    /**
     * Get chart data for dashboard.
     * 
     * @param string $chartType Type of chart
     * @param array $filters Filter criteria
     * @return array Chart data
     */
    public function getChartData(string $chartType, array $filters = []): array
    {
        try {
            return match ($chartType) {
                'enrollment' => $this->getEnrollmentChartData($filters),
                'attendance' => $this->getAttendanceChartData($filters),
                'payment' => $this->getPaymentChartData($filters),
                'performance' => $this->getPerformanceChartData($filters),
                default => [],
            };
        } catch (QueryException $e) {
            Log::error('Database error getting chart data', [
                'chart_type' => $chartType,
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);
            return [
                'labels' => [],
                'data' => [],
                'error' => 'Unable to load chart data. Please try again.',
            ];
        } catch (Exception $e) {
            Log::error('Failed to get chart data', [
                'chart_type' => $chartType,
                'error' => $e->getMessage(),
            ]);
            return [
                'labels' => [],
                'data' => [],
                'error' => 'Unable to load chart data. Please try again.',
            ];
        }
    }

    /**
     * Get enrollment chart data.
     * 
     * @param array $filters Filter criteria
     * @return array Chart data
     */
    protected function getEnrollmentChartData(array $filters): array
    {
        try {
            $months = collect();
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $startOfMonth = $date->copy()->startOfMonth();
                $endOfMonth = $date->copy()->endOfMonth();
                $count = Student::whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->count();
                $months->put($date->format('M Y'), $count);
            }

            return [
                'labels' => $months->keys()->toArray(),
                'data' => $months->values()->toArray(),
            ];
        } catch (QueryException $e) {
            Log::error('Database error getting enrollment chart data', [
                'error' => $e->getMessage(),
            ]);
            return ['labels' => [], 'data' => []];
        }
    }

    /**
     * Get attendance chart data.
     * 
     * @param array $filters Filter criteria
     * @return array Chart data
     */
    protected function getAttendanceChartData(array $filters): array
    {
        try {
            $days = collect();
            for ($i = 29; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $attendances = Attendance::where('date', $date->toDateString())->get();
                $days->put($date->format('M d'), [
                    'present' => $attendances->where('status', 'present')->count(),
                    'absent' => $attendances->where('status', 'absent')->count(),
                ]);
            }

            return [
                'labels' => $days->keys()->toArray(),
                'present' => $days->pluck('present')->toArray(),
                'absent' => $days->pluck('absent')->toArray(),
            ];
        } catch (QueryException $e) {
            Log::error('Database error getting attendance chart data', [
                'error' => $e->getMessage(),
            ]);
            return ['labels' => [], 'present' => [], 'absent' => []];
        }
    }

    /**
     * Get payment chart data.
     * 
     * @param array $filters Filter criteria
     * @return array Chart data
     */
    protected function getPaymentChartData(array $filters): array
    {
        try {
            $months = collect();
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $startOfMonth = $date->copy()->startOfMonth();
                $endOfMonth = $date->copy()->endOfMonth();
                $amount = Payment::completed()
                    ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
                    ->sum('amount');
                $months->put($date->format('M Y'), $amount);
            }

            return [
                'labels' => $months->keys()->toArray(),
                'data' => $months->values()->toArray(),
            ];
        } catch (QueryException $e) {
            Log::error('Database error getting payment chart data', [
                'error' => $e->getMessage(),
            ]);
            return ['labels' => [], 'data' => []];
        }
    }

    /**
     * Get performance chart data.
     * 
     * @param array $filters Filter criteria
     * @return array Chart data
     */
    protected function getPerformanceChartData(array $filters): array
    {
        try {
            $grades = ExamResult::select('grade', DB::raw('count(*) as count'))
                ->groupBy('grade')
                ->pluck('count', 'grade')
                ->toArray();

            return [
                'labels' => array_keys($grades),
                'data' => array_values($grades),
            ];
        } catch (QueryException $e) {
            Log::error('Database error getting performance chart data', [
                'error' => $e->getMessage(),
            ]);
            return ['labels' => [], 'data' => []];
        }
    }

    /**
     * Get comprehensive student report data with filtering support.
     * 
     * Retrieves student records with their enrollment, payment, and performance data.
     * Compiles comprehensive information including:
     * - Student personal details and enrollment information
     * - Batch and course information
     * - Payment history and balance
     * - Exam results and performance metrics
     * - Attendance summary
     *
     * @param array $filters Supported filters: batch_id, student_id, name, enrollment_status, start_date, end_date
     * @return Collection Collection of students with comprehensive data
     * 
     * Requirements: 8.1, 8.2, 8.5
     */
    public function getStudentReport(array $filters): Collection
    {
        $query = Student::with([
            'user',
            'batch.course',
            'payments' => function ($q) {
                $q->orderBy('payment_date', 'desc');
            },
            'results.exam.course',
            'attendances',
            'invoices',
        ]);

        // Filter by batch
        if (!empty($filters['batch_id'])) {
            $query->where('batch_id', $filters['batch_id']);
        }

        // Filter by specific student
        if (!empty($filters['student_id'])) {
            $query->where('id', $filters['student_id']);
        }

        // Filter by student name (search)
        if (!empty($filters['name'])) {
            $searchTerm = $filters['name'];
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('user', function ($userQuery) use ($searchTerm) {
                    $userQuery->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%");
                })
                ->orWhere('registration_no', 'like', "%{$searchTerm}%")
                ->orWhere('name_bn', 'like', "%{$searchTerm}%")
                ->orWhere('phone', 'like', "%{$searchTerm}%");
            });
        }

        // Filter by enrollment status (active, inactive, completed, withdrawn)
        if (!empty($filters['enrollment_status'])) {
            $status = $filters['enrollment_status'];
            
            switch ($status) {
                case 'active':
                    // Students in active batches
                    $query->whereHas('batch', function ($q) {
                        $q->where('status', 'active');
                    });
                    break;
                case 'completed':
                    // Students in completed batches
                    $query->whereHas('batch', function ($q) {
                        $q->whereIn('status', Payment::settledStatuses());
                    });
                    break;
                case 'inactive':
                    // Students in inactive batches
                    $query->whereHas('batch', function ($q) {
                        $q->where('status', 'inactive');
                    });
                    break;
                case 'with_dues':
                    // Students with outstanding dues
                    $query->where('due_amount', '>', 0);
                    break;
                case 'paid':
                    // Students with no dues
                    $query->where('due_amount', '<=', 0);
                    break;
            }
        }

        // Filter by enrollment date range - start date
        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        // Filter by enrollment date range - end date
        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        // Get students and compile comprehensive data
        $students = $query->orderBy('created_at', 'desc')->get();

        // Enhance each student with compiled data
        return $students->map(function ($student) {
            return $this->compileStudentData($student);
        });
    }

    /**
     * Compile comprehensive data for a single student.
     * 
     * Aggregates all relevant information for a student including:
     * - Personal and enrollment details
     * - Payment summary and history
     * - Performance metrics and exam results
     * - Attendance statistics
     *
     * @param Student $student The student model with loaded relationships
     * @return array Compiled student data array
     * 
     * Requirements: 8.1, 8.2
     */
    protected function compileStudentData(Student $student): array
    {
        // Basic student information
        $studentData = [
            'id' => $student->id,
            'registration_no' => $student->registration_no,
            'name' => $student->user ? $student->user->name : 'Unknown',
            'name_bn' => $student->name_bn,
            'email' => $student->user ? $student->user->email : null,
            'phone' => $student->phone,
            'profile_image' => $student->profile_image,
            'gender' => $student->gender,
            'dob' => $student->dob ? $student->dob->format('Y-m-d') : null,
            'blood_group' => $student->blood_group,
        ];

        // Enrollment information
        $studentData['enrollment'] = [
            'enrollment_date' => $student->created_at ? $student->created_at->format('Y-m-d') : null,
            'batch_id' => $student->batch_id,
            'batch_name' => $student->batch ? $student->batch->name : null,
            'batch_code' => $student->batch ? $student->batch->code : null,
            'batch_status' => $student->batch ? $student->batch->status : null,
            'course_id' => $student->batch && $student->batch->course ? $student->batch->course->id : null,
            'course_name' => $student->batch && $student->batch->course ? $student->batch->course->name : null,
            'course_code' => $student->batch && $student->batch->course ? $student->batch->course->code : null,
            'class' => $student->class,
        ];

        // Guardian information
        $studentData['guardian'] = [
            'father_name' => $student->father_name,
            'mother_name' => $student->mother_name,
            'father_phone' => $student->father_phone,
            'mother_phone' => $student->mother_phone,
            'guardian_name' => $student->guardian_name,
            'guardian_phone' => $student->guardian_phone,
        ];

        // Payment summary
        $payments = $student->payments ?? collect();
        $completedPayments = $payments->whereIn('status', Payment::settledStatuses());
        
        $studentData['payment_summary'] = [
            'total_amount' => (float) $student->total_amount,
            'paid_amount' => (float) $student->paid_amount,
            'due_amount' => (float) $student->due_amount,
            'total_payments' => $payments->count(),
            'completed_payments' => $completedPayments->count(),
            'pending_payments' => $payments->where('status', 'pending')->count(),
            'last_payment_date' => $completedPayments->first() && $completedPayments->first()->payment_date
                ? $completedPayments->first()->payment_date->format('Y-m-d') 
                : null,
            'last_payment_amount' => $completedPayments->first() 
                ? (float) $completedPayments->first()->amount 
                : null,
            'payment_methods_used' => $completedPayments->pluck('payment_method')->unique()->values()->toArray(),
        ];

        // Payment history (last 10 payments)
        $studentData['payment_history'] = $payments->take(10)->map(function ($payment) {
            return [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'payment_method' => $payment->payment_method,
                'payment_date' => $payment->payment_date ? $payment->payment_date->format('Y-m-d') : null,
                'receipt_number' => $payment->receipt_number,
                'transaction_id' => $payment->transaction_id,
                'status' => $payment->status,
                'notes' => $payment->notes,
            ];
        })->values()->toArray();

        // Performance summary
        $results = $student->results ?? collect();
        
        $studentData['performance_summary'] = [
            'total_exams' => $results->pluck('exam_id')->unique()->count(),
            'average_score' => $results->count() > 0 ? round($results->avg('obtained_marks'), 2) : 0,
            'average_percentage' => $results->count() > 0 
                ? round($results->avg(function ($result) {
                    if (!$result->total_marks || $result->total_marks === 0) {
                        return 0;
                    }
                    return ($result->obtained_marks / $result->total_marks) * 100;
                }), 2) 
                : 0,
            'highest_score' => $results->max('obtained_marks') ?? 0,
            'lowest_score' => $results->count() > 0 ? ($results->min('obtained_marks') ?? 0) : 0,
            'pass_count' => $results->filter(function ($result) {
                return $result->exam && $result->obtained_marks >= $result->exam->pass_marks;
            })->count(),
            'fail_count' => $results->filter(function ($result) {
                return $result->exam && $result->obtained_marks < $result->exam->pass_marks;
            })->count(),
            'grade_distribution' => $results->groupBy('grade')->map(function ($gradeResults) {
                return $gradeResults->count();
            })->toArray(),
        ];

        // Exam results (all results)
        $studentData['exam_results'] = $results->map(function ($result) {
            return [
                'id' => $result->id,
                'exam_id' => $result->exam_id,
                'exam_title' => $result->exam ? $result->exam->title : 'Unknown',
                'course_name' => $result->exam && $result->exam->course 
                    ? $result->exam->course->name 
                    : 'Unknown',
                'obtained_marks' => $result->obtained_marks,
                'total_marks' => $result->total_marks,
                'percentage' => $result->total_marks > 0 
                    ? round(($result->obtained_marks / $result->total_marks) * 100, 2) 
                    : 0,
                'grade' => $result->grade,
                'rank' => $result->rank,
                'passed' => $result->exam && $result->obtained_marks >= $result->exam->pass_marks,
                'exam_date' => $result->created_at ? $result->created_at->format('Y-m-d') : null,
                'feedback' => $result->feedback,
            ];
        })->values()->toArray();

        // Attendance summary
        $attendances = $student->attendances ?? collect();
        $totalAttendance = $attendances->count();
        $presentCount = $attendances->where('status', 'present')->count();
        $lateCount = $attendances->where('status', 'late')->count();
        $absentCount = $attendances->where('status', 'absent')->count();
        $excusedCount = $attendances->where('status', 'excused')->count();
        $attendedCount = $presentCount + $lateCount;

        $studentData['attendance_summary'] = [
            'total_days' => $totalAttendance,
            'present_count' => $presentCount,
            'late_count' => $lateCount,
            'absent_count' => $absentCount,
            'excused_count' => $excusedCount,
            'attendance_percentage' => $totalAttendance > 0 
                ? round(($attendedCount / $totalAttendance) * 100, 2) 
                : 0,
            'present_percentage' => $totalAttendance > 0 
                ? round(($presentCount / $totalAttendance) * 100, 2) 
                : 0,
        ];

        // Educational background
        $studentData['education'] = [
            'ssc' => [
                'institute' => $student->ssc_institute,
                'board' => $student->ssc_board,
                'year' => $student->ssc_year,
                'gpa' => $student->ssc_gpa,
                'group' => $student->ssc_group,
            ],
            'hsc' => [
                'institute' => $student->hsc_institute,
                'board' => $student->hsc_board,
                'year' => $student->hsc_year,
                'gpa' => $student->hsc_gpa,
                'group' => $student->hsc_group,
            ],
            'undergrad' => [
                'institute' => $student->undergrad_institute,
                'board' => $student->undergrad_board,
                'year' => $student->undergrad_year,
                'gpa' => $student->undergrad_gpa,
                'group' => $student->undergrad_group,
                'department' => $student->undergrad_department,
            ],
        ];

        // Address information
        $studentData['address'] = [
            'present' => [
                'village' => $student->present_village,
                'post_office' => $student->present_po,
                'police_station' => $student->present_ps,
                'district' => $student->present_dist,
                'holding' => $student->present_holding,
            ],
            'permanent' => [
                'village' => $student->permanent_village,
                'post_office' => $student->permanent_po,
                'police_station' => $student->permanent_ps,
                'district' => $student->permanent_dist,
                'holding' => $student->permanent_holding,
            ],
        ];

        // Invoice summary
        $invoices = $student->invoices ?? collect();
        $studentData['invoice_summary'] = [
            'total_invoices' => $invoices->count(),
            'pending_invoices' => $invoices->where('status', 'pending')->count(),
            'paid_invoices' => $invoices->where('status', 'paid')->count(),
            'overdue_invoices' => $invoices->where('status', 'pending')
                ->filter(function ($invoice) {
                    return $invoice->due_date && $invoice->due_date->isPast();
                })->count(),
        ];

        return $studentData;
    }

    /**
     * Calculate student report statistics from a collection of compiled student data.
     * 
     * Computes aggregate statistics including:
     * - Total students count
     * - Enrollment statistics by batch and status
     * - Payment statistics (total dues, paid amounts)
     * - Performance statistics (average scores, pass rates)
     * - Attendance statistics
     *
     * @param Collection $students Collection of compiled student data arrays
     * @return array Statistics array with aggregated metrics
     * 
     * Requirements: 8.1, 8.5
     */
    public function calculateStudentReportStats(Collection $students): array
    {
        if ($students->isEmpty()) {
            return [
                'total_students' => 0,
                'by_batch' => [],
                'by_enrollment_status' => [],
                'payment_stats' => [
                    'total_fees' => 0,
                    'total_paid' => 0,
                    'total_dues' => 0,
                    'students_with_dues' => 0,
                    'students_fully_paid' => 0,
                    'collection_rate' => 0,
                ],
                'performance_stats' => [
                    'average_score' => 0,
                    'students_above_average' => 0,
                    'students_below_average' => 0,
                ],
                'attendance_stats' => [
                    'average_attendance_percentage' => 0,
                    'students_above_80_percent' => 0,
                    'students_below_50_percent' => 0,
                ],
            ];
        }

        $totalStudents = $students->count();

        // Group by batch
        $byBatch = $students->groupBy(function ($student) {
            return $student['enrollment']['batch_id'] ?? 'unassigned';
        })->map(function ($batchStudents, $batchId) {
            $first = $batchStudents->first();
            return [
                'batch_id' => $batchId,
                'batch_name' => $first['enrollment']['batch_name'] ?? 'Unassigned',
                'count' => $batchStudents->count(),
            ];
        })->values()->toArray();

        // Group by enrollment status
        $byEnrollmentStatus = [
            'active' => $students->filter(function ($student) {
                return ($student['enrollment']['batch_status'] ?? '') === 'active';
            })->count(),
            'completed' => $students->filter(function ($student) {
                return ($student['enrollment']['batch_status'] ?? '') === 'completed';
            })->count(),
            'inactive' => $students->filter(function ($student) {
                return ($student['enrollment']['batch_status'] ?? '') === 'inactive';
            })->count(),
            'unassigned' => $students->filter(function ($student) {
                return empty($student['enrollment']['batch_id']);
            })->count(),
        ];

        // Payment statistics
        $totalFees = $students->sum(function ($student) {
            return $student['payment_summary']['total_amount'] ?? 0;
        });
        $totalPaid = $students->sum(function ($student) {
            return $student['payment_summary']['paid_amount'] ?? 0;
        });
        $totalDues = $students->sum(function ($student) {
            return $student['payment_summary']['due_amount'] ?? 0;
        });
        $studentsWithDues = $students->filter(function ($student) {
            return ($student['payment_summary']['due_amount'] ?? 0) > 0;
        })->count();
        $studentsFullyPaid = $students->filter(function ($student) {
            return ($student['payment_summary']['due_amount'] ?? 0) <= 0;
        })->count();

        // Performance statistics
        $averageScore = $students->avg(function ($student) {
            return $student['performance_summary']['average_percentage'] ?? 0;
        });
        $studentsAboveAverage = $students->filter(function ($student) use ($averageScore) {
            return ($student['performance_summary']['average_percentage'] ?? 0) >= $averageScore;
        })->count();
        $studentsBelowAverage = $totalStudents - $studentsAboveAverage;

        // Attendance statistics
        $averageAttendance = $students->avg(function ($student) {
            return $student['attendance_summary']['attendance_percentage'] ?? 0;
        });
        $studentsAbove80Percent = $students->filter(function ($student) {
            return ($student['attendance_summary']['attendance_percentage'] ?? 0) >= 80;
        })->count();
        $studentsBelow50Percent = $students->filter(function ($student) {
            return ($student['attendance_summary']['attendance_percentage'] ?? 0) < 50;
        })->count();

        return [
            'total_students' => $totalStudents,
            'by_batch' => $byBatch,
            'by_enrollment_status' => $byEnrollmentStatus,
            'payment_stats' => [
                'total_fees' => round($totalFees, 2),
                'total_paid' => round($totalPaid, 2),
                'total_dues' => round($totalDues, 2),
                'students_with_dues' => $studentsWithDues,
                'students_fully_paid' => $studentsFullyPaid,
                'collection_rate' => $totalFees > 0 
                    ? round(($totalPaid / $totalFees) * 100, 2) 
                    : 0,
            ],
            'performance_stats' => [
                'average_score' => round($averageScore, 2),
                'students_above_average' => $studentsAboveAverage,
                'students_below_average' => $studentsBelowAverage,
            ],
            'attendance_stats' => [
                'average_attendance_percentage' => round($averageAttendance, 2),
                'students_above_80_percent' => $studentsAbove80Percent,
                'students_below_50_percent' => $studentsBelow50Percent,
            ],
        ];
    }

    /**
     * Get dashboard chart data for various chart types with filtering support.
     * 
     * Generates data for specific dashboard charts including:
     * - payment_trends: Payment amounts over time
     * - attendance_stats: Attendance statistics (present, absent, late)
     * - enrollment_distribution: Student enrollment by batch/course
     * - performance_distribution: Grade distribution and performance metrics
     * 
     * @param string $chartType The type of chart data to generate
     * @param array $filters Supported filters: start_date, end_date, batch_id, course_id
     * @return array Chart data with labels and datasets
     * 
     * Requirements: 9.1, 9.2, 9.3, 9.4, 9.5
     */
    public function getDashboardChartData(string $chartType, array $filters = []): array
    {
        $cacheKey = 'dashboard_chart_' . $chartType . '_' . md5(json_encode($filters));
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($chartType, $filters) {
            return match ($chartType) {
                'payment_trends' => $this->getPaymentTrendsChartData($filters),
                'attendance_stats' => $this->getAttendanceStatsChartData($filters),
                'enrollment_distribution' => $this->getEnrollmentDistributionChartData($filters),
                'performance_distribution' => $this->getPerformanceDistributionChartData($filters),
                default => [
                    'labels' => [],
                    'datasets' => [],
                    'error' => 'Unknown chart type: ' . $chartType,
                ],
            };
        });
    }

    /**
     * Generate payment trends chart data.
     * 
     * Shows payment amounts over time, grouped by day, week, or month
     * depending on the date range selected.
     * 
     * @param array $filters Supported: start_date, end_date, batch_id, payment_method
     * @return array Chart data with labels and payment amounts
     * 
     * Requirements: 9.1, 9.5
     */
    protected function getPaymentTrendsChartData(array $filters): array
    {
        // Determine date range
        $endDate = !empty($filters['end_date']) 
            ? Carbon::parse($filters['end_date']) 
            : Carbon::now();
        $startDate = !empty($filters['start_date']) 
            ? Carbon::parse($filters['start_date']) 
            : $endDate->copy()->subMonths(6);

        // Calculate the number of days in the range to determine grouping
        $daysDiff = $startDate->diffInDays($endDate);
        
        // Build the query
        $query = Payment::completed();

        // Apply date range filter
        $query->whereBetween('payment_date', [$startDate->startOfDay(), $endDate->endOfDay()]);

        // Apply batch filter if provided
        if (!empty($filters['batch_id'])) {
            $query->whereHas('student', function ($q) use ($filters) {
                $q->where('batch_id', $filters['batch_id']);
            });
        }

        // Apply payment method filter if provided
        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        $payments = $query->get();

        // Group data based on date range
        if ($daysDiff <= 31) {
            // Daily grouping for ranges up to 1 month
            $groupedData = $this->groupPaymentsByDay($payments, $startDate, $endDate);
        } elseif ($daysDiff <= 90) {
            // Weekly grouping for ranges up to 3 months
            $groupedData = $this->groupPaymentsByWeek($payments, $startDate, $endDate);
        } else {
            // Monthly grouping for longer ranges
            $groupedData = $this->groupPaymentsByMonth($payments, $startDate, $endDate);
        }

        // Calculate summary statistics
        $totalAmount = $payments->sum('amount');
        $averageAmount = $payments->count() > 0 ? $payments->avg('amount') : 0;
        $paymentCount = $payments->count();

        // Payment method breakdown
        $methodBreakdown = $payments->groupBy('payment_method')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'amount' => round($group->sum('amount'), 2),
                ];
            })->toArray();

        return [
            'labels' => $groupedData['labels'],
            'datasets' => [
                [
                    'label' => 'Payment Amount',
                    'data' => $groupedData['amounts'],
                    'backgroundColor' => 'rgba(54, 162, 235, 0.5)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
                [
                    'label' => 'Payment Count',
                    'data' => $groupedData['counts'],
                    'backgroundColor' => 'rgba(75, 192, 192, 0.5)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 2,
                    'fill' => false,
                    'yAxisID' => 'y1',
                ],
            ],
            'summary' => [
                'total_amount' => round($totalAmount, 2),
                'average_amount' => round($averageAmount, 2),
                'payment_count' => $paymentCount,
                'method_breakdown' => $methodBreakdown,
                'date_range' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                ],
            ],
        ];
    }

    /**
     * Group payments by day.
     */
    protected function groupPaymentsByDay(Collection $payments, Carbon $startDate, Carbon $endDate): array
    {
        $labels = [];
        $amounts = [];
        $counts = [];

        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dateStr = $current->format('Y-m-d');
            $labels[] = $current->format('M d');
            
            $dayPayments = $payments->filter(function ($payment) use ($dateStr) {
                return $payment->payment_date && $payment->payment_date->format('Y-m-d') === $dateStr;
            });
            
            $amounts[] = round($dayPayments->sum('amount'), 2);
            $counts[] = $dayPayments->count();
            
            $current->addDay();
        }

        return ['labels' => $labels, 'amounts' => $amounts, 'counts' => $counts];
    }

    /**
     * Group payments by week.
     */
    protected function groupPaymentsByWeek(Collection $payments, Carbon $startDate, Carbon $endDate): array
    {
        $labels = [];
        $amounts = [];
        $counts = [];

        $current = $startDate->copy()->startOfWeek();
        while ($current <= $endDate) {
            $weekEnd = $current->copy()->endOfWeek();
            $labels[] = $current->format('M d') . ' - ' . $weekEnd->format('M d');
            
            $weekPayments = $payments->filter(function ($payment) use ($current, $weekEnd) {
                return $payment->payment_date && 
                       $payment->payment_date >= $current && 
                       $payment->payment_date <= $weekEnd;
            });
            
            $amounts[] = round($weekPayments->sum('amount'), 2);
            $counts[] = $weekPayments->count();
            
            $current->addWeek();
        }

        return ['labels' => $labels, 'amounts' => $amounts, 'counts' => $counts];
    }

    /**
     * Group payments by month.
     */
    protected function groupPaymentsByMonth(Collection $payments, Carbon $startDate, Carbon $endDate): array
    {
        $labels = [];
        $amounts = [];
        $counts = [];

        $current = $startDate->copy()->startOfMonth();
        while ($current <= $endDate) {
            $labels[] = $current->format('M Y');
            
            $monthPayments = $payments->filter(function ($payment) use ($current) {
                return $payment->payment_date && 
                       $payment->payment_date->year === $current->year && 
                       $payment->payment_date->month === $current->month;
            });
            
            $amounts[] = round($monthPayments->sum('amount'), 2);
            $counts[] = $monthPayments->count();
            
            $current->addMonth();
        }

        return ['labels' => $labels, 'amounts' => $amounts, 'counts' => $counts];
    }

    /**
     * Generate attendance statistics chart data.
     * 
     * Shows attendance breakdown (present, absent, late, excused) over time.
     * 
     * @param array $filters Supported: start_date, end_date, batch_id, student_id
     * @return array Chart data with labels and attendance counts
     * 
     * Requirements: 9.2, 9.5
     */
    protected function getAttendanceStatsChartData(array $filters): array
    {
        // Determine date range
        $endDate = !empty($filters['end_date']) 
            ? Carbon::parse($filters['end_date']) 
            : Carbon::now();
        $startDate = !empty($filters['start_date']) 
            ? Carbon::parse($filters['start_date']) 
            : $endDate->copy()->subDays(30);

        // Build the query
        $query = Attendance::query();

        // Apply date range filter
        $query->whereBetween('date', [$startDate->startOfDay(), $endDate->endOfDay()]);

        // Apply batch filter if provided
        if (!empty($filters['batch_id'])) {
            $query->where('batch_id', $filters['batch_id']);
        }

        // Apply student filter if provided
        if (!empty($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }

        $attendances = $query->get();

        // Group by date
        $labels = [];
        $presentData = [];
        $absentData = [];
        $lateData = [];
        $excusedData = [];

        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dateStr = $current->format('Y-m-d');
            $labels[] = $current->format('M d');
            
            $dayAttendances = $attendances->filter(function ($attendance) use ($dateStr) {
                return $attendance->date && $attendance->date->format('Y-m-d') === $dateStr;
            });
            
            $presentData[] = $dayAttendances->where('status', 'present')->count();
            $absentData[] = $dayAttendances->where('status', 'absent')->count();
            $lateData[] = $dayAttendances->where('status', 'late')->count();
            $excusedData[] = $dayAttendances->where('status', 'excused')->count();
            
            $current->addDay();
        }

        // Calculate summary statistics
        $totalRecords = $attendances->count();
        $presentCount = $attendances->where('status', 'present')->count();
        $absentCount = $attendances->where('status', 'absent')->count();
        $lateCount = $attendances->where('status', 'late')->count();
        $excusedCount = $attendances->where('status', 'excused')->count();

        $attendanceRate = $totalRecords > 0 
            ? round((($presentCount + $lateCount) / $totalRecords) * 100, 2) 
            : 0;

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Present',
                    'data' => $presentData,
                    'backgroundColor' => 'rgba(75, 192, 192, 0.7)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Absent',
                    'data' => $absentData,
                    'backgroundColor' => 'rgba(255, 99, 132, 0.7)',
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Late',
                    'data' => $lateData,
                    'backgroundColor' => 'rgba(255, 206, 86, 0.7)',
                    'borderColor' => 'rgba(255, 206, 86, 1)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Excused',
                    'data' => $excusedData,
                    'backgroundColor' => 'rgba(153, 102, 255, 0.7)',
                    'borderColor' => 'rgba(153, 102, 255, 1)',
                    'borderWidth' => 1,
                ],
            ],
            'summary' => [
                'total_records' => $totalRecords,
                'present_count' => $presentCount,
                'absent_count' => $absentCount,
                'late_count' => $lateCount,
                'excused_count' => $excusedCount,
                'attendance_rate' => $attendanceRate,
                'date_range' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                ],
            ],
            'pie_data' => [
                'labels' => ['Present', 'Absent', 'Late', 'Excused'],
                'data' => [$presentCount, $absentCount, $lateCount, $excusedCount],
                'backgroundColor' => [
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                ],
            ],
        ];
    }

    /**
     * Generate enrollment distribution chart data.
     * 
     * Shows student enrollment distribution by batch and course.
     * 
     * @param array $filters Supported: start_date, end_date (for enrollment date), course_id
     * @return array Chart data with batch/course distribution
     * 
     * Requirements: 9.3, 9.5
     */
    protected function getEnrollmentDistributionChartData(array $filters): array
    {
        // Build the query for students
        $query = Student::with(['batch.course']);

        // Apply enrollment date range filter if provided
        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['start_date'])->startOfDay());
        }
        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['end_date'])->endOfDay());
        }

        // Apply course filter if provided
        if (!empty($filters['course_id'])) {
            $query->whereHas('batch', function ($q) use ($filters) {
                $q->where('course_id', $filters['course_id']);
            });
        }

        $students = $query->get();

        // Group by batch
        $byBatch = $students->groupBy(function ($student) {
            return $student->batch ? $student->batch->name : 'Unassigned';
        })->map(function ($group) {
            return $group->count();
        })->sortDesc();

        // Group by course
        $byCourse = $students->groupBy(function ($student) {
            return $student->batch && $student->batch->course 
                ? $student->batch->course->name 
                : 'Unassigned';
        })->map(function ($group) {
            return $group->count();
        })->sortDesc();

        // Group by batch status
        $byBatchStatus = $students->groupBy(function ($student) {
            return $student->batch ? $student->batch->status : 'unassigned';
        })->map(function ($group) {
            return $group->count();
        });

        // Monthly enrollment trend
        $monthlyEnrollment = [];
        $endDate = !empty($filters['end_date']) 
            ? Carbon::parse($filters['end_date']) 
            : Carbon::now();
        $startDate = !empty($filters['start_date']) 
            ? Carbon::parse($filters['start_date']) 
            : $endDate->copy()->subMonths(12);

        $current = $startDate->copy()->startOfMonth();
        while ($current <= $endDate) {
            $monthStudents = $students->filter(function ($student) use ($current) {
                return $student->created_at && 
                       $student->created_at->year === $current->year && 
                       $student->created_at->month === $current->month;
            });
            
            $monthlyEnrollment[$current->format('M Y')] = $monthStudents->count();
            $current->addMonth();
        }

        // Generate colors for batches
        $batchColors = $this->generateChartColors($byBatch->count());
        $courseColors = $this->generateChartColors($byCourse->count());

        return [
            'by_batch' => [
                'labels' => $byBatch->keys()->toArray(),
                'datasets' => [
                    [
                        'label' => 'Students per Batch',
                        'data' => $byBatch->values()->toArray(),
                        'backgroundColor' => $batchColors['backgrounds'],
                        'borderColor' => $batchColors['borders'],
                        'borderWidth' => 1,
                    ],
                ],
            ],
            'by_course' => [
                'labels' => $byCourse->keys()->toArray(),
                'datasets' => [
                    [
                        'label' => 'Students per Course',
                        'data' => $byCourse->values()->toArray(),
                        'backgroundColor' => $courseColors['backgrounds'],
                        'borderColor' => $courseColors['borders'],
                        'borderWidth' => 1,
                    ],
                ],
            ],
            'by_status' => [
                'labels' => $byBatchStatus->keys()->map(function ($status) {
                    return ucfirst($status);
                })->toArray(),
                'data' => $byBatchStatus->values()->toArray(),
                'backgroundColor' => [
                    'rgba(75, 192, 192, 0.7)',   // active
                    'rgba(255, 206, 86, 0.7)',   // inactive
                    'rgba(54, 162, 235, 0.7)',   // completed
                    'rgba(153, 102, 255, 0.7)',  // unassigned
                ],
            ],
            'monthly_trend' => [
                'labels' => array_keys($monthlyEnrollment),
                'datasets' => [
                    [
                        'label' => 'New Enrollments',
                        'data' => array_values($monthlyEnrollment),
                        'backgroundColor' => 'rgba(54, 162, 235, 0.5)',
                        'borderColor' => 'rgba(54, 162, 235, 1)',
                        'borderWidth' => 2,
                        'fill' => true,
                    ],
                ],
            ],
            'summary' => [
                'total_students' => $students->count(),
                'total_batches' => $byBatch->count(),
                'total_courses' => $byCourse->count(),
                'active_students' => $byBatchStatus->get('active', 0),
                'completed_students' => $byBatchStatus->get('completed', 0),
                'date_range' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                ],
            ],
        ];
    }

    /**
     * Generate performance distribution chart data.
     * 
     * Shows grade distribution and performance metrics.
     * 
     * @param array $filters Supported: start_date, end_date, batch_id, course_id, exam_id
     * @return array Chart data with grade distribution and performance metrics
     * 
     * Requirements: 9.4, 9.5
     */
    protected function getPerformanceDistributionChartData(array $filters): array
    {
        // Build the query
        $query = ExamResult::with(['student.batch', 'exam.course']);

        // Apply date range filter if provided
        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['start_date'])->startOfDay());
        }
        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['end_date'])->endOfDay());
        }

        // Apply batch filter if provided
        if (!empty($filters['batch_id'])) {
            $query->whereHas('student', function ($q) use ($filters) {
                $q->where('batch_id', $filters['batch_id']);
            });
        }

        // Apply course filter if provided
        if (!empty($filters['course_id'])) {
            $query->whereHas('exam', function ($q) use ($filters) {
                $q->where('course_id', $filters['course_id']);
            });
        }

        // Apply exam filter if provided
        if (!empty($filters['exam_id'])) {
            $query->where('exam_id', $filters['exam_id']);
        }

        $results = $query->get();

        // Grade distribution
        $gradeDistribution = $results->groupBy('grade')
            ->map(function ($group) {
                return $group->count();
            })
            ->sortKeys();

        // Define standard grade order
        $gradeOrder = ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D', 'F'];
        $orderedGrades = collect($gradeOrder)->mapWithKeys(function ($grade) use ($gradeDistribution) {
            return [$grade => $gradeDistribution->get($grade, 0)];
        })->filter(function ($count) {
            return $count > 0;
        });

        // Score distribution (percentage ranges)
        $scoreRanges = [
            '90-100%' => 0,
            '80-89%' => 0,
            '70-79%' => 0,
            '60-69%' => 0,
            '50-59%' => 0,
            '40-49%' => 0,
            'Below 40%' => 0,
        ];

        foreach ($results as $result) {
            if ($result->total_marks > 0) {
                $percentage = ($result->obtained_marks / $result->total_marks) * 100;
                
                if ($percentage >= 90) {
                    $scoreRanges['90-100%']++;
                } elseif ($percentage >= 80) {
                    $scoreRanges['80-89%']++;
                } elseif ($percentage >= 70) {
                    $scoreRanges['70-79%']++;
                } elseif ($percentage >= 60) {
                    $scoreRanges['60-69%']++;
                } elseif ($percentage >= 50) {
                    $scoreRanges['50-59%']++;
                } elseif ($percentage >= 40) {
                    $scoreRanges['40-49%']++;
                } else {
                    $scoreRanges['Below 40%']++;
                }
            }
        }

        // Pass/Fail distribution
        $passCount = $results->filter(function ($result) {
            return $result->exam && $result->obtained_marks >= $result->exam->pass_marks;
        })->count();
        $failCount = $results->count() - $passCount;

        // Performance by exam
        $byExam = $results->groupBy(function ($result) {
            return $result->exam ? $result->exam->title : 'Unknown';
        })->map(function ($group) {
            $avgScore = $group->avg(function ($result) {
                if ($result->total_marks > 0) {
                    return ($result->obtained_marks / $result->total_marks) * 100;
                }
                return 0;
            });
            
            return [
                'count' => $group->count(),
                'average_percentage' => round($avgScore, 2),
                'highest_score' => $group->max('obtained_marks'),
                'lowest_score' => $group->min('obtained_marks'),
            ];
        });

        // Calculate summary statistics
        $totalResults = $results->count();
        $averagePercentage = $results->avg(function ($result) {
            if ($result->total_marks > 0) {
                return ($result->obtained_marks / $result->total_marks) * 100;
            }
            return 0;
        });
        $passRate = $totalResults > 0 ? round(($passCount / $totalResults) * 100, 2) : 0;

        // Generate colors
        $gradeColors = $this->generateChartColors($orderedGrades->count());
        $scoreColors = $this->generateChartColors(count($scoreRanges));

        return [
            'grade_distribution' => [
                'labels' => $orderedGrades->keys()->toArray(),
                'datasets' => [
                    [
                        'label' => 'Number of Students',
                        'data' => $orderedGrades->values()->toArray(),
                        'backgroundColor' => $gradeColors['backgrounds'],
                        'borderColor' => $gradeColors['borders'],
                        'borderWidth' => 1,
                    ],
                ],
            ],
            'score_distribution' => [
                'labels' => array_keys($scoreRanges),
                'datasets' => [
                    [
                        'label' => 'Number of Students',
                        'data' => array_values($scoreRanges),
                        'backgroundColor' => $scoreColors['backgrounds'],
                        'borderColor' => $scoreColors['borders'],
                        'borderWidth' => 1,
                    ],
                ],
            ],
            'pass_fail' => [
                'labels' => ['Passed', 'Failed'],
                'data' => [$passCount, $failCount],
                'backgroundColor' => [
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(255, 99, 132, 0.7)',
                ],
            ],
            'by_exam' => $byExam->toArray(),
            'summary' => [
                'total_results' => $totalResults,
                'average_percentage' => round($averagePercentage, 2),
                'pass_rate' => $passRate,
                'pass_count' => $passCount,
                'fail_count' => $failCount,
                'unique_exams' => $results->pluck('exam_id')->unique()->count(),
                'unique_students' => $results->pluck('student_id')->unique()->count(),
            ],
        ];
    }

    /**
     * Generate chart colors for a given number of items.
     * 
     * @param int $count Number of colors needed
     * @return array Array with 'backgrounds' and 'borders' color arrays
     */
    protected function generateChartColors(int $count): array
    {
        $baseColors = [
            [54, 162, 235],   // Blue
            [255, 99, 132],   // Red
            [75, 192, 192],   // Teal
            [255, 206, 86],   // Yellow
            [153, 102, 255],  // Purple
            [255, 159, 64],   // Orange
            [199, 199, 199],  // Gray
            [83, 102, 255],   // Indigo
            [255, 99, 255],   // Pink
            [99, 255, 132],   // Green
        ];

        $backgrounds = [];
        $borders = [];

        for ($i = 0; $i < $count; $i++) {
            $color = $baseColors[$i % count($baseColors)];
            $backgrounds[] = "rgba({$color[0]}, {$color[1]}, {$color[2]}, 0.7)";
            $borders[] = "rgba({$color[0]}, {$color[1]}, {$color[2]}, 1)";
        }

        return [
            'backgrounds' => $backgrounds,
            'borders' => $borders,
        ];
    }

    /**
     * Export report to PDF (placeholder).
     */
    public function exportToPdf(array $data, string $template): string
    {
        // This would integrate with a PDF library like DomPDF or Snappy
        // For now, return a placeholder path
        return 'exports/report_' . time() . '.pdf';
    }

    /**
     * Export report to Excel (placeholder).
     */
    public function exportToExcel(array $data, array $columns): string
    {
        // This would integrate with a library like Laravel Excel
        // For now, return a placeholder path
        return 'exports/report_' . time() . '.xlsx';
    }

    /**
     * Clear report cache.
     */
    public function clearCache(): void
    {
        Cache::forget('attendance_report_*');
        Cache::forget('payment_report_*');
        Cache::forget('performance_report_*');
    }

    /**
     * Get dashboard statistics.
     */
    public function getDashboardStats(): array
    {
        return Cache::remember('dashboard_stats', 300, function () {
            $currentMonth = now();
            return [
                'total_students' => Student::count(),
                'total_batches' => Batch::count(),
                'active_batches' => Batch::active()->count(),
                'today_attendance' => Attendance::where('date', now()->toDateString())->count(),
                'this_month_payments' => Payment::completed()
                    ->whereBetween('payment_date', [
                        $currentMonth->copy()->startOfMonth(),
                        $currentMonth->copy()->endOfMonth()
                    ])
                    ->sum('amount'),
                'total_dues' => Student::sum('due_amount'),
            ];
        });
    }
}
