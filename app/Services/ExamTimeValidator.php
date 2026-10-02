<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAttempt;
use Carbon\Carbon;

class ExamTimeValidator
{
    /**
     * Check if a student can start an exam based on time window.
     */
    public function canStartExam(Exam $exam): bool
    {
        $now = Carbon::now();

        // Check if exam has started
        if ($exam->start_time && $now->lt($exam->start_time)) {
            return false;
        }

        // Check if exam has ended
        if ($exam->end_time && $now->gt($exam->end_time)) {
            return false;
        }

        return true;
    }

    /**
     * Get the current time status of an exam.
     *
     * @return string Returns 'not_started', 'active', or 'ended'
     */
    public function getTimeStatus(Exam $exam): string
    {
        $now = Carbon::now();

        // Check if exam hasn't started yet
        if ($exam->start_time && $now->lt($exam->start_time)) {
            return 'not_started';
        }

        // Check if exam has ended
        if ($exam->end_time && $now->gt($exam->end_time)) {
            return 'ended';
        }

        // Exam is currently active
        return 'active';
    }

    /**
     * Get remaining time in seconds for an exam attempt.
     *
     * @return int Returns seconds remaining, 0 if expired
     */
    public function getRemainingTime(ExamAttempt $attempt): int
    {
        // A finished attempt has no countdown left to show.
        if ($attempt->status !== 'in_progress') {
            return 0;
        }

        // Remaining time comes from the attempt, which is the single definition
        // of the countdown (duration limit *and* scheduled end_time, and it
        // already returns 0 for a missing exam or start time).
        //
        // This used to recompute it as
        // $now->diffInSeconds($attempt->started_at). Carbon computes
        // A.diff(B) as B - A, so a past timestamp yielded -7200 for two hours
        // elapsed, and duration - (-7200) reported 150 minutes remaining on a
        // 30 minute exam: the countdown never reached zero, the client's
        // auto-submit never fired, and a student watched an impossible timer.
        return $attempt->remaining_time;
    }

    /**
     * Check if an exam attempt has expired.
     */
    public function isAttemptExpired(ExamAttempt $attempt): bool
    {
        // Delegated for the same reason. This used to report true for an
        // already-submitted attempt, because getRemainingTime() short-circuited
        // to 0 for anything not in progress. A submitted attempt did not miss a
        // deadline; it met one.
        return $attempt->isExpired();
    }

    /**
     * Get a human-readable message about exam time status.
     */
    public function getTimeStatusMessage(Exam $exam): string
    {
        $status = $this->getTimeStatus($exam);

        switch ($status) {
            case 'not_started':
                if ($exam->start_time) {
                    return 'This exam will start on '.$exam->start_time->format('M d, Y \a\t h:i A');
                }

                return 'This exam has not started yet.';

            case 'ended':
                if ($exam->end_time) {
                    return 'This exam ended on '.$exam->end_time->format('M d, Y \a\t h:i A');
                }

                return 'This exam has ended.';

            case 'active':
                if ($exam->end_time) {
                    return 'This exam is active and will end on '.$exam->end_time->format('M d, Y \a\t h:i A');
                }

                return 'This exam is currently active.';

            default:
                return 'Exam status unknown.';
        }
    }

    /**
     * Get time until exam starts (in seconds).
     *
     * @return int Returns seconds until start, 0 if already started
     */
    public function getTimeUntilStart(Exam $exam): int
    {
        if (! $exam->start_time) {
            return 0;
        }

        // Raw timestamps. Carbon computes A.diff(B) as B - A, so this was
        // correct only by virtue of the guard above: with the guard in place
        // start_time is always in the future, so the signed result happened to
        // come out positive. Subtracting the timestamps directly is correct
        // whatever the inputs, and the guard becomes a courtesy rather than a
        // load-bearing assumption.
        return max(0, $exam->start_time->getTimestamp() - Carbon::now()->getTimestamp());
    }

    /**
     * Get time until exam ends (in seconds).
     *
     * @return int Returns seconds until end, 0 if already ended
     */
    public function getTimeUntilEnd(Exam $exam): int
    {
        if (! $exam->end_time) {
            return PHP_INT_MAX;
        }

        // Same reasoning as getTimeUntilStart().
        return max(0, $exam->end_time->getTimestamp() - Carbon::now()->getTimestamp());
    }
}
