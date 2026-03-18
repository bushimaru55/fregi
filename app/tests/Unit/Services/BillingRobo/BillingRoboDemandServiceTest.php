<?php

namespace Tests\Unit\Services\BillingRobo;

use App\Models\Contract;
use App\Services\BillingRobo\BillingRoboDemandService;
use App\Services\BillingRobo\ContractToBillingLinesMapper;
use Tests\TestCase;

class BillingRoboDemandServiceTest extends TestCase
{
    public function test_build_demand_array_with_schedule_uses_schedule_values(): void
    {
        $contract = new Contract;
        $contract->id = 1;
        $contract->billing_code = 'BC00000001';
        $contract->billing_individual_number = 1;
        $contract->desired_start_date = now();

        $mockMapper = $this->createMock(ContractToBillingLinesMapper::class);
        $mockMapper->method('map')->willReturn([
            [
                'goods_name' => 'Plan',
                'price' => 10000,
                'quantity' => 1,
                'tax_category' => 1,
                'tax' => 10,
                'demand_type' => 0,
            ],
        ]);

        $client = $this->createMock(\App\Services\BillingRobo\BillingRoboApiClient::class);
        $service = new BillingRoboDemandService($client, $mockMapper);

        $schedule = [
            'issue_month' => 1,
            'issue_day' => 1,
            'sending_month' => 1,
            'sending_day' => 1,
            'deadline_month' => 1,
            'deadline_day' => 1,
        ];
        $demands = $service->buildDemandArray($contract, $schedule);

        $this->assertCount(1, $demands);
        $this->assertSame(1, $demands[0]['issue_month']);
        $this->assertSame(1, $demands[0]['issue_day']);
        $this->assertSame(1, $demands[0]['deadline_month']);
        $this->assertSame(1, $demands[0]['deadline_day']);
        $this->assertSame('Plan', $demands[0]['goods_name']);
        $this->assertSame(10000, $demands[0]['price']);
    }

    public function test_build_demand_array_without_schedule_uses_default_schedule(): void
    {
        $contract = new Contract;
        $contract->id = 1;
        $contract->billing_code = 'BC00000001';
        $contract->billing_individual_number = 1;
        $contract->desired_start_date = now();

        $mockMapper = $this->createMock(ContractToBillingLinesMapper::class);
        $mockMapper->method('map')->willReturn([
            [
                'goods_name' => 'Monthly',
                'price' => 5000,
                'quantity' => 1,
                'tax_category' => 1,
                'tax' => 10,
                'demand_type' => 1,
            ],
        ]);

        $client = $this->createMock(\App\Services\BillingRobo\BillingRoboApiClient::class);
        $service = new BillingRoboDemandService($client, $mockMapper);

        $demands = $service->buildDemandArray($contract, null);

        $this->assertCount(1, $demands);
        $this->assertSame(0, $demands[0]['issue_month']);
        $this->assertSame(1, $demands[0]['issue_day']);
        $this->assertSame(1, $demands[0]['type']);
    }
}
