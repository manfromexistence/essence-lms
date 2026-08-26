<?php

namespace App\Services;

use App\Exports\AttendanceExport;
use App\Exports\PaymentExport;
use App\Exports\PerformanceExport;
use App\Exports\StudentExport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use InvalidArgumentException;
use Exception;
use RuntimeException;

/**
 * ExportService handles Excel and PDF export generation for all report types.
 * 
 * This service provides a unified interface for generating downloadable exports
 * using Laravel Excel for Excel files and DomPDF for PDF files.
 * 
 * Supported report types:
 * - attendance: Student attendance records
 * - payment: Payment transaction records
 * - performance: Exam results and scores
 * - student: Comprehensive student information
 * 
 * Requirements: 16.4
 */
class ExportService
{
    /**
     * Mapping of report types to their corresponding Laravel Excel export classes.
     *
     * @var array<string, string>
     */
    protected array $exportClasses = [
        'attendance' => AttendanceExport::class,
        'payment' => PaymentExport::class,
        'performance' => PerformanceExport::class,
        'student' => StudentExport::class,
    ];

    /**
     * Mapping of report types to their corresponding PDF Blade views.
     *
     * @var array<string, string>
     */
    protected array $pdfViews = [
        'attendance' => 'exports.pdf.attendance',
        'payment' => 'exports.pdf.payment',
        'performance' => 'exports.pdf.performance',
        'student' => 'exports.pdf.student',
    ];

    /**
     * Export data to Excel format using Laravel Excel.
     * 
     * Generates an Excel file for the specified report type with the given filters.
     * The file is generated with a timestamped filename and triggers a download.
     * 
     * Requirements: 16.4
     *
     * @param string $reportType The type of report (attendance, payment, performance, student)
     * @param Collection $data The data collection to export (optional, export class handles data retrieval)
     * @param array $filters Filters to apply to the export
     * @return BinaryFileResponse The downloadable Excel file response
     * @throws InvalidArgumentException If the report type is not supported
     * @throws Exception If export generation fails
     */
    public function exportToExcel(string $reportType, Collection $data, array $filters = []): BinaryFileResponse
    {
        $exportClass = $this->getExportClass($reportType);
        
        if (!$exportClass) {
            Log::warning('Unsupported report type for Excel export', [
                'report_type' => $reportType,
            ]);
            throw new InvalidArgumentException("Unsupported report type: {$reportType}. Please select a valid report type.");
        }

        try {
            // Generate filename with timestamp
            $filename = $this->generateFilename($reportType, 'xlsx');

            // Create export instance with filters
            $export = new $exportClass($filters);

            // Generate and return the Excel download
            return Excel::download($export, $filename);
        } catch (QueryException $e) {
            Log::error('Database error during Excel export', [
                'report_type' => $reportType,
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);
            throw new Exception("Unable to generate Excel export due to a database error. Please try again.");
        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            Log::error('Spreadsheet error during Excel export', [
                'report_type' => $reportType,
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);
            throw new Exception("Unable to generate Excel file. The data may be too large or contain invalid characters.");
        } catch (\OutOfMemoryError $e) {
            Log::error('Memory error during Excel export', [
                'report_type' => $reportType,
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);
            throw new Exception("Unable to generate Excel export. The dataset is too large. Please apply filters to reduce the data size.");
        } catch (Exception $e) {
            Log::error('Excel export failed', [
                'report_type' => $reportType,
                'filters' => $filters,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw new Exception("Failed to generate Excel export. Please try again or contact support if the problem persists.");
        }
    }

    /**
     * Export data to PDF format using DomPDF.
     * 
     * Generates a PDF file for the specified report type with the given data and filters.
     * The PDF includes institution branding and proper formatting.
     * 
     * Requirements: 17.2, 17.3, 17.4
     *
     * @param string $reportType The type of report (attendance, payment, performance, student)
     * @param Collection $data The data collection to export
     * @param array $filters Filters applied to the report
     * @return Response The downloadable PDF file response
     * @throws InvalidArgumentException If the report type is not supported
     * @throws Exception If PDF generation fails
     */
    public function exportToPdf(string $reportType, Collection $data, array $filters = []): Response
    {
        $view = $this->getPdfView($reportType);
        
        if (!$view) {
            Log::warning('Unsupported report type for PDF export', [
                'report_type' => $reportType,
            ]);
            throw new InvalidArgumentException("Unsupported report type for PDF: {$reportType}. Please select a valid report type.");
        }

        try {
            // Generate filename with timestamp
            $filename = $this->generateFilename($reportType, 'pdf');

            // Prepare view data
            $viewData = [
                'data' => $data,
                'filters' => $filters,
                'reportType' => $reportType,
                'generatedAt' => Carbon::now()->format('Y-m-d H:i:s'),
                'title' => $this->getReportTitle($reportType),
            ];

            // Generate PDF with appropriate settings
            $pdf = Pdf::loadView($view, $viewData);
            
            // Configure PDF settings based on report type
            $pdf = $this->configurePdfSettings($pdf, $reportType);

            // Return the PDF download response
            return $pdf->download($filename);
        } catch (QueryException $e) {
            Log::error('Database error during PDF export', [
                'report_type' => $reportType,
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);
            throw new Exception("Unable to generate PDF export due to a database error. Please try again.");
        } catch (\Dompdf\Exception $e) {
            Log::error('DomPDF error during PDF export', [
                'report_type' => $reportType,
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);
            throw new Exception("Unable to generate PDF file. The content may contain unsupported elements.");
        } catch (\OutOfMemoryError $e) {
            Log::error('Memory error during PDF export', [
                'report_type' => $reportType,
                'filters' => $filters,
                'data_count' => $data->count(),
                'error' => $e->getMessage(),
            ]);
            throw new Exception("Unable to generate PDF export. The dataset is too large. Please apply filters to reduce the data size.");
        } catch (\InvalidArgumentException $e) {
            Log::error('Invalid view for PDF export', [
                'report_type' => $reportType,
                'view' => $view,
                'error' => $e->getMessage(),
            ]);
            throw new Exception("Unable to generate PDF export. The report template is not available.");
        } catch (Exception $e) {
            Log::error('PDF export failed', [
                'report_type' => $reportType,
                'filters' => $filters,
                'data_count' => $data->count(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw new Exception("Failed to generate PDF export. Please try again or contact support if the problem persists.");
        }
    }

    /**
     * Build (but do not stream) the PDF for a report type so queued jobs can
     * persist it to a storage disk.
     *
     * @param string $reportType The type of report (attendance, payment, performance, student)
     * @param Collection $data The data collection to export
     * @param array $filters Filters applied to the report
     */
    public function buildPdf(string $reportType, Collection $data, array $filters = []): \Barryvdh\DomPDF\PDF
    {
        $view = $this->getPdfView($reportType);

        if (!$view) {
            throw new InvalidArgumentException("Unsupported report type for PDF: {$reportType}.");
        }

        $viewData = [
            'data' => $data,
            'filters' => $filters,
            'reportType' => $reportType,
            'generatedAt' => Carbon::now()->format('Y-m-d H:i:s'),
            'title' => $this->getReportTitle($reportType),
        ];

        $pdf = Pdf::loadView($view, $viewData);

        return $this->configurePdfSettings($pdf, $reportType);
    }

    /**
     * Get the appropriate Laravel Excel export class for a report type.
     * 
     * Returns the fully qualified class name of the export class
     * that handles the specified report type.
     *
     * @param string $reportType The type of report
     * @return string|null The export class name or null if not found
     */
    public function getExportClass(string $reportType): ?string
    {
        $normalizedType = strtolower(trim($reportType));
        
        return $this->exportClasses[$normalizedType] ?? null;
    }

    /**
     * Get the appropriate Blade view for PDF generation.
     * 
     * Returns the Blade view path used for generating PDFs
     * for the specified report type.
     *
     * @param string $reportType The type of report
     * @return string|null The Blade view path or null if not found
     */
    public function getPdfView(string $reportType): ?string
    {
        $normalizedType = strtolower(trim($reportType));
        
        return $this->pdfViews[$normalizedType] ?? null;
    }

    /**
     * Generate a filename for the export with timestamp.
     * 
     * Creates a descriptive filename including the report type
     * and current timestamp for uniqueness.
     *
     * @param string $reportType The type of report
     * @param string $extension The file extension (xlsx, pdf)
     * @return string The generated filename
     */
    protected function generateFilename(string $reportType, string $extension): string
    {
        $timestamp = Carbon::now()->format('Y-m-d_His');
        $reportName = ucfirst(strtolower(trim($reportType)));
        
        return "{$reportName}_Report_{$timestamp}.{$extension}";
    }

    /**
     * Get a human-readable title for the report type.
     *
     * @param string $reportType The type of report
     * @return string The report title
     */
    protected function getReportTitle(string $reportType): string
    {
        $titles = [
            'attendance' => 'Attendance Report',
            'payment' => 'Payment Report',
            'performance' => 'Performance Report',
            'student' => 'Student Report',
        ];

        $normalizedType = strtolower(trim($reportType));
        
        return $titles[$normalizedType] ?? ucfirst($reportType) . ' Report';
    }

    /**
     * Configure PDF settings based on report type.
     * 
     * Applies appropriate paper size, orientation, and other settings
     * based on the type of report being generated.
     *
     * @param \Barryvdh\DomPDF\PDF $pdf The PDF instance
     * @param string $reportType The type of report
     * @return \Barryvdh\DomPDF\PDF The configured PDF instance
     */
    protected function configurePdfSettings($pdf, string $reportType)
    {
        // Default settings
        $paperSize = 'a4';
        $orientation = 'portrait';

        // Adjust settings based on report type
        switch (strtolower($reportType)) {
            case 'attendance':
                // Attendance reports may have many columns
                $orientation = 'landscape';
                break;
            case 'payment':
                // Payment reports work well in portrait
                $orientation = 'portrait';
                break;
            case 'performance':
                // Performance reports with rankings may need landscape
                $orientation = 'landscape';
                break;
            case 'student':
                // Student reports are comprehensive, use landscape
                $orientation = 'landscape';
                break;
        }

        return $pdf->setPaper($paperSize, $orientation);
    }

    /**
     * Get all supported report types.
     *
     * @return array List of supported report type keys
     */
    public function getSupportedReportTypes(): array
    {
        return array_keys($this->exportClasses);
    }

    /**
     * Check if a report type is supported.
     *
     * @param string $reportType The report type to check
     * @return bool True if supported, false otherwise
     */
    public function isReportTypeSupported(string $reportType): bool
    {
        $normalizedType = strtolower(trim($reportType));
        
        return isset($this->exportClasses[$normalizedType]);
    }

    /**
     * Export to Excel with custom export class instance.
     * 
     * Allows using a pre-configured export class instance
     * for more control over the export process.
     *
     * @param object $exportInstance The export class instance
     * @param string $filename The filename for the download
     * @return BinaryFileResponse The downloadable Excel file response
     * @throws Exception If export fails
     */
    public function exportWithInstance(object $exportInstance, string $filename): BinaryFileResponse
    {
        try {
            return Excel::download($exportInstance, $filename);
        } catch (QueryException $e) {
            Log::error('Database error during custom Excel export', [
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);
            throw new Exception("Unable to generate Excel export due to a database error. Please try again.");
        } catch (\OutOfMemoryError $e) {
            Log::error('Memory error during custom Excel export', [
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);
            throw new Exception("Unable to generate Excel export. The dataset is too large.");
        } catch (Exception $e) {
            Log::error('Custom Excel export failed', [
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);
            throw new Exception("Failed to generate Excel export. Please try again.");
        }
    }

    /**
     * Stream Excel export directly to browser.
     * 
     * Useful for large exports where you want to start
     * sending data immediately without buffering.
     *
     * @param string $reportType The type of report
     * @param array $filters Filters to apply to the export
     * @return BinaryFileResponse The streamed Excel response
     * @throws InvalidArgumentException If the report type is not supported
     * @throws Exception If streaming fails
     */
    public function streamExcel(string $reportType, array $filters = []): BinaryFileResponse
    {
        $exportClass = $this->getExportClass($reportType);
        
        if (!$exportClass) {
            Log::warning('Unsupported report type for Excel streaming', [
                'report_type' => $reportType,
            ]);
            throw new InvalidArgumentException("Unsupported report type: {$reportType}. Please select a valid report type.");
        }

        try {
            $filename = $this->generateFilename($reportType, 'xlsx');
            $export = new $exportClass($filters);

            return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::XLSX);
        } catch (QueryException $e) {
            Log::error('Database error during Excel streaming', [
                'report_type' => $reportType,
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);
            throw new Exception("Unable to stream Excel export due to a database error. Please try again.");
        } catch (\OutOfMemoryError $e) {
            Log::error('Memory error during Excel streaming', [
                'report_type' => $reportType,
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);
            throw new Exception("Unable to stream Excel export. The dataset is too large. Please apply filters to reduce the data size.");
        } catch (Exception $e) {
            Log::error('Excel streaming failed', [
                'report_type' => $reportType,
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);
            throw new Exception("Failed to stream Excel export. Please try again or contact support if the problem persists.");
        }
    }
}
