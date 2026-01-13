<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContractRequest;
use App\Models\Contract;
use App\Models\ContractPlan;
use App\Models\Payment;
use App\Models\SiteSetting;
use App\Services\EncryptionService;
use App\Services\FregiApiService;
use App\Services\FregiConfigService;
use App\Services\FregiPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ContractController extends Controller
{
    protected FregiConfigService $configService;
    protected FregiPaymentService $paymentService;
    protected FregiApiService $apiService;
    protected EncryptionService $encryptionService;

    public function __construct(
        FregiConfigService $configService,
        FregiPaymentService $paymentService,
        FregiApiService $apiService,
        EncryptionService $encryptionService
    ) {
        $this->configService = $configService;
        $this->paymentService = $paymentService;
        $this->apiService = $apiService;
        $this->encryptionService = $encryptionService;
    }

    /**
     * 申込フォームを表示
     */
    public function create(Request $request): View
    {
        try {
            $query = ContractPlan::active();
            $planIds = [];
            
            // plansパラメータによる絞り込み（複数プランID）
            if ($request->has('plans')) {
                $planIdsString = $request->input('plans');
                $planIds = array_filter(array_map('intval', explode(',', $planIdsString)));
                if (!empty($planIds)) {
                    $query->whereIn('id', $planIds);
                }
            }
            
            $plans = $query->orderBy('display_order')->get();
            $termsOfService = SiteSetting::getValue('terms_of_service', '');
            
            // ログ記録
            Log::channel('contract_payment')->info('申込フォーム表示', [
                'url' => $request->fullUrl(),
                'plan_ids' => $planIds,
                'plan_count' => $plans->count(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toIso8601String(),
            ]);
            
            return view('contracts.create', compact('plans', 'termsOfService'));
        } catch (\Exception $e) {
            Log::channel('contract_payment')->error('申込フォーム表示エラー', [
                'url' => $request->fullUrl(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
            ]);
            throw $e;
        }
    }

    /**
     * 申込内容確認画面を表示
     */
    public function confirm(ContractRequest $request): View
    {
        try {
            // セッションからエラーメッセージをクリア（正常なPOSTリクエストなので）
            $request->session()->forget('error');
            
            $validated = $request->validated();
            $plan = ContractPlan::findOrFail($validated['contract_plan_id']);
            $termsOfService = SiteSetting::getValue('terms_of_service', '');
            
            // ログ記録（個人情報はマスク）
            $logData = [
                'contract_plan_id' => $validated['contract_plan_id'],
                'plan_name' => $plan->name,
                'plan_price' => $plan->price,
                'company_name' => substr($validated['company_name'], 0, 3) . '***', // マスク
                'email' => substr($validated['email'], 0, 3) . '***', // マスク
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toIso8601String(),
            ];
            Log::channel('contract_payment')->info('確認画面表示', $logData);
            
            return view('contracts.confirm', [
                'data' => $validated,
                'plan' => $plan,
                'termsOfService' => $termsOfService,
            ]);
        } catch (\Exception $e) {
            Log::channel('contract_payment')->error('確認画面表示エラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
                'request_data' => $request->except(['password', '_token']),
            ]);
            throw $e;
        }
    }

    /**
     * 申込内容確認画面（GETリクエスト: トークンベースで閲覧可能）
     */
    public function confirmGet(Request $request)
    {
        // トークンパラメータがある場合は閲覧用表示
        if ($request->has('token')) {
            $token = $request->input('token');
            $cacheKey = 'contract_confirm_view:' . $token;
            $viewData = Cache::get($cacheKey);
            
            if (!$viewData) {
                return redirect()->route('contract.create')
                    ->with('error', '閲覧用URLの有効期限が切れています。');
            }
            
            $plan = ContractPlan::findOrFail($viewData['contract_plan_id']);
            $termsOfService = SiteSetting::getValue('terms_of_service', '');
            
            return view('contracts.confirm', [
                'data' => $viewData,
                'plan' => $plan,
                'termsOfService' => $termsOfService,
                'isViewOnly' => true, // 閲覧専用フラグ（フォーム送信を無効化）
            ]);
        }
        
        // トークンがない場合は通常通りエラー
        return redirect()->route('contract.create')
            ->with('error', '確認画面に直接アクセスすることはできません。フォームからお申し込みください。');
    }

    /**
     * 申込を保存し、決済へ進む
     */
    public function store(ContractRequest $request): RedirectResponse
    {
        $startTime = microtime(true);
        
        return DB::transaction(function () use ($request, $startTime) {
            try {
                $validated = $request->validated();
                
                Log::channel('contract_payment')->info('決済処理開始', [
                    'contract_plan_id' => $validated['contract_plan_id'],
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'timestamp' => now()->toIso8601String(),
                ]);
                
                // 契約情報を作成（ステータス: draft）
                $contract = Contract::create([
                    ...$validated,
                    'status' => 'draft',
                ]);

                Log::channel('contract_payment')->info('契約作成完了', [
                    'contract_id' => $contract->id,
                    'contract_plan_id' => $contract->contract_plan_id,
                    'status' => $contract->status,
                ]);

                // 決済情報を作成
                $plan = $contract->contractPlan;
                // 伝票番号（ID）は最大20文字（F-REGI仕様）
                // 形式: ORD + YmdHis + 契約ID（パディング）
                $timestamp = now()->format('YmdHis'); // 14文字
                $contractId = str_pad((string)$contract->id, 4, '0', STR_PAD_LEFT); // 最大4桁
                $orderId = 'ORD' . $timestamp . $contractId; // 最大21文字（契約IDが4桁の場合）
                // 20文字を超える場合は切り詰める（末尾を優先）
                if (strlen($orderId) > 20) {
                    $orderId = substr($orderId, -20);
                }
                
                $payment = Payment::create([
                    'company_id' => 1, // 仮のcompany_id（マルチテナント対応時に変更）
                    'contract_id' => $contract->id,
                    'orderid' => $orderId,
                    'amount' => $plan->price,
                    'currency' => 'JPY',
                    'payment_method' => 'credit_card',
                    'status' => 'created',
                ]);

                Log::channel('contract_payment')->info('決済情報作成完了', [
                    'payment_id' => $payment->id,
                    'contract_id' => $contract->id,
                    'orderid' => $orderId,
                    'amount' => $payment->amount,
                    'status' => $payment->status,
                ]);

                // 契約に決済IDを紐付け、ステータスを更新
                $contract->update([
                    'payment_id' => $payment->id,
                    'status' => 'pending_payment',
                ]);

                // F-REGI設定を取得
                $environment = env('APP_ENV') === 'production' ? 'prod' : 'test';
                $fregiConfig = $this->configService->getActiveConfig(
                    $payment->company_id,
                    $environment
                );

                if (!$fregiConfig) {
                    Log::channel('contract_payment')->error('F-REGI設定が見つかりません', [
                        'company_id' => $payment->company_id,
                        'environment' => $environment,
                        'payment_id' => $payment->id,
                    ]);
                    throw new \Exception('F-REGI設定が見つかりません');
                }

                // 接続パスワードを復号
                $connectPassword = $this->encryptionService->decryptSecret($fregiConfig->connect_password_enc);

                // 決済タイプに応じた処理
                $billingType = $plan->billing_type ?? 'one_time';
                
                // 発行受付APIのパラメータを構築
                // 注意: リダイレクト決済（SaaS型）ではMONTHLYMODEパラメータを指定できない
                // F-REGIエラーコード CSA1-1-107: "リダイレクト決済（SaaS型）発行処理時の洗替利用フラグは指定できません"
                $apiParams = [
                    'SHOPID' => $fregiConfig->shopid,
                    'ID' => $payment->orderid,
                    'PAY' => (string)$payment->amount,
                ];
                
                // 月額課金の場合も、リダイレクト決済ではMONTHLYMODEを送信しない
                // 月額課金の実装が必要な場合は、別のAPIを使用する必要がある
                // if ($billingType === 'monthly') {
                //     $apiParams['MONTHLYMODE'] = '0'; // リダイレクト決済では指定不可
                // }

                Log::channel('contract_payment')->info('F-REGI API呼び出し開始', [
                    'payment_id' => $payment->id,
                    'orderid' => $payment->orderid,
                    'api_params' => $apiParams,
                    'billing_type' => $billingType,
                    'environment' => $environment,
                ]);

                // 発行受付APIを呼び出し
                $apiResult = $this->apiService->issuePayment($apiParams, $fregiConfig);

                Log::channel('contract_payment')->info('F-REGI API呼び出し結果', [
                    'payment_id' => $payment->id,
                    'result' => $apiResult['result'] ?? 'UNKNOWN',
                    'settleno' => $apiResult['settleno'] ?? null,
                    'error_message' => $apiResult['error_message'] ?? null,
                ]);

                if ($apiResult['result'] !== 'OK') {
                    Log::channel('contract_payment')->error('F-REGI API呼び出し失敗', [
                        'payment_id' => $payment->id,
                        'error_message' => $apiResult['error_message'] ?? '不明なエラー',
                        'api_result' => $apiResult,
                    ]);
                    
                    return back()->withErrors([
                        'error' => '決済の発行に失敗しました: ' . ($apiResult['error_message'] ?? '不明なエラー'),
                    ]);
                }

                // 発行番号を取得
                $settleno = $apiResult['settleno'];

                // Paymentに発行番号を保存
                $payment->update([
                    'settleno' => $settleno,
                    'status' => 'redirect_issued',
                ]);

                // チェックサムを生成（お支払い方法選択画面URL用）
                $checksum = $this->paymentService->generatePaymentPageChecksum(
                    $fregiConfig->shopid,
                    $connectPassword,
                    $settleno,
                    $payment->orderid
                );

                // お支払い方法選択画面URLを生成
                $paymentPageUrl = $this->apiService->getPaymentPageUrlWithParams(
                    $settleno,
                    $checksum,
                    $fregiConfig
                );

                $processingTime = round((microtime(true) - $startTime) * 1000, 2);

                Log::channel('contract_payment')->info('決済処理完了・リダイレクト', [
                    'payment_id' => $payment->id,
                    'contract_id' => $contract->id,
                    'orderid' => $payment->orderid,
                    'settleno' => $settleno,
                    'payment_page_url' => $paymentPageUrl,
                    'processing_time_ms' => $processingTime,
                ]);

                // リダイレクト
                return redirect($paymentPageUrl);
            } catch (\Exception $e) {
                $processingTime = round((microtime(true) - $startTime) * 1000, 2);
                
                Log::channel('contract_payment')->error('決済処理エラー', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'ip' => $request->ip(),
                    'processing_time_ms' => $processingTime,
                ]);
                
                throw $e;
            }
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

}
