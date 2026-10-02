<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\ReportExport;
use App\Services\ExportService;
use App\Storage\CatboxStorage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Renders a requested report export (Excel/PDF), hosts the result, then
 * notifies the requester with a download link. Slow exports no longer block
 * HTTP workers.
 *
 * Exports are written to local disk first and then uploaded, because Excel and
 * dompdf both need a real filesystem path and the media host only accepts an
 * upload. The exported URL is what gets persisted.
 */
class GenerateReportExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 1800;

    public function __construct(protected ReportExport $export)
    {
        //
    }

    public function handle(ExportService $exportService): void
    {
        $this->export->markProcessing();

        $filters = $this->export->filters ?? [];
        [$data, $extraFilters] = $this->buildReportData($filters);
        $renderFilters = array_merge($filters, $extraFilters);

        $storage = app(CatboxStorage::class);
        $extension = $this->export->format === 'pdf' ? 'pdf' : 'xlsx';
        $localPath = 'report-exports/' . $this->export->uuid . '.' . $extension;

        try {
            if ($this->export->format === 'pdf') {
                $output = $exportService->buildPdf($this->export->report_type, $data, $renderFilters)->output();
                Storage::disk('local')->put($localPath, $output);
            } else {
                $exportClass = $exportService->getExportClass($this->export->report_type);

                if (!$exportClass) {
                    throw new \InvalidArgumentException("Unsupported report type: {$this->export->report_type}.");
                }

                Excel::store(new $exportClass($renderFilters), $localPath, 'local');
            }

            $url = $storage->storeFromDisk($localPath, 'local', 'report-exports');
        } finally {
            // The staged copy has served its purpose either way.
            Storage::disk('local')->delete($localPath);
        }

        $this->export->markCompleted($url, config('filesystems.default'), $storage->size($url));

        Notification::create([
            'user_id' => $this->export->requested_by,
            'user_type' => 'admin',
            'type' => 'report_export_ready',
            'title' => 'Report export ready',
            'message' => "Your {$this->export->format} export of the {$this->export->report_type} report is ready to download.",
            'data' => ['report_export_id' => $this->export->id],
            'action_url' => route('dashboard.reports.exports.download', $this->export),
        ]);
    }

    /**
     * Regenerate the report dataset exactly as the on-screen report would.
     *
     * @return array{0: Collection, 1: array<string, mixed>} data + extra render filters
     */
    protected function buildReportData(array $filters): array
    {
        $service = app(\App\Services\ReportService::class);

        $type = $this->export->report_type;

        if (!in_array($type, ['attendance', 'payment', 'performance', 'student'], true)) {
            throw new \InvalidArgumentException("Unsupported report type: {$type}.");
        }

        if ($type === 'student') {
            $students = $service->getStudentReport($filters);
            $students = $students instanceof Collection ? $students : collect($students);

            return [$students, ['stats' => $service->calculateStudentReportStats($students)]];
        }

        $report = match ($type) {
            'attendance' => $service->generateAttendanceReport($filters),
            'payment' => $service->generatePaymentReport($filters),
            default => $service->generatePerformanceReport($filters),
        };

        return [collect($report['data'] ?? []), ['summary' => $report['summary'] ?? []]];
    }

    public function failed(\Throwable $exception): void
    {
        $this->export->markFailed($exception->getMessage());

        Log::error('Queued report export failed', [
            'report_export_id' => $this->export->id,
            'report_type' => $this->export->report_type,
            'format' => $this->export->format,
            'error' => $exception->getMessage(),
        ]);

        try {
            Notification::create([
                'user_id' => $this->export->requested_by,
                'user_type' => 'admin',
                'type' => 'report_export_failed',
                'title' => 'Report export failed',
                'message' => "Your {$this->export->format} export of the {$this->export->report_type} report failed: {$exception->getMessage()}",
                'data' => ['report_export_id' => $this->export->id],
                'action_url' => route('dashboard.reports.export'),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to create export-failure notification', ['error' => $e->getMessage()]);
        }
    }
}
