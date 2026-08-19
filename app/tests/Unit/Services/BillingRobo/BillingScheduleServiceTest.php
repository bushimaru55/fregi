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

    public function test_is_within_last_5_business_days_returns_true_on_weekend_after_threshold(): void
    {
        // 2026/05 の最終5営業日は [25,26,27,28,29]、しきい値=25。
        // 25以降の土日（30=Sat, 31=Sun）も「以内」扱い。
        $sat = Carbon::create(2026, 5, 30, 12, 0, 0, 'Asia/Tokyo');
        $sun = Carbon::create(2026, 5, 31, 12, 0, 0, 'Asia/Tokyo');
        $this->assertTrue($this->service->isWithinLast5BusinessDaysOfMonth($sat));
        $this->assertTrue($this->service->isWithinLast5BusinessDaysOfMonth($sun));
    }

    public function test_is_within_last_5_business_days_returns_false_on_24th(): void
    {
        $date = Carbon::create(2026, 3, 24, 12, 0, 0, 'Asia/Tokyo');
        $this->assertFalse($this->service->isWithinLast5BusinessDaysOfMonth($date));
    }

    public function test_get_schedule_for_date_within_last_5_uses_prev_month_end_and_current_month_first(): void
    {
        // start_date は「課金開始月の1日」前提なので、
        // 請求書発行=start_date前月末日(-1/99)、決済期限=start_date月1日(0/1)で within も after も同値
        $date = Carbon::create(2026, 3, 25, 12, 0, 0, 'Asia/Tokyo');
        $schedule = $this->service->getScheduleForDate($date);
        $this->assertSame(-1, $schedule['issue_month']);
        $this->assertSame(99, $schedule['issue_day']);
        $this->assertSame(0, $schedule['deadline_month']);
        $this->assertSame(1, $schedule['deadline_day']);
    }

    public function test_get_schedule_for_date_after_last_5_uses_prev_month_end_and_current_month_first(): void
    {
        $date = Carbon::create(2026, 3, 24, 12, 0, 0, 'Asia/Tokyo');
        $schedule = $this->service->getScheduleForDate($date);
        $this->assertSame(-1, $schedule['issue_month']);
        $this->assertSame(99, $schedule['issue_day']);
        $this->assertSame(0, $schedule['deadline_month']);
        $this->assertSame(1, $schedule['deadline_day']);
    }

    public function test_get_schedule_for_application_uses_contract_desired_start_date(): void
    {
        $contract = new Contract;
        $contract->desired_start_date = Carbon::create(2026, 3, 31, 0, 0, 0, 'Asia/Tokyo');
        $schedule = $this->service->getScheduleForApplication($contract);
        $this->assertSame(-1, $schedule['issue_month']);
        $this->assertSame(99, $schedule['issue_day']);
    }

    public function test_get_schedule_for_application_card_keeps_deadline_first_of_start_date_month(): void
    {
        $contract = new Contract;
        $contract->payment_collection_method = Contract::PAYMENT_CARD;
        $contract->desired_start_date = Carbon::create(2026, 7, 10, 0, 0, 0, 'Asia/Tokyo');
        $schedule = $this->service->getScheduleForApplication($contract);
        $this->assertSame(-1, $schedule['issue_month']);
        $this->assertSame(99, $schedule['issue_day']);
        $this->assertSame(-1, $schedule['sending_month']);
        $this->assertSame(99, $schedule['sending_day']);
        $this->assertSame(0, $schedule['deadline_month']);
        $this->assertSame(1, $schedule['deadline_day']);
    }

    public function test_get_schedule_for_application_unset_payment_method_keeps_credit_deadline(): void
    {
        $contract = new Contract;
        $contract->desired_start_date = Carbon::create(2026, 7, 10, 0, 0, 0, 'Asia/Tokyo');
        $schedule = $this->service->getScheduleForApplication($contract);
        $this->assertSame(0, $schedule['deadline_month']);
        $this->assertSame(1, $schedule['deadline_day']);
    }

    public function test_get_schedule_for_application_bank_transfer_sets_deadline_end_of_start_date_month(): void
    {
        $contract = new Contract;
        $contract->payment_collection_method = Contract::PAYMENT_BANK_TRANSFER;
        $contract->desired_start_date = Carbon::create(2026, 7, 10, 0, 0, 0, 'Asia/Tokyo');
        $schedule = $this->service->getScheduleForApplication($contract);
        $this->assertSame(-1, $schedule['issue_month']);
        $this->assertSame(99, $schedule['issue_day']);
        $this->assertSame(-1, $schedule['sending_month']);
        $this->assertSame(99, $schedule['sending_day']);
        $this->assertSame(0, $schedule['deadline_month']);
        $this->assertSame(99, $schedule['deadline_day']);
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
