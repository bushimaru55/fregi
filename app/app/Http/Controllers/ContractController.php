<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContractRequest;
use App\Models\Contract;
use App\Models\ContractPlan;
use App\Models\Payment;
use App\Models\SiteSetting;
use App\Services\FregiConfigService;
use App\Services\FregiPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ContractController extends Controller
{
    protected FregiConfigService $configService;
    protected FregiPaymentService $paymentService;

    public function __construct(FregiConfigService $configService, FregiPaymentService $paymentService)
    {
        $this->configService = $configService;
        $this->paymentService = $paymentService;
    }

    /**
     * 申込フォームを表示
     */
    public function create(): View
    {
        $plans = ContractPlan::active()->get();
        $termsOfService = SiteSetting::getValue('terms_of_service', '');
        return view('contracts.create', compact('plans', 'termsOfService'));
    }

    /**
     * 申込内容確認画面を表示
     */
    public function confirm(ContractRequest $request): View
    {
        $validated = $request->validated();
        $plan = ContractPlan::findOrFail($validated['contract_plan_id']);
        $termsOfService = SiteSetting::getValue('terms_of_service', '');
        
        return view('contracts.confirm', [
            'data' => $validated,
            'plan' => $plan,
            'termsOfService' => $termsOfService,
        ]);
    }

    /**
     * 申込を保存し、決済へ進む
     */
    public function store(ContractRequest $request): View
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validated();
            
            // 契約情報を作成（ステータス: draft）
            $contract = Contract::create([
                ...$validated,
                'status' => 'draft',
            ]);

            // 決済情報を作成
            $plan = $contract->contractPlan;
            $orderId = 'ORD-' . now()->format('YmdHis') . '-' . $contract->id;
            
            $payment = Payment::create([
                'company_id' => 1, // 仮のcompany_id（マルチテナント対応時に変更）
                'contract_id' => $contract->id,
                'orderid' => $orderId,
                'amount' => $plan->price,
                'currency' => 'JPY',
                'payment_method' => 'credit_card',
                'status' => 'created',
            ]);

            // 契約に決済IDを紐付け、ステータスを更新
            $contract->update([
                'payment_id' => $payment->id,
                'status' => 'pending_payment',
            ]);

            // F-REGI設定を取得
            $fregiConfig = $this->configService->getActiveConfig(
                1, // 仮のcompany_id
                'test' // 環境（本番時は'prod'に変更）
            );

            // F-REGIへのPOSTパラメータを生成
            $postParams = $this->paymentService->initiatePayment($payment, $fregiConfig);

            // F-REGI決済画面へ自動POSTするためのフォームを表示
            return view('payment.redirect', [
                'fregiUrl' => 'https://fregi.example.com/payment', // 実際のF-REGI決済URL
                'postParams' => $postParams,
            ]);
        });
    }

    /**
     * 申込完了画面（決済成功後）
     */
    public function complete(Request $request): View
    {
        $orderId = $request->input('orderid');
        $payment = Payment::where('orderid', $orderId)->with('contract.contractPlan')->firstOrFail();
        $contract = $payment->contract;

        return view('contracts.complete', [
            'contract' => $contract,
            'payment' => $payment,
        ]);
    }

    /**
     * 管理画面: 契約一覧
     */
    public function index(): View
    {
        $contracts = Contract::with(['contractPlan', 'payment'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('admin.contracts.index', compact('contracts'));
    }

    /**
     * 管理画面: 契約詳細
     */
    public function show(Contract $contract): View
    {
        $contract->load(['contractPlan', 'payment.events']);
        return view('admin.contracts.show', compact('contract'));
    }
}
