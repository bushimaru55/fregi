<?php

namespace Tests\Unit\Services\BillingRobo;

use App\Models\Contract;
use App\Services\BillingRobo\BillingRoboApiClient;
use App\Services\BillingRobo\BillingRoboBillingService;
use Tests\TestCase;

class BillingRoboBillingServiceTest extends TestCase
{
    public function test_build_billing_body_without_schedule_has_no_issue_month_in_individual(): void
    {
        $client = $this->createMock(BillingRoboApiClient::class);
        $service = new BillingRoboBillingService($client);
        $contract = new Contract;
        $contract->id = 1;
        $contract->company_name = 'Test Co';
        $contract->department = 'Dept';
        $contract->email = 'test@example.com';

        $body = $service->buildBillingBody($contract, null);

        $individual = $body['billing'][0]['individual'][0];
        $this->assertArrayNotHasKey('issue_month', $individual);
        $this->assertArrayNotHasKey('deadline_day', $individual);
    }

    public function test_build_billing_body_with_schedule_adds_schedule_to_individual(): void
    {
        $client = $this->createMock(BillingRoboApiClient::class);
        $service = new BillingRoboBillingService($client);
        $contract = new Contract;
        $contract->id = 1;
        $contract->company_name = 'Test Co';
        $contract->department = 'Dept';
        $contract->email = 'test@example.com';

        $schedule = [
            'issue_month' => 1,
            'issue_day' => 1,
            'sending_month' => 1,
            'sending_day' => 1,
            'deadline_month' => 1,
            'deadline_day' => 1,
        ];
        $body = $service->buildBillingBody($contract, $schedule);

        $individual = $body['billing'][0]['individual'][0];
        $this->assertSame(1, $individual['billing_method']);
        $this->assertSame(1, $individual['issue_month']);
        $this->assertSame(1, $individual['issue_day']);
        $this->assertSame(1, $individual['deadline_month']);
        $this->assertSame(1, $individual['deadline_day']);
    }
}
