<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateReportExportJob;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Exam;
use App\Models\ReportExport;
use App\Services\ExportService;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * ReportController handles all report generation, display, and export operations.
 * 
 * This controller provides endpoints for:
 * - Attendance reports with filtering and exports (Requirements: 5.2, 5.3, 5.4)
 * - Payment reports with filtering and exports (Requirements: 6.2, 6.3, 6.4)
 * - Performance reports with filtering and exports (Requirements: 7.2, 7.3, 7.4)
 * - Student reports with filtering and exports (Requirements: 8.1, 8.3, 8.4, 8.5)
 * - Dashboard chart data (Requirements: 9.1, 9.2, 9.3, 9.4, 9.5)
 */
class ReportController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param ReportService $reportService Service for report data retrieval
     * @param ExportService $exportService Service for Excel and PDF exports
     */
    public function __construct(
        protected ReportService $reportService,
        protected ExportService $exportService
    ) {
    }

    /**
     * Display the reports index page.
     *
     * @return View
     */
    public function index(): View
    {
        return view('dashboard.reports.index');
    }

    // =========================================================================
    // ATTENDANCE REPORT ENDPOINTS
    // Requirements: 5.2, 5.3, 5.4
    // =========================================================================

    /**
     * Display attendance report with filters.
     * 
     * Retrieves attendance data from the database with support for filtering
     * by batch, date range, and individual student.
     * 
     * Requirements: 5.2
     *
     * @param Request $request
     * @return View
     */
    public function attendance(Request $request): View
    {
        $filters = $this->extractAttendanceFilters($request);
        
        // Generate report with default filters if none provided
        if (empty(array_filter($filters))) {
            // Default to last 30 days
            $filters['start_date'] = now()->subDays(30)->format('Y-m-d');
            $filters['end_date'] = now()->format('Y-m-d');
        }
        
        $report = $this->reportService->generateAttendanceReport($filters);
        
        $batches = Batch::active()->get();
        
        return view('dashboard.reports.attendance', compact('report', 'batches', 'filters'));
    }

    /**
     * Export attendance report to Excel format.
     * 
     * Generates an Excel file with formatted attendance records
     * using the same filters applied in the report view.
     * 
     * Requirements: 5.3
     *
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function exportAttendanceExcel(Request $request): RedirectResponse
    {
        return $this->queueExport($request, 'attendance', 'xlsx', $this->extractAttendanceFilters($request));
    }

    /**
     * Export attendance report to PDF format.
     * 
     * Generates a PDF file with formatted attendance records
     * including institution branding.
     * 
     * Requirements: 5.4
     *
     * @param Request $request
     * @return Response
     */
    public function exportAttendancePdf(Request $request): RedirectResponse
    {
        return $this->queueExport($request, 'attendance', 'pdf', $this->extractAttendanceFilters($request));
    }

    // =========================================================================
    // PAYMENT REPORT ENDPOINTS
    // Requirements: 6.2, 6.3, 6.4
    // =========================================================================

    /**
     * Display payment report with filters.
     * 
     * Retrieves payment data from the database with support for filtering
     * by date range, batch, and payment method. Shows total revenue,
     * payment method breakdown, and outstanding dues.
     * 
     * Requirements: 6.2
     *
     * @param Request $request
     * @return View
     */
    public function payment(Request $request): View
    {
        $filters = $this->extractPaymentFilters($request);
        
        // Generate report with filters
        $report = $this->reportService->generatePaymentReport($filters);
        
        $batches = Batch::all();
        $paymentMethods = ['cash', 'bkash', 'nagad', 'bank_transfer'];
        
        return view('dashboard.reports.payment', compact('report', 'batches', 'paymentMethods', 'filters'));
    }

    /**
     * Export payment report to Excel format.
     * 
     * Generates an Excel file with payment summaries
     * using the same filters applied in the report view.
     * 
     * Requirements: 6.3
     *
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function exportPaymentExcel(Request $request): RedirectResponse
    {
        return $this->queueExport($request, 'payment', 'xlsx', $this->extractPaymentFilters($request));
    }

    /**
     * Export payment report to PDF format.
     * 
     * Generates a PDF file with payment summaries
     * including institution branding.
     * 
     * Requirements: 6.4
     *
     * @param Request $request
     * @return Response
     */
    public function exportPaymentPdf(Request $request): RedirectResponse
    {
        return $this->queueExport($request, 'payment', 'pdf', $this->extractPaymentFilters($request));
    }

    // =========================================================================
    // PERFORMANCE REPORT ENDPOINTS
    // Requirements: 7.2, 7.3, 7.4
    // =========================================================================

    /**
     * Display performance/exam report with filters.
     * 
     * Retrieves exam and grade data from the database with support for
     * filtering by batch, course, and exam. Calculates averages, pass rates,
     * and grade distributions.
     * 
     * Requirements: 7.2
     *
     * @param Request $request
     * @return View
     */
    public function performance(Request $request): View
    {
        $filters = $this->extractPerformanceFilters($request);
        
        // Generate report with default filters if none provided
        if (empty(array_filter($filters))) {
            // Default to first batch
            $firstBatch = Batch::active()->first();
            if ($firstBatch) {
                $filters['batch_id'] = $firstBatch->id;
            }
        }
        
        $report = $this->reportService->generatePerformanceReport($filters);
        
        $batches = Batch::active()->get();
        $courses = Course::active()->get();
        $exams = Exam::orderBy('created_at', 'desc')->get();
        
        return view('dashboard.reports.performance', compact('report', 'batches', 'courses', 'exams', 'filters'));
    }

    /**
     * Export performance report to Excel format.
     * 
     * Generates an Excel file with student scores and rankings
     * using the same filters applied in the report view.
     * 
     * Requirements: 7.3
     *
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function exportPerformanceExcel(Request $request): RedirectResponse
    {
        return $this->queueExport($request, 'performance', 'xlsx', $this->extractPerformanceFilters($request));
    }

    /**
     * Export performance report to PDF format.
     * 
     * Generates a PDF file with student scores and rankings
     * including institution branding.
     * 
     * Requirements: 7.4
     *
     * @param Request $request
     * @return Response
     */
    public function exportPerformancePdf(Request $request): RedirectResponse
    {
        return $this->queueExport($request, 'performance', 'pdf', $this->extractPerformanceFilters($request));
    }

    // =========================================================================
    // STUDENT REPORT ENDPOINTS
    // Requirements: 8.1, 8.3, 8.4, 8.5
    // =========================================================================

    /**
     * Display comprehensive student report with filters.
     * 
     * Compiles enrollment, payment, and performance data for students.
     * Supports searching and filtering by name, batch, or enrollment status.
     * 
     * Requirements: 8.1, 8.5
     *
     * @param Request $request
     * @return View
     */
    public function student(Request $request): View
    {
        $filters = $this->extractStudentFilters($request);
        
        // Get student report data
        $students = $this->reportService->getStudentReport($filters);
        
        // Calculate statistics
        $stats = $this->reportService->calculateStudentReportStats($students);
        
        $batches = Batch::all();
        $enrollmentStatuses = ['active', 'completed', 'inactive', 'with_dues', 'paid'];
        
        return view('dashboard.reports.student', compact('students', 'stats', 'batches', 'enrollmentStatuses', 'filters'));
    }

    /**
     * Export student report to Excel format.
     * 
     * Generates an Excel file with comprehensive student information
     * using the same filters applied in the report view.
     * 
     * Requirements: 8.3
     *
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function exportStudentExcel(Request $request): RedirectResponse
    {
        return $this->queueExport($request, 'student', 'xlsx', $this->extractStudentFilters($request));
    }

    /**
     * Export student report to PDF format.
     * 
     * Generates a PDF file with comprehensive student information
     * including institution branding.
     * 
     * Requirements: 8.4
     *
     * @param Request $request
     * @return Response
     */
    public function exportStudentPdf(Request $request): RedirectResponse
    {
        return $this->queueExport($request, 'student', 'pdf', $this->extractStudentFilters($request));
    }

    // =========================================================================
    // DASHBOARD DATA ENDPOINT
    // Requirements: 9.1, 9.2, 9.3, 9.4, 9.5
    // =========================================================================

    /**
     * Return dashboard chart data as JSON.
     * 
     * Generates data for dashboard visualizations including:
     * - Payment trend charts (Requirement 9.1)
     * - Attendance statistics charts (Requirement 9.2)
     * - Enrollment and batch distribution charts (Requirement 9.3)
     * - Performance distribution charts (Requirement 9.4)
     * 
     * Supports filtering by date range and other criteria (Requirement 9.5).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function dashboardData(Request $request): JsonResponse
    {
        $chartType = $request->input('chart_type', 'all');
        $filters = $this->extractDashboardFilters($request);
        
        try {
            if ($chartType === 'all') {
                // Return all chart data
                $data = [
                    'payment_trends' => $this->reportService->getDashboardChartData('payment_trends', $filters),
                    'attendance_stats' => $this->reportService->getDashboardChartData('attendance_stats', $filters),
                    'enrollment_distribution' => $this->reportService->getDashboardChartData('enrollment_distribution', $filters),
                    'performance_distribution' => $this->reportService->getDashboardChartData('performance_distribution', $filters),
                ];
            } else {
                // Return specific chart data
                $data = $this->reportService->getDashboardChartData($chartType, $filters);
            }
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'filters' => $filters,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate chart data: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // LEGACY ENDPOINTS (for backward compatibility)
    // =========================================================================

    /**
     * Display payment summary report (legacy endpoint).
     * 
     * @deprecated Use payment() method instead
     * @param Request $request
     * @return View
     */
    public function paymentSummary(Request $request): View
    {
        return $this->payment($request);
    }

    /**
     * Display charts page.
     *
     * @param Request $request
     * @return View
     */
    public function charts(Request $request): View
    {
        $chartData = [
            'revenue' => $this->reportService->getChartData('payment'),
            'attendance' => $this->reportService->getChartData('attendance'),
            'admissions' => $this->reportService->getChartData('enrollment'),
        ];
        
        return view('dashboard.reports.charts', compact('chartData'));
    }

    /**
     * Display export page.
     *
     * @param Request $request
     * @return View
     */
    public function export(Request $request): View
    {
        $batches = Batch::all();
        $courses = Course::all();
        $exports = ReportExport::where('requested_by', Auth::id())
            ->latest()
            ->limit(20)
            ->get();

        return view('dashboard.reports.export', compact('batches', 'courses', 'exports'));
    }

    /**
     * Generic PDF export (legacy endpoint).
     *
     * @deprecated Use specific export methods instead
     * @param Request $request
     * @return RedirectResponse
     */
    public function exportPdf(Request $request): RedirectResponse
    {
        $request->validate([
            'report_type' => 'required|in:attendance,payment,performance,student',
        ]);

        $reportType = $request->input('report_type');

        return match ($reportType) {
            'attendance' => $this->exportAttendancePdf($request),
            'payment' => $this->exportPaymentPdf($request),
            'performance' => $this->exportPerformancePdf($request),
            'student' => $this->exportStudentPdf($request),
        };
    }

    /**
     * Generic Excel export (legacy endpoint).
     *
     * @deprecated Use specific export methods instead
     * @param Request $request
     * @return RedirectResponse
     */
    public function exportExcel(Request $request): RedirectResponse
    {
        $request->validate([
            'report_type' => 'required|in:attendance,payment,performance,student',
        ]);

        $reportType = $request->input('report_type');

        return match ($reportType) {
            'attendance' => $this->exportAttendanceExcel($request),
            'payment' => $this->exportPaymentExcel($request),
            'performance' => $this->exportPerformanceExcel($request),
            'student' => $this->exportStudentExcel($request),
        };
    }

    /**
     * Queue a report export for background generation.
     *
     * The file is rendered by GenerateReportExportJob onto private storage and
     * the requester receives a dashboard notification with a download link.
     */
    private function queueExport(Request $request, string $reportType, string $format, array $filters): RedirectResponse
    {
        $export = ReportExport::create([
            'uuid' => (string) Str::uuid(),
            'requested_by' => Auth::id(),
            'report_type' => $reportType,
            'format' => $format,
            'filters' => array_filter($filters, fn ($value) => $value !== null && $value !== ''),
            'status' => ReportExport::STATUS_PENDING,
            'disk' => config('filesystems.private'),
            'filename' => ucfirst($reportType) . '_Report_' . now()->format('Y-m-d_His') . '.' . ($format === 'pdf' ? 'pdf' : 'xlsx'),
        ]);

        GenerateReportExportJob::dispatch($export);

        return back()->with(
            'success',
            "Your {$format} export is being generated in the background. You will receive a notification with a download link when it is ready."
        );
    }

    /**
     * Stream a completed export file to its requester.
     */
    public function downloadExport(ReportExport $export)
    {
        abort_unless(
            $export->requested_by === Auth::id() || Auth::user()->isSuperAdmin(),
            403,
            'You can only download your own exports.'
        );
        abort_unless($export->isCompleted(), 404, 'This export is not available.');

        $disk = Storage::disk($export->disk);
        abort_unless($export->path && $disk->exists($export->path), 404, 'The export file is no longer available.');

        return $disk->download($export->path, $export->filename);
    }

    // =========================================================================
    // PRIVATE HELPER METHODS
    // =========================================================================

    /**
     * Extract attendance report filters from request.
     *
     * @param Request $request
     * @return array
     */
    private function extractAttendanceFilters(Request $request): array
    {
        return [
            'batch_id' => $request->input('batch_id'),
            'student_id' => $request->input('student_id'),
            'start_date' => $request->input('date_from') ?? $request->input('start_date'),
            'end_date' => $request->input('date_to') ?? $request->input('end_date'),
            'status' => $request->input('status'),
        ];
    }

    /**
     * Extract payment report filters from request.
     *
     * @param Request $request
     * @return array
     */
    private function extractPaymentFilters(Request $request): array
    {
        return [
            'batch_id' => $request->input('batch_id'),
            'student_id' => $request->input('student_id'),
            'start_date' => $request->input('date_from') ?? $request->input('start_date'),
            'end_date' => $request->input('date_to') ?? $request->input('end_date'),
            'payment_method' => $request->input('payment_method'),
            'status' => $request->input('status'),
        ];
    }

    /**
     * Extract performance report filters from request.
     *
     * @param Request $request
     * @return array
     */
    private function extractPerformanceFilters(Request $request): array
    {
        return [
            'batch_id' => $request->input('batch_id'),
            'course_id' => $request->input('course_id'),
            'exam_id' => $request->input('exam_id'),
            'student_id' => $request->input('student_id'),
        ];
    }

    /**
     * Extract student report filters from request.
     *
     * @param Request $request
     * @return array
     */
    private function extractStudentFilters(Request $request): array
    {
        return [
            'batch_id' => $request->input('batch_id'),
            'student_id' => $request->input('student_id'),
            'name' => $request->input('name') ?? $request->input('search'),
            'enrollment_status' => $request->input('enrollment_status'),
            'start_date' => $request->input('date_from') ?? $request->input('start_date'),
            'end_date' => $request->input('date_to') ?? $request->input('end_date'),
        ];
    }

    /**
     * Extract dashboard chart filters from request.
     *
     * @param Request $request
     * @return array
     */
    private function extractDashboardFilters(Request $request): array
    {
        return [
            'start_date' => $request->input('start_date') ?? $request->input('date_from'),
            'end_date' => $request->input('end_date') ?? $request->input('date_to'),
            'batch_id' => $request->input('batch_id'),
            'course_id' => $request->input('course_id'),
            'exam_id' => $request->input('exam_id'),
            'payment_method' => $request->input('payment_method'),
        ];
    }
}
