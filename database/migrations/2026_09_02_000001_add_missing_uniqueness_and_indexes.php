<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the uniqueness and indexes the schema always assumed but never had.
 *
 * Every one of these was found missing against the migrated schema, and each has
 * a concrete consequence:
 *
 *   exam_results(student_id, exam_id)  duplicate result rows under concurrency;
 *                                     updateOrCreate is a read-then-write, so two
 *                                     simultaneous submits both insert and every
 *                                     aggregate double-counts the student
 *   certificates(student_id, course_id) CertificateService::issue() is a
 *                                     read-then-write, so a double issue either
 *                                     duplicates the certificate or collides on
 *                                     the deterministic certificate_number
 *   students.registration_no           silent duplicate registration numbers,
 *                                     which the public /results lookup resolves
 *                                     arbitrarily
 *   attendances                        date/status were unindexed while being
 *                                     filtered in ~15 places, and concurrent
 *                                     marking duplicated rows that then
 *                                     double-count in both numerator and
 *                                     denominator
 *
 * Duplicates are resolved before the index is added, because a unique index
 * cannot be created over data that violates it. Resolution is deliberately
 * conservative and logged.
 *
 * ---------------------------------------------------------------------------
 * BACK UP THE DATABASE BEFORE DEPLOYING THIS. IT IS NOT REVERSIBLE.
 * ---------------------------------------------------------------------------
 *
 * down() drops the constraints. It does not undo the de-duplication, because a
 * migration cannot resurrect rows it deleted. Rolling back returns you to a
 * schema without the indexes but with the duplicate rows already gone.
 *
 * What is kept, and what is discarded:
 *
 *   exam_results     keeps the highest awarded marks per student/exam; the
 *                    lower-scoring rows are deleted outright
 *   certificates     keeps the active certificate, then the most recently
 *                    issued; certificate_verifications are repointed at the
 *                    survivor so the audit trail is preserved, then the
 *                    duplicate rows are deleted
 *   students         no student is ever deleted: a colliding registration number
 *                    is re-numbered to <original>-2, -3, ... and the change is
 *                    logged
 *   attendances      keeps the earliest row per student/batch/day
 *
 * On a production database, take a copy first. On a fresh one there is nothing
 * to lose, because there are no duplicates to resolve.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->dedupeExamResults();
        $this->dedupeCertificates();
        $this->dedupeRegistrationNumbers();
        $this->dedupeAttendance();

        Schema::table('exam_results', function (Blueprint $table) {
            $table->unique(['student_id', 'exam_id'], 'exam_results_student_exam_unique');
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->unique(['student_id', 'course_id'], 'certificates_student_course_unique');
        });

        Schema::table('students', function (Blueprint $table) {
            // nullable is required: SQL treats NULLs as distinct, so rows with no
            // registration number yet do not collide.
            $table->unique('registration_no', 'students_registration_no_unique');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->unique(['student_id', 'batch_id', 'date'], 'attendances_student_batch_date_unique');
            $table->index('date', 'attendances_date_index');
            $table->index('status', 'attendances_status_index');
        });

        Schema::table('students', function (Blueprint $table) {
            // Filtered and sorted directly by the dues reports and payment
            // tracking screens.
            $table->index('due_amount', 'students_due_amount_index');
            $table->index('paid_amount', 'students_paid_amount_index');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('students_paid_amount_index');
            $table->dropIndex('students_due_amount_index');
            $table->dropUnique('students_registration_no_unique');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('attendances_status_index');
            $table->dropIndex('attendances_date_index');
            $table->dropUnique('attendances_student_batch_date_unique');
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->dropUnique('certificates_student_course_unique');
        });

        Schema::table('exam_results', function (Blueprint $table) {
            $table->dropUnique('exam_results_student_exam_unique');
        });
    }

    /**
     * Keep the row with the highest awarded marks; drop the rest.
     */
    private function dedupeExamResults(): void
    {
        $duplicates = DB::table('exam_results')
            ->select('student_id', 'exam_id')
            ->groupBy('student_id', 'exam_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $pair) {
            $rows = DB::table('exam_results')
                ->where('student_id', $pair->student_id)
                ->where('exam_id', $pair->exam_id)
                ->orderByDesc(DB::raw('COALESCE(obtained_marks, marks, 0)'))
                ->orderBy('id')
                ->get();

            $keep = $rows->shift();

            $ids = $rows->pluck('id');
            DB::table('exam_results')->whereIn('id', $ids)->delete();

            Log::warning('Removed duplicate exam_results rows.', [
                'student_id' => $pair->student_id,
                'exam_id' => $pair->exam_id,
                'kept_id' => $keep?->id,
                'deleted' => $ids->all(),
            ]);
        }
    }

    /**
     * Prefer an active certificate, then the most recently issued one.
     */
    private function dedupeCertificates(): void
    {
        $duplicates = DB::table('certificates')
            ->select('student_id', 'course_id')
            ->groupBy('student_id', 'course_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $pair) {
            $rows = DB::table('certificates')
                ->where('student_id', $pair->student_id)
                ->where('course_id', $pair->course_id)
                ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
                ->orderByDesc('issued_at')
                ->orderBy('id')
                ->get();

            $keep = $rows->shift();

            // Keep the audit trail for anything we remove.
            if ($keep) {
                DB::table('certificate_verifications')
                    ->whereIn('certificate_id', $rows->pluck('id'))
                    ->update(['certificate_id' => $keep->id]);
            }

            $ids = $rows->pluck('id');
            DB::table('certificates')->whereIn('id', $ids)->delete();

            Log::warning('Removed duplicate certificates.', [
                'student_id' => $pair->student_id,
                'course_id' => $pair->course_id,
                'kept_id' => $keep?->id,
                'deleted' => $ids->all(),
            ]);
        }
    }

    /**
     * Re-number rather than delete: the student is real, only the number clashes.
     */
    private function dedupeRegistrationNumbers(): void
    {
        $duplicates = DB::table('students')
            ->select('registration_no')
            ->whereNotNull('registration_no')
            ->groupBy('registration_no')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $group) {
            $students = DB::table('students')
                ->where('registration_no', $group->registration_no)
                ->orderBy('id')
                ->get();

            // The earliest student keeps the number.
            $students->shift();

            foreach ($students as $student) {
                $base = rtrim($group->registration_no, '-');
                $suffix = 2;
                $candidate = $base.'-'.$suffix;

                while (DB::table('students')->where('registration_no', $candidate)->exists()) {
                    $suffix++;
                    $candidate = $base.'-'.$suffix;
                }

                DB::table('students')->where('id', $student->id)
                    ->update(['registration_no' => $candidate]);

                Log::warning('Re-numbered a duplicate registration number.', [
                    'student_id' => $student->id,
                    'from' => $group->registration_no,
                    'to' => $candidate,
                ]);
            }
        }
    }

    /**
     * Keep the earliest row per student/batch/day.
     */
    private function dedupeAttendance(): void
    {
        $duplicates = DB::table('attendances')
            ->select('student_id', 'batch_id', 'date')
            ->groupBy('student_id', 'batch_id', 'date')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $pair) {
            $rows = DB::table('attendances')
                ->where('student_id', $pair->student_id)
                ->where('batch_id', $pair->batch_id)
                ->where('date', $pair->date)
                ->orderBy('id')
                ->get();

            $ids = $rows->pluck('id')->slice(1);
            DB::table('attendances')->whereIn('id', $ids)->delete();

            Log::warning('Removed duplicate attendance rows.', [
                'student_id' => $pair->student_id,
                'batch_id' => $pair->batch_id,
                'date' => $pair->date,
                'deleted' => $ids->all(),
            ]);
        }
    }
};
