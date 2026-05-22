<?php

namespace Tests\Unit\Services\BillingRobo;

use App\Models\Contract;
use App\Models\SiteSetting;
use App\Services\BillingRobo\BillingScheduleService;
use Carbon\Carbon;
use Tests\TestCase;

class BillingScheduleServiceTest extends TestCase
{
    private BillingScheduleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // テストでは SiteSetting の保存値ではなくコード上の規定値（fcfcc3b 以降の正規）を検証する
        SiteSetting::query()->where('key', 'billing_cycle_schedule')->delete();
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

    public function test_get_schedule_for_date_within_last_5_uses_next_month_end_for_issue(): void
    {
        // 月末5営業日以内 → 翌月末発行・翌々月1日決済
        $date = Carbon::create(2026, 3, 25, 12, 0, 0, 'Asia/Tokyo');
        $schedule = $this->service->getScheduleForDate($date);
        $this->assertSame(1, $schedule['issue_month']);
        $this->assertSame(99, $schedule['issue_day']);
        $this->assertSame(2, $schedule['deadline_month']);
        $this->assertSame(1, $schedule['deadline_day']);
    }

    public function test_get_schedule_for_date_after_last_5_uses_current_month_end_for_issue(): void
    {
        // 月末5営業日以前 → 当月末発行・翌月1日決済
        $date = Carbon::create(2026, 3, 24, 12, 0, 0, 'Asia/Tokyo');
        $schedule = $this->service->getScheduleForDate($date);
        $this->assertSame(0, $schedule['issue_month']);
        $this->assertSame(99, $schedule['issue_day']);
        $this->assertSame(1, $schedule['deadline_month']);
        $this->assertSame(1, $schedule['deadline_day']);
    }

    public function test_get_schedule_for_application_uses_contract_desired_start_date(): void
    {
        $contract = new Contract;
        $contract->desired_start_date = Carbon::create(2026, 3, 31, 0, 0, 0, 'Asia/Tokyo');
        $schedule = $this->service->getScheduleForApplication($contract);
        // 3/31 は月末5営業日以内 → within ブロック
        $this->assertSame(1, $schedule['issue_month']);
        $this->assertSame(99, $schedule['issue_day']);
    }

    public function test_billing_start_date_for_application_after_cutoff_returns_next_month_first(): void
    {
        // 2026/05/22 (Fri) は May の月末5営業日(25-29)に含まれない → 翌月1日 = 2026/06/01
        $contract = new Contract;
        $contract->desired_start_date = Carbon::create(2026, 5, 22, 0, 0, 0, 'Asia/Tokyo');
        $startDate = $this->service->getBillingStartDateForApplication($contract);
        $this->assertSame('2026-06-01', $startDate->format('Y-m-d'));
    }

    public function test_billing_start_date_for_application_within_cutoff_returns_after_next_month_first(): void
    {
        // 2026/05/28 (Thu) は May の月末5営業日(25-29)に含まれる → 翌々月1日 = 2026/07/01
        $contract = new Contract;
        $contract->desired_start_date = Carbon::create(2026, 5, 28, 0, 0, 0, 'Asia/Tokyo');
        $startDate = $this->service->getBillingStartDateForApplication($contract);
        $this->assertSame('2026-07-01', $startDate->format('Y-m-d'));
    }

    public function test_billing_start_date_for_application_first_of_month_returns_next_month_first(): void
    {
        // 2026/05/01 は月末5営業日に含まれない → 翌月1日 = 2026/06/01
        $contract = new Contract;
        $contract->desired_start_date = Carbon::create(2026, 5, 1, 0, 0, 0, 'Asia/Tokyo');
        $startDate = $this->service->getBillingStartDateForApplication($contract);
        $this->assertSame('2026-06-01', $startDate->format('Y-m-d'));
    }
}
