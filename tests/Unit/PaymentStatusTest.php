<?php

namespace Tests\Unit;

use App\Models\Payment;
use PHPUnit\Framework\TestCase;

class PaymentStatusTest extends TestCase
{
    public function test_legacy_approved_and_completed_are_both_settled(): void
    {
        $this->assertContains(Payment::STATUS_APPROVED, Payment::settledStatuses());
        $this->assertContains(Payment::STATUS_COMPLETED, Payment::settledStatuses());
    }

    public function test_payment_helpers_use_the_canonical_settled_definition(): void
    {
        $legacy = new Payment(['status' => Payment::STATUS_APPROVED]);
        $completed = new Payment(['status' => Payment::STATUS_COMPLETED]);
        $pending = new Payment(['status' => Payment::STATUS_PENDING]);

        $this->assertTrue($legacy->isApproved());
        $this->assertTrue($legacy->isCompleted());
        $this->assertTrue($completed->isApproved());
        $this->assertFalse($pending->isCompleted());
    }
}
