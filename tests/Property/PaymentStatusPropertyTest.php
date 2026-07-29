<?php

namespace Tests\Property;

use App\Models\Payment;
use PHPUnit\Framework\TestCase;

class PaymentStatusPropertyTest extends TestCase
{
    public function test_pending_and_rejected_are_never_settled(): void
    {
        $this->assertNotContains(Payment::STATUS_PENDING, Payment::settledStatuses());
        $this->assertNotContains(Payment::STATUS_REJECTED, Payment::settledStatuses());
    }
}
