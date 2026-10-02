<?php

namespace App\Console\Commands;

use App\Services\ExamTakingService;
use Illuminate\Console\Command;

/**
 * Closes exam attempts whose time has run out.
 *
 * Registered because ExamTakingService::autoSubmitExpired() had no callers, so
 * the 'expired' status was unreachable. This is a backstop only: the deadline is
 * also enforced inline when an answer is saved or the exam is submitted, so
 * nothing here is load-bearing for correctness.
 */
class AutoSubmitExpiredExams extends Command
{
    protected $signature = 'exams:auto-submit-expired';

    protected $description = 'Submit exam attempts that have passed their time limit';

    public function handle(ExamTakingService $examTaking): int
    {
        $count = $examTaking->autoSubmitExpired();

        $this->info($count === 0
            ? 'No expired attempts.'
            : "Submitted {$count} expired attempt(s).");

        return self::SUCCESS;
    }
}
