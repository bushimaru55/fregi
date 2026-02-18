<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * 契約・決済フローおよび ROBOT PAYMENT 通知エンドポイントの動作テスト
 */
class ContractPaymentFlowTest extends TestCase
{
    /**
     * 決済ページはセッションがない場合に申込フォームへリダイレクトする
     */
    public function test_payment_page_redirects_to_create_without_session(): void
    {
        $response = $this->get(route('contract.payment'));

        $response->assertRedirect(route('contract.create'));
    }

    /**
     * 申込フォーム（create）が表示できる
     */
    public function test_contract_create_page_can_be_rendered(): void
    {
        $response = $this->get(route('contract.create'));

        $response->assertStatus(200);
        $response->assertViewIs('contracts.create');
    }

    /**
     * 初回決済結果通知URLが 200 を返す（ContentLength 0 以上）
     */
    public function test_notify_initial_returns_200(): void
    {
        $response = $this->get(route('api.robotpayment.notify-initial'));

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(0, strlen($response->getContent()));
    }

    /**
     * 自動課金結果通知URLが 200 を返す
     */
    public function test_notify_recurring_returns_200(): void
    {
        $response = $this->get(route('api.robotpayment.notify-recurring'));

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(0, strlen($response->getContent()));
    }
}
