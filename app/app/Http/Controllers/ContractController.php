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
            $hasPlanIdsParam = false;
            
            // plansパラメータによる絞り込み（複数プランID）
            if ($request->has('plans')) {
                $planIdsString = $request->input('plans');
                $planIds = array_filter(array_map('intval', explode(',', $planIdsString)));
                if (!empty($planIds)) {
                    $query->whereIn('id', $planIds);
                    $hasPlanIdsParam = true;
                }
            }
            
            $plans = $query->orderBy('display_order')->get();
            
            // プランIDが指定されているのに、該当するプランが見つからない場合は404
            if ($hasPlanIdsParam && $plans->isEmpty()) {
                Log::channel('contract_payment')->warning('指定されたプランが見つかりません', [
                    'url' => $request->fullUrl(),
                    'plan_ids' => $planIds,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'timestamp' => now()->toIso8601String(),
                ]);
                abort(404, '指定されたプランが見つかりません。プランが存在しないか、現在公開されていません。');
            }
            
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
            // 詳細なエラーログを記録
            Log::channel('contract_payment')->error('申込フォーム表示エラー（詳細）', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_class' => get_class($e),
                'stack_trace' => $e->getTraceAsString(),
                'request_method' => $request->method(),
                'request_url' => $request->fullUrl(),
                'request_path' => $request->path(),
                'request_headers' => [
                    'user-agent' => $request->userAgent(),
                    'referer' => $request->header('referer'),
                ],
                'session_id' => $request->session()->getId(),
                'ip' => $request->ip(),
                'fregi_env' => config('fregi.environment', 'unknown'),
                'app_env' => config('app.env', 'unknown'),
                'php_version' => PHP_VERSION,
                'timestamp' => now()->toIso8601String(),
            ]);
            throw $e;
        }
    }

    /**
     * 申込内容確認画面を表示
     * POSTリクエスト時は必ず確認画面を表示する
     */
    public function confirm(ContractRequest $request): View
    {
        try {
            // POSTリクエストであることを確認（GETリクエストの場合はconfirmGet()に処理を委譲）
            if (!$request->isMethod('post')) {
                return $this->confirmGet($request);
            }
            
            // セッションからエラーメッセージをクリア（正常なPOSTリクエストなので）
            $request->session()->forget('error');
            
            // バリデーション済みデータを取得（バリデーションエラー時はfailedValidation()でハンドリング）
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
            Log::channel('contract_payment')->info('確認画面表示（POST）', $logData);
            
            return view('contracts.confirm', [
                'data' => $validated,
                'plan' => $plan,
                'termsOfService' => $termsOfService,
                'environment' => config('fregi.environment', 'test'), // F-REGI環境設定
            ]);
        } catch (\Exception $e) {
            // 詳細なエラーログを記録
            Log::channel('contract_payment')->error('確認画面表示エラー（詳細）', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_class' => get_class($e),
                'stack_trace' => $e->getTraceAsString(),
                'previous_exception' => $e->getPrevious() ? [
                    'message' => $e->getPrevious()->getMessage(),
                    'file' => $e->getPrevious()->getFile(),
                    'line' => $e->getPrevious()->getLine(),
                ] : null,
                'request_method' => $request->method(),
                'request_url' => $request->fullUrl(),
                'request_path' => $request->path(),
                'request_query' => $request->query(),
                'request_headers' => [
                    'user-agent' => $request->userAgent(),
                    'referer' => $request->header('referer'),
                    'accept' => $request->header('accept'),
                ],
                'request_data' => $request->except(['password', '_token', 'pan1', 'pan2', 'pan3', 'pan4', 'scode']),
                'session_id' => $request->session()->getId(),
                'session_data' => $request->session()->all(),
                'ip' => $request->ip(),
                'fregi_env' => config('fregi.environment', 'unknown'),
                'app_env' => config('app.env', 'unknown'),
                'app_debug' => config('app.debug', false),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'timestamp' => now()->toIso8601String(),
            ]);
            throw $e;
        }
    }

    /**
     * 申込内容確認画面（GETリクエスト: トークンベースで閲覧可能、またはエラー時の再表示）
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
                'environment' => config('fregi.environment', 'test'), // F-REGI環境設定
            ]);
        }
        
        // セッションに確認画面のデータがある場合（エラー時の再表示）
        if ($request->session()->has('contract_confirm_data')) {
            $viewData = $request->session()->get('contract_confirm_data');
            $errorMessage = $request->session()->get('contract_confirm_error');
            $validationErrors = $request->session()->get('contract_confirm_errors');
            
            // セッションからデータを削除（一度だけ表示）
            $request->session()->forget('contract_confirm_data');
            $request->session()->forget('contract_confirm_error');
            $request->session()->forget('contract_confirm_errors');
            
            try {
                $plan = ContractPlan::findOrFail($viewData['contract_plan_id']);
                $termsOfService = SiteSetting::getValue('terms_of_service', '');
                
                return view('contracts.confirm', [
                    'data' => $viewData,
                    'plan' => $plan,
                    'termsOfService' => $termsOfService,
                    'error' => $errorMessage, // エラーメッセージを渡す
                    'validation_errors' => $validationErrors, // バリデーションエラーを渡す
                    'environment' => config('fregi.environment', 'test'), // F-REGI環境設定
                ]);
            } catch (\Exception $e) {
                // プランが見つからない場合はcreateに戻る
                Log::channel('contract_payment')->error('確認画面表示：プランが見つかりません（詳細）', [
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'error_class' => get_class($e),
                    'stack_trace' => $e->getTraceAsString(),
                    'contract_plan_id' => $viewData['contract_plan_id'] ?? null,
                    'view_data_keys' => array_keys($viewData ?? []),
                    'request_method' => $request->method(),
                    'request_url' => $request->fullUrl(),
                    'session_id' => $request->session()->getId(),
                    'fregi_env' => config('fregi.environment', 'unknown'),
                    'app_env' => config('app.env', 'unknown'),
                    'timestamp' => now()->toIso8601String(),
                ]);
                return redirect()->route('contract.create')
                    ->with('error', '選択されたプランが見つかりませんでした。もう一度お試しください。');
            }
        }
        
        // トークンもセッションデータもない場合は通常通りエラー
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
                // FREGI_ENVを使用して設定を検索（APP_ENVとは独立）
                $targetEnv = config('fregi.environment', 'test');
                $fregiConfig = $this->configService->getActiveConfig(
                    $payment->company_id,
                    $targetEnv
                );

                if (!$fregiConfig) {
                    Log::channel('contract_payment')->error('F-REGI設定が見つかりません', [
                        'company_id' => $payment->company_id,
                        'target_env' => $targetEnv,
                        'payment_id' => $payment->id,
                        'fregi_env_config' => config('fregi.environment', 'test'),
                    ]);
                    // エラーメッセージはFregiConfigServiceから投げられるため、ここでは再スローしない
                    // （実際にはgetActiveConfig()内で例外が投げられる）
                }

                // 決済タイプに応じた処理
                $billingType = $plan->billing_type ?? 'one_time';
                
                // カード情報を取得
                $pan1 = $validated['pan1'];
                $pan2 = $validated['pan2'];
                $pan3 = $validated['pan3'];
                $pan4 = $validated['pan4'];
                $cardExpiryMonth = $validated['card_expiry_month'];
                $cardExpiryYear = $validated['card_expiry_year'];
                // 年を2桁に変換（4桁の場合は下2桁を取得）
                if (strlen($cardExpiryYear) === 4) {
                    $cardExpiryYear = substr($cardExpiryYear, -2);
                }
                $cardName = strtoupper($validated['card_name']); // 大文字に変換
                $scode = $validated['scode'] ?? null;

                // オーソリ処理（authm.cgi）のパラメータを構築
                $apiParams = [
                    'SHOPID' => $fregiConfig->shopid,
                    'ID' => $payment->orderid,
                    'PAY' => (string)$payment->amount,
                    'PAN1' => $pan1,
                    'PAN2' => $pan2,
                    'PAN3' => $pan3,
                    'PAN4' => $pan4,
                    'CARDEXPIRY1' => $cardExpiryMonth,
                    'CARDEXPIRY2' => $cardExpiryYear,
                    'CARDNAME' => $cardName,
                    'IP' => $request->ip(),
                ];
                
                // セキュリティコードがある場合は追加
                if ($scode) {
                    $apiParams['SCODE'] = $scode;
                }
                
                // 決済タイプに応じてMONTHLYパラメータを設定
                $customerId = null; // 月額課金の場合のみ設定
                if ($billingType === 'monthly') {
                    // 月額課金の場合
                    $customerId = $contract->generateCustomerId();
                    $apiParams['MONTHLY'] = '1';
                    $apiParams['MONTHLYMODE'] = '0'; // 月次課金
                    $apiParams['CUSTOMERID'] = $customerId;
                } else {
                    // 一回限りの決済の場合
                    $apiParams['MONTHLY'] = '0'; // 即時決済
                }

                // ログ用にマスクしたパラメータを作成
                $logParams = $apiParams;
                $logParams['PAN1'] = '****';
                $logParams['PAN2'] = '****';
                $logParams['PAN3'] = '****';
                $logParams['PAN4'] = '****';
                if (isset($logParams['SCODE'])) {
                    $logParams['SCODE'] = '****';
                }
                
                Log::channel('contract_payment')->info('F-REGIオーソリ処理開始', [
                    'payment_id' => $payment->id,
                    'orderid' => $payment->orderid,
                    'billing_type' => $billingType,
                    'environment' => $targetEnv, // $targetEnvを使用（config('fregi.environment', 'test')）
                    'api_params' => $logParams, // ログにはマスクした値を出力
                ]);

                // payment_events: fregi_authorize_request を記録
                DB::table('payment_events')->insert([
                    'payment_id' => $payment->id,
                    'event_type' => 'fregi_authorize_request',
                    'payload' => json_encode([
                        'url' => config('fregi.auth_url'),
                        'fregi_env' => $targetEnv,
                        'shopid' => $fregiConfig->shopid,
                        'orderid' => $payment->orderid,
                        'amount' => $payment->amount,
                        'billing_type' => $billingType,
                        'has_card_info' => true,
                        'has_customer_id' => !empty($customerId),
                    ]),
                    'created_at' => now(),
                ]);

                // オーソリ処理（authm.cgi）を呼び出し
                $apiResult = $this->apiService->authorizePayment($apiParams, $fregiConfig);

                Log::channel('contract_payment')->info('F-REGIオーソリ処理結果', [
                    'payment_id' => $payment->id,
                    'result' => $apiResult['result'] ?? 'UNKNOWN',
                    'auth_code' => $apiResult['auth_code'] ?? null,
                    'seqno' => $apiResult['seqno'] ?? null,
                    'customer_id' => ($billingType === 'monthly') ? $customerId : null,
                    'error_message' => $apiResult['error_message'] ?? null,
                ]);

                // payment_events: fregi_authorize_response を記録
                DB::table('payment_events')->insert([
                    'payment_id' => $payment->id,
                    'event_type' => 'fregi_authorize_response',
                    'payload' => json_encode([
                        'result' => $apiResult['result'] ?? 'UNKNOWN',
                        'auth_code' => $apiResult['auth_code'] ?? null,
                        'seqno' => $apiResult['seqno'] ?? null,
                        'customer_id' => ($billingType === 'monthly') ? $customerId : null,
                        'error_message' => $apiResult['error_message'] ?? null,
                    ]),
                    'created_at' => now(),
                ]);

                if ($apiResult['result'] !== 'OK') {
                    Log::channel('contract_payment')->error('F-REGIオーソリ処理失敗', [
                        'payment_id' => $payment->id,
                        'error_message' => $apiResult['error_message'] ?? '不明なエラー',
                        'api_result' => $apiResult,
                    ]);
                    
                    // payment_events: fregi_authorize_failed を記録
                    DB::table('payment_events')->insert([
                        'payment_id' => $payment->id,
                        'event_type' => 'fregi_authorize_failed',
                        'payload' => json_encode([
                            'error_message' => $apiResult['error_message'] ?? '不明なエラー',
                            'error_code' => $apiResult['error_code'] ?? null,
                        ]),
                        'created_at' => now(),
                    ]);
                    
                    // トランザクションをロールバック（例外をスロー）
                    // これにより、契約と決済情報が作成されない
                    throw new \Exception('決済処理に失敗しました: ' . ($apiResult['error_message'] ?? '不明なエラー'));
                }

                // 承認番号、取引番号を取得
                $authCode = $apiResult['auth_code'];
                $seqno = $apiResult['seqno'];

                // payment_events: fregi_authorize_success を記録
                DB::table('payment_events')->insert([
                    'payment_id' => $payment->id,
                    'event_type' => 'fregi_authorize_success',
                    'payload' => json_encode([
                        'auth_code' => $authCode,
                        'seqno' => $seqno,
                        'customer_id' => ($billingType === 'monthly') ? $customerId : null,
                    ]),
                    'created_at' => now(),
                ]);

                // Paymentに承認番号、取引番号を保存
                $payment->update([
                    'receiptno' => $authCode, // 承認番号
                    'slipno' => $seqno, // 取引番号
                    'status' => 'paid', // ENUM値: created, redirect_issued, waiting_notify, paid, failed, canceled, expired
                    'completed_at' => now(),
                ]);

                // 月額課金の場合はCUSTOMERIDを保存（送信したCUSTOMERIDを保存）
                if ($billingType === 'monthly') {
                    $contract->update([
                        'customer_id' => $customerId, // 送信したCUSTOMERIDを保存
                        'status' => 'active',
                    ]);
                } else {
                    // 一回限りの決済の場合も契約を有効化
                    $contract->update([
                        'status' => 'active',
                    ]);
                }

                $processingTime = round((microtime(true) - $startTime) * 1000, 2);

                Log::channel('contract_payment')->info('決済処理完了', [
                    'payment_id' => $payment->id,
                    'contract_id' => $contract->id,
                    'orderid' => $payment->orderid,
                    'auth_code' => $authCode,
                    'seqno' => $seqno,
                    'customer_id' => $contract->customer_id,
                    'processing_time_ms' => $processingTime,
                ]);

                // 完了画面にリダイレクト
                return redirect()->route('contract.complete', ['orderid' => $payment->orderid]);
            } catch (\Exception $e) {
                $processingTime = round((microtime(true) - $startTime) * 1000, 2);
                
                // 詳細なエラーログを記録
                Log::channel('contract_payment')->error('決済処理エラー（詳細）', [
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'error_class' => get_class($e),
                    'stack_trace' => $e->getTraceAsString(),
                    'previous_exception' => $e->getPrevious() ? [
                        'message' => $e->getPrevious()->getMessage(),
                        'file' => $e->getPrevious()->getFile(),
                        'line' => $e->getPrevious()->getLine(),
                    ] : null,
                    'request_method' => $request->method(),
                    'request_url' => $request->fullUrl(),
                    'request_path' => $request->path(),
                    'request_headers' => [
                        'user-agent' => $request->userAgent(),
                        'referer' => $request->header('referer'),
                    ],
                    'request_data_keys' => array_keys($request->except(['password', '_token'])),
                    'session_id' => $request->session()->getId(),
                    'session_keys' => array_keys($request->session()->all()),
                    'ip' => $request->ip(),
                    'fregi_env' => config('fregi.environment', 'unknown'),
                    'app_env' => config('app.env', 'unknown'),
                    'app_debug' => config('app.debug', false),
                    'php_version' => PHP_VERSION,
                    'processing_time_ms' => $processingTime,
                    'memory_usage' => memory_get_usage(true),
                    'memory_peak' => memory_get_peak_usage(true),
                    'timestamp' => now()->toIso8601String(),
                ]);
                
                // トランザクションは自動的にロールバックされる
                // 確認画面のデータをセッションに保存して確認画面にリダイレクト
                // （back()だとGETリクエストになり、confirmGet()でエラーになるため）
                try {
                    $validated = $request->validated();
                    $request->session()->put('contract_confirm_data', $validated);
                    $request->session()->put('contract_confirm_error', $e->getMessage());
                    
                    return redirect()->route('contract.confirm.get');
                } catch (\Illuminate\Validation\ValidationException $validationException) {
                    // バリデーションエラーの場合は、元のフォームに戻す
                    return back()->withErrors($validationException->errors())->withInput();
                }
            }
        });
    }

    /**
     * 申込完了画面（決済成功後）
     */
    public function complete(Request $request): View
    {
        $orderId = $request->input('orderid');
        
        if (!$orderId) {
            Log::channel('contract_payment')->error('完了画面: orderidパラメータがありません', [
                'request_params' => $request->all(),
            ]);
            abort(404, '決済情報が見つかりません。');
        }
        
        $payment = Payment::where('orderid', $orderId)->with('contract.contractPlan')->first();
        
        if (!$payment) {
            Log::channel('contract_payment')->error('完了画面: Paymentが見つかりません', [
                'orderid' => $orderId,
            ]);
            abort(404, '決済情報が見つかりません。');
        }
        
        $contract = $payment->contract;
        
        if (!$contract) {
            Log::channel('contract_payment')->error('完了画面: Contractが見つかりません', [
                'payment_id' => $payment->id,
                'orderid' => $orderId,
            ]);
            abort(404, '契約情報が見つかりません。');
        }

        return view('contracts.complete', [
            'contract' => $contract,
            'payment' => $payment,
        ]);
    }

}
