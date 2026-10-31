<?php

namespace Tests\Unit;

use App\Model\FactModule;
use Tests\TestCase;

class DueDateTest extends TestCase
{
    private function due(string $period, ?string $override = null): ?string
    {
        return (new FactModule(['period' => $period, 'due_date' => $override]))->dueDate()?->format('Y-m-d');
    }

    public function test_eba_remittance_dates_for_quarter_ends(): void
    {
        $this->assertSame('2026-05-12', $this->due('2026-03-31'));
        $this->assertSame('2026-08-11', $this->due('2026-06-30'));
        $this->assertSame('2026-11-11', $this->due('2026-09-30'));
        $this->assertSame('2027-02-11', $this->due('2026-12-31'));
    }

    public function test_monthly_reports_and_weekends(): void
    {
        $this->assertSame('2026-11-16', $this->due('2026-10-31'));   // 15 Nov 2026 is a Sunday
        $this->assertSame('2026-02-16', $this->due('2026-01-31'));   // 15 Feb 2026 is a Sunday
        $this->assertSame('2027-05-12', $this->due('2027-03-31'));   // a Wednesday stays
    }

    public function test_a_deadline_set_on_the_instance_wins(): void
    {
        $this->assertSame('2026-12-01', $this->due('2026-09-30', '2026-12-01'));
    }
}
