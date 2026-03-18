<?php

namespace Tests\Unit\Services\BillingRobo;

use App\Models\Contract;
use App\Services\BillingRobo\BillingScheduleService;
use Carbon\Carbon;
use Tests\TestCase;

class BillingScheduleServiceTest extends TestCase
{
    private BillingScheduleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BillingScheduleService;
    }

    public function test_get_last_5_business_days_of_march_2026(): void
    {
        $days = $this->service->getLast5BusinessDaysOfMonth(2026, 3);
        $this->assertCount(5, $days);
        // March 2026: 25 Wed, 26 Thu, 27 Fri, 30 Mon, 31 Tue are last 5 weekdays
        $this->assertSame([25, 26, 27, 30, 31], $days);
    }

    public function test_is_within_last_5_business_days_returns_true_on_25th(): void
    {
        $date = Carbon::create(2026, 3, 25, 12, 0, 0, 'Asia/Tokyo');
        $this->assertTrue($this->service->isWithinLast5BusinessDaysOfMonth($date));
    }

    public function test_is_within_last_5_business_days_returns_true_on_31st(): void
    {
        $date = Carbon::create(2026, 3, 31, 12, 0, 0, 'Asia/Tokyo');
        $this->assertTrue($this->service->isWithinLast5BusinessDaysOfMonth($date));
    }

    public function test_is_within_last_5_business_days_returns_false_on_24th(): void
    {
        $date = Carbon::create(2026, 3, 24, 12, 0, 0, 'Asia/Tokyo');
        $this->assertFalse($this->service->isWithinLast5BusinessDaysOfMonth($date));
    }

    public function test_get_schedule_for_date_within_last_5_uses_end_of_month(): void
    {
        $date = Carbon::create(2026, 3, 25, 12, 0, 0, 'Asia/Tokyo');
        $schedule = $this->service->getScheduleForDate($date);
        $this->assertSame(0, $schedule['issue_month']);
        $this->assertSame(99, $schedule['issue_day']);
        $this->assertSame(0, $schedule['deadline_month']);
        $this->assertSame(99, $schedule['deadline_day']);
    }

    public function test_get_schedule_for_date_after_last_5_uses_next_month_first(): void
    {
        $date = Carbon::create(2026, 3, 24, 12, 0, 0, 'Asia/Tokyo');
        $schedule = $this->service->getScheduleForDate($date);
        $this->assertSame(1, $schedule['issue_month']);
        $this->assertSame(1, $schedule['issue_day']);
        $this->assertSame(1, $schedule['deadline_month']);
        $this->assertSame(1, $schedule['deadline_day']);
    }

    public function test_get_schedule_for_application_uses_contract_desired_start_date(): void
    {
        $contract = new Contract;
        $contract->desired_start_date = Carbon::create(2026, 3, 31, 0, 0, 0, 'Asia/Tokyo');
        $schedule = $this->service->getScheduleForApplication($contract);
        $this->assertSame(0, $schedule['issue_month']);
        $this->assertSame(99, $schedule['issue_day']);
    }
}
