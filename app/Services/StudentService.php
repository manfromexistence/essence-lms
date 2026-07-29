<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Batch;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class StudentService
{
    public function __construct(
        protected StudentIdGenerator $idGenerator
    ) {}

    /**
     * Create a new student with auto-generated registration number.
     */
    public function create(array $data): Student
    {
        return DB::transaction(function () use ($data) {
            // Get batch if provided
            $batch = isset($data['batch_id']) ? Batch::find($data['batch_id']) : null;

            // Generate registration number if not provided
            if (empty($data['registration_no'])) {
                $data['registration_no'] = $this->idGenerator->generateUnique($batch);
            }

            // Calculate due amount if total and paid are provided
            if (isset($data['total_amount']) && isset($data['paid_amount'])) {
                $data['due_amount'] = $data['total_amount'] - $data['paid_amount'];
            }

            return Student::create($data);
        });
    }

    /**
     * Update an existing student.
     */
    public function update(Student $student, array $data): Student
    {
        return DB::transaction(function () use ($student, $data) {
            // Recalculate due amount if amounts changed
            if (isset($data['total_amount']) || isset($data['paid_amount'])) {
                $total = $data['total_amount'] ?? $student->total_amount;
                $paid = $data['paid_amount'] ?? $student->paid_amount;
                $data['due_amount'] = $total - $paid;
            }

            $student->update($data);
            return $student->fresh();
        });
    }

    /**
     * Delete a student.
     */
    public function delete(Student $student): bool
    {
        return DB::transaction(function () use ($student) {
            // Delete related records if needed
            $student->attendances()->delete();
            $student->payments()->delete();
            $student->invoices()->delete();
            $student->results()->delete();

            return $student->delete();
        });
    }

    /**
     * Assign a student to a batch.
     */
    public function assignToBatch(Student $student, int $batchId): Student
    {
        $batch = Batch::findOrFail($batchId);

        // Check batch capacity
        $currentCount = Student::where('batch_id', $batchId)->count();
        if ($batch->max_students && $currentCount >= $batch->max_students) {
            throw new \RuntimeException('Batch has reached maximum capacity');
        }

        $student->update(['batch_id' => $batchId]);
        return $student->fresh();
    }

    /**
     * Get students filtered by enrollment year.
     */
    public function getByYear(int $year): Collection
    {
        return Student::enrolledInYear($year)
            ->with(['batch', 'user'])
            ->orderBy('name_bn')
            ->get();
    }

    /**
     * Get a student with all related data for profile view.
     */
    public function getWithRelations(int $id): Student
    {
        return Student::with([
            'batch',
            'batch.course',
            'user',
            'attendances' => function ($query) {
                $query->orderBy('date', 'desc')->limit(30);
            },
            'results' => function ($query) {
                $query->with('exam')->orderBy('created_at', 'desc');
            },
            'payments' => function ($query) {
                $query->orderBy('payment_date', 'desc');
            },
            'invoices' => function ($query) {
                $query->orderBy('due_date', 'desc');
            },
        ])->findOrFail($id);
    }

    /**
     * Get paginated students with optional filters.
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Student::with(['batch', 'user']);

        // Filter by year
        if (!empty($filters['year'])) {
            $query->enrolledInYear($filters['year']);
        }

        // Filter by batch
        if (!empty($filters['batch_id'])) {
            $query->inBatch($filters['batch_id']);
        }

        // Filter by class
        if (!empty($filters['class'])) {
            $query->where('class', $filters['class']);
        }

        // Filter by search term
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name_bn', 'like', "%{$search}%")
                  ->orWhere('registration_no', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uQ) use ($search) {
                      $uQ->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by dues
        if (!empty($filters['with_dues'])) {
            $query->withDues();
        }

        // Filter by featured
        if (!empty($filters['featured'])) {
            $query->featured();
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    /**
     * Get students with low attendance.
     */
    public function getWithLowAttendance(float $threshold = 75.0): Collection
    {
        return Student::with(['batch'])
            ->get()
            ->filter(function ($student) use ($threshold) {
                return $student->attendance_percentage < $threshold;
            });
    }

    /**
     * Get students with outstanding dues.
     */
    public function getWithDues(): Collection
    {
        return Student::withDues()
            ->with(['batch'])
            ->orderBy('due_amount', 'desc')
            ->get();
    }

    /**
     * Update student payment amounts.
     */
    public function updatePaymentAmounts(Student $student): Student
    {
        $totalPaid = $student->payments()->whereIn('status', \App\Models\Payment::settledStatuses())->sum('amount');
        $student->update([
            'paid_amount' => $totalPaid,
            'due_amount' => $student->total_amount - $totalPaid,
        ]);

        return $student->fresh();
    }

    /**
     * Get enrollment statistics by year.
     */
    public function getEnrollmentStats(): array
    {
        $currentYear = date('Y');
        $stats = [];

        for ($year = $currentYear - 4; $year <= $currentYear; $year++) {
            $stats[$year] = Student::enrolledInYear($year)->count();
        }

        return $stats;
    }

    /**
     * Get students count by batch.
     */
    public function getCountByBatch(): Collection
    {
        return Student::select('batch_id', DB::raw('count(*) as count'))
            ->with('batch')
            ->groupBy('batch_id')
            ->get();
    }

    /**
     * Bulk assign students to a batch.
     */
    public function bulkAssignToBatch(array $studentIds, int $batchId): int
    {
        $batch = Batch::findOrFail($batchId);

        // Check capacity
        $currentCount = Student::where('batch_id', $batchId)->count();
        $newCount = count($studentIds);

        if ($batch->max_students && ($currentCount + $newCount) > $batch->max_students) {
            throw new \RuntimeException('Adding these students would exceed batch capacity');
        }

        return Student::whereIn('id', $studentIds)->update(['batch_id' => $batchId]);
    }

    /**
     * Search students by name or registration number.
     */
    public function search(string $term, int $limit = 10): Collection
    {
        return Student::where('name_bn', 'like', "%{$term}%")
            ->orWhere('registration_no', 'like', "%{$term}%")
            ->orWhere('phone', 'like', "%{$term}%")
            ->with(['batch'])
            ->limit($limit)
            ->get();
    }
}
