<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContractRequest;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\ContractPlan;
use App\Models\Payment;
use App\Models\Product;
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
            
            // オプション商品は後でJavaScriptで動的に取得（選択されたベース商品に応じて）
            // 初期表示時は空のコレクションを渡す
            $optionProducts = collect();
            
            // ログ記録
            Log::channel('contract_payment')->info('申込フォーム表示', [
                'url' => $request->fullUrl(),
                'plan_ids' => $planIds,
                'plan_count' => $plans->count(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toIso8601String(),
            ]);
            
            return view('contracts.create', compact('plans', 'termsOfService', 'optionProducts'));
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
            
            // オプション商品を取得
            $optionProductIds = $validated['option_product_ids'] ?? [];
            $optionProducts = collect();
            $optionTotalAmount = 0;
            if (!empty($optionProductIds)) {
                $optionProducts = Product::whereIn('id', $optionProductIds)
                    ->where('type', 'option')
                    ->where('is_active', true)
                    ->get();
                $optionTotalAmount = $optionProducts->sum('unit_price');
            }
            
            // DBの設定レコードからenvironmentを取得（設定画面で変更可能）
            $fregiConfig = $this->configService->getSingleConfig();
            $fregiEnvironment = $fregiConfig ? $fregiConfig->environment : config('fregi.environment', 'test');
            
            return view('contracts.confirm', [
                'data' => $validated,
                'plan' => $plan,
                'termsOfService' => $termsOfService,
                'optionProducts' => $optionProducts,
                'optionTotalAmount' => $optionTotalAmount,
                'environment' => $fregiEnvironment, // DBの設定レコードから取得
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
            
            // オプション商品を取得
            $optionProductIds = $viewData['option_product_ids'] ?? [];
            $optionProducts = collect();
            $optionTotalAmount = 0;
            if (!empty($optionProductIds)) {
                $optionProducts = Product::whereIn('id', $optionProductIds)
                    ->where('type', 'option')
                    ->where('is_active', true)
                    ->get();
                $optionTotalAmount = $optionProducts->sum('unit_price');
            }
            
            // DBの設定レコードからenvironmentを取得（設定画面で変更可能）
            $fregiConfig = $this->configService->getSingleConfig();
            $fregiEnvironment = $fregiConfig ? $fregiConfig->environment : config('fregi.environment', 'test');
            
            return view('contracts.confirm', [
                'data' => $viewData,
                'plan' => $plan,
                'termsOfService' => $termsOfService,
                'optionProducts' => $optionProducts,
                'optionTotalAmount' => $optionTotalAmount,
                'isViewOnly' => true, // 閲覧専用フラグ（フォーム送信を無効化）
                'environment' => $fregiEnvironment, // DBの設定レコードから取得
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
                
                // オプション商品を取得
                $optionProductIds = $viewData['option_product_ids'] ?? [];
                $optionProducts = collect();
                $optionTotalAmount = 0;
                if (!empty($optionProductIds)) {
                    $optionProducts = Product::whereIn('id', $optionProductIds)
                        ->where('type', 'option')
                        ->where('is_active', true)
                        ->get();
                    $optionTotalAmount = $optionProducts->sum('unit_price');
                }
                
                // DBの設定レコードからenvironmentを取得（設定画面で変更可能）
                $fregiConfig = $this->configService->getSingleConfig();
                $fregiEnvironment = $fregiConfig ? $fregiConfig->environment : config('fregi.environment', 'test');
                
                return view('contracts.confirm', [
                    'data' => $viewData,
                    'plan' => $plan,
                    'termsOfService' => $termsOfService,
                    'optionProducts' => $optionProducts,
                    'optionTotalAmount' => $optionTotalAmount,
                    'error' => $errorMessage, // エラーメッセージを渡す
                    'validation_errors' => $validationErrors, // バリデーションエラーを渡す
                    'environment' => $fregiEnvironment, // DBの設定レコードから取得
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
        
        // トークンもセッションデータもない場合は申込フォームへリダイレクト（エンドユーザーには技術的な文言を見せない）
        return redirect()->route('contract.create');
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
                // カード情報（pan1-4, scode, card_expiry_*, card_name）は保存しない。F-REGI 送信時のみ使用。
                $createData = $validated;
                unset(
                    $createData['pan1'],
                    $createData['pan2'],
                    $createData['pan3'],
                    $createData['pan4'],
                    $createData['scode'],
                    $createData['card_expiry_month'],
                    $createData['card_expiry_year'],
                    $createData['card_name'],
                    $createData['option_product_ids'],
                    $createData['terms_agreed']
                );
                $contract = Contract::create([...$createData, 'status' => 'draft']);

                Log::channel('contract_payment')->info('契約作成完了', [
                    'contract_id' => $contract->id,
                    'contract_plan_id' => $contract->contract_plan_id,
                    'status' => $contract->status,
                ]);

                // プラン情報を取得
                $plan = $contract->contractPlan;
                $billingType = $plan->billing_type ?? 'one_time';

                // 契約明細（contract_items）を作成
                // 1. ベース商品の明細（必須・自動生成）
                ContractItem::create([
                    'contract_id' => $contract->id,
                    'contract_plan_id' => $plan->id,
                    'product_id' => null,
                    'product_name' => $plan->name,
                    'product_code' => $plan->item,
                    'quantity' => 1,
                    'unit_price' => $plan->price,
                    'subtotal' => $plan->price,
                ]);

                // 2. オプション商品の明細（任意・ユーザー選択）
                $optionProductIds = $validated['option_product_ids'] ?? [];
                foreach ($optionProductIds as $productId) {
                    $product = Product::where('id', $productId)
                        ->where('type', 'option')
                        ->where('is_active', true)
                        ->firstOrFail();
                    
                    ContractItem::create([
                        'contract_id' => $contract->id,
                        'contract_plan_id' => null,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_code' => $product->code,
                        'quantity' => 1,
                        'unit_price' => $product->unit_price,
                        'subtotal' => $product->unit_price,
                    ]);
                }

                Log::channel('contract_payment')->info('契約明細作成完了', [
                    'contract_id' => $contract->id,
                    'option_count' => count($optionProductIds),
                ]);

                // F-REGI設定を取得
                // まず、DBから単一設定を取得（設定画面で変更可能）
                $fregiConfig = $this->configService->getSingleConfig();
                
                if (!$fregiConfig) {
                    // 設定が存在しない場合は、.envのFREGI_ENVをフォールバックとして使用
                    $targetEnv = config('fregi.environment', 'test');
                    $fregiConfig = $this->configService->getActiveConfig(
                        1, // company_id（マルチテナント対応時に変更）
                        $targetEnv
                    );
                }
                
                // DBの設定レコードのenvironmentを使用（設定画面で変更可能）
                $targetEnv = $fregiConfig->environment;

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

                // 月額商品と買い切り商品を分離
                // 月額商品：ベース商品が月額課金の場合
                $monthlyItems = collect();
                $monthlyAmount = 0;
                if ($billingType === 'monthly') {
                    $monthlyItems = $contract->contractItems()
                        ->whereNotNull('contract_plan_id')
                        ->get();
                    $monthlyAmount = $monthlyItems->sum('subtotal');
                }

                // 買い切り商品：ベース商品が買い切り + オプション商品（全て買い切り）
                $oneTimeItems = collect();
                $oneTimeAmount = 0;
                if ($billingType === 'one_time') {
                    // ベース商品が買い切りの場合、ベース商品 + オプション商品
                    $oneTimeItems = $contract->contractItems()->get();
                    $oneTimeAmount = $oneTimeItems->sum('subtotal');
                } else {
                    // ベース商品が月額の場合、オプション商品のみ
                    $oneTimeItems = $contract->contractItems()
                        ->whereNotNull('product_id')
                        ->get();
                    $oneTimeAmount = $oneTimeItems->sum('subtotal');
                }

                $monthlyPayment = null;
                $oneTimePayment = null;
                $customerId = null;

                // 1. 月額商品のオーソリ処理（MONTHLY=1, CUSTOMERID付き）
                if ($monthlyItems->isNotEmpty() && $monthlyAmount > 0) {
                    // 伝票番号を生成
                    $timestamp = now()->format('YmdHis');
                    $contractId = str_pad((string)$contract->id, 4, '0', STR_PAD_LEFT);
                    $orderId = 'ORD' . $timestamp . $contractId . '-M';
                    if (strlen($orderId) > 20) {
                        $orderId = substr($orderId, -20);
                    }

                    $monthlyPayment = Payment::create([
                        'company_id' => 1,
                        'contract_id' => $contract->id,
                        'orderid' => $orderId,
                        'amount' => $monthlyAmount,
                        'currency' => 'JPY',
                        'payment_method' => 'credit_card',
                        'status' => 'created',
                    ]);

                    $customerId = $contract->generateCustomerId();
                    
                    $apiParams = [
                        'SHOPID' => $fregiConfig->shopid,
                        'ID' => $monthlyPayment->orderid,
                        'PAY' => (string)$monthlyAmount,
                        'MONTHLY' => '1',
                        'MONTHLYMODE' => '0',
                        'CUSTOMERID' => $customerId,
                        'PAN1' => $pan1,
                        'PAN2' => $pan2,
                        'PAN3' => $pan3,
                        'PAN4' => $pan4,
                        'CARDEXPIRY1' => $cardExpiryMonth,
                        'CARDEXPIRY2' => $cardExpiryYear,
                        'CARDNAME' => $cardName,
                        'IP' => $request->ip(),
                    ];
                    
                    if ($scode) {
                        $apiParams['SCODE'] = $scode;
                    }

                    // ログ記録
                    $logParams = $apiParams;
                    $logParams['PAN1'] = '****';
                    $logParams['PAN2'] = '****';
                    $logParams['PAN3'] = '****';
                    $logParams['PAN4'] = '****';
                    if (isset($logParams['SCODE'])) {
                        $logParams['SCODE'] = '****';
                    }

                    Log::channel('contract_payment')->info('F-REGIオーソリ処理開始（月額商品）', [
                        'payment_id' => $monthlyPayment->id,
                        'orderid' => $monthlyPayment->orderid,
                        'amount' => $monthlyAmount,
                        'environment' => $targetEnv,
                        'api_params' => $logParams,
                    ]);

                    // payment_events記録
                    // DBの設定レコードのenvironmentに基づいてURLを生成
                    $authUrl = $targetEnv === 'test' 
                        ? 'https://ssl.f-regi.com/connecttest/authm.cgi'
                        : 'https://ssl.f-regi.com/connect/authm.cgi';
                    
                    DB::table('payment_events')->insert([
                        'payment_id' => $monthlyPayment->id,
                        'event_type' => 'fregi_authorize_request_monthly',
                        'payload' => json_encode([
                            'url' => $authUrl,
                            'fregi_env' => $targetEnv,
                            'shopid' => $fregiConfig->shopid,
                            'orderid' => $monthlyPayment->orderid,
                            'amount' => $monthlyAmount,
                            'billing_type' => 'monthly',
                            'has_card_info' => true,
                            'has_customer_id' => true,
                        ]),
                        'created_at' => now(),
                    ]);

                    // オーソリ処理実行
                    $apiResult = $this->apiService->authorizePayment($apiParams, $fregiConfig);

                    DB::table('payment_events')->insert([
                        'payment_id' => $monthlyPayment->id,
                        'event_type' => 'fregi_authorize_response_monthly',
                        'payload' => json_encode([
                            'result' => $apiResult['result'] ?? 'UNKNOWN',
                            'auth_code' => $apiResult['auth_code'] ?? null,
                            'seqno' => $apiResult['seqno'] ?? null,
                            'customer_id' => $customerId,
                            'error_message' => $apiResult['error_message'] ?? null,
                        ]),
                        'created_at' => now(),
                    ]);

                    if ($apiResult['result'] !== 'OK') {
                        Log::channel('contract_payment')->error('F-REGIオーソリ処理失敗（月額商品）', [
                            'payment_id' => $monthlyPayment->id,
                            'error_message' => $apiResult['error_message'] ?? '不明なエラー',
                        ]);
                        
                        DB::table('payment_events')->insert([
                            'payment_id' => $monthlyPayment->id,
                            'event_type' => 'fregi_authorize_failed_monthly',
                            'payload' => json_encode([
                                'error_message' => $apiResult['error_message'] ?? '不明なエラー',
                            ]),
                            'created_at' => now(),
                        ]);
                        
                        throw new \Exception('月額商品の決済処理に失敗しました: ' . ($apiResult['error_message'] ?? '不明なエラー'));
                    }

                    // 成功時の処理
                    $monthlyPayment->update([
                        'receiptno' => $apiResult['auth_code'],
                        'slipno' => $apiResult['seqno'],
                        'status' => 'paid',
                        'completed_at' => now(),
                    ]);

                    DB::table('payment_events')->insert([
                        'payment_id' => $monthlyPayment->id,
                        'event_type' => 'fregi_authorize_success_monthly',
                        'payload' => json_encode([
                            'auth_code' => $apiResult['auth_code'],
                            'seqno' => $apiResult['seqno'],
                            'customer_id' => $customerId,
                        ]),
                        'created_at' => now(),
                    ]);

                    // CUSTOMERIDを保存
                    $contract->update([
                        'customer_id' => $customerId,
                        'payment_id' => $monthlyPayment->id, // 主決済として設定（後方互換性）
                    ]);

                    Log::channel('contract_payment')->info('F-REGIオーソリ処理成功（月額商品）', [
                        'payment_id' => $monthlyPayment->id,
                        'customer_id' => $customerId,
                    ]);
                }

                // 2. 買い切り商品のオーソリ処理（MONTHLY=0）
                if ($oneTimeItems->isNotEmpty() && $oneTimeAmount > 0) {
                    // 伝票番号を生成
                    $timestamp = now()->format('YmdHis');
                    $contractId = str_pad((string)$contract->id, 4, '0', STR_PAD_LEFT);
                    $orderId = 'ORD' . $timestamp . $contractId . '-OT';
                    if (strlen($orderId) > 20) {
                        $orderId = substr($orderId, -20);
                    }

                    $oneTimePayment = Payment::create([
                        'company_id' => 1,
                        'contract_id' => $contract->id,
                        'orderid' => $orderId,
                        'amount' => $oneTimeAmount,
                        'currency' => 'JPY',
                        'payment_method' => 'credit_card',
                        'status' => 'created',
                    ]);

                    $apiParams = [
                        'SHOPID' => $fregiConfig->shopid,
                        'ID' => $oneTimePayment->orderid,
                        'PAY' => (string)$oneTimeAmount,
                        'MONTHLY' => '0',
                        'PAN1' => $pan1,
                        'PAN2' => $pan2,
                        'PAN3' => $pan3,
                        'PAN4' => $pan4,
                        'CARDEXPIRY1' => $cardExpiryMonth,
                        'CARDEXPIRY2' => $cardExpiryYear,
                        'CARDNAME' => $cardName,
                        'IP' => $request->ip(),
                    ];
                    
                    if ($scode) {
                        $apiParams['SCODE'] = $scode;
                    }

                    // ログ記録
                    $logParams = $apiParams;
                    $logParams['PAN1'] = '****';
                    $logParams['PAN2'] = '****';
                    $logParams['PAN3'] = '****';
                    $logParams['PAN4'] = '****';
                    if (isset($logParams['SCODE'])) {
                        $logParams['SCODE'] = '****';
                    }

                    Log::channel('contract_payment')->info('F-REGIオーソリ処理開始（買い切り商品）', [
                        'payment_id' => $oneTimePayment->id,
                        'orderid' => $oneTimePayment->orderid,
                        'amount' => $oneTimeAmount,
                        'environment' => $targetEnv,
                        'api_params' => $logParams,
                    ]);

                    // payment_events記録
                    // DBの設定レコードのenvironmentに基づいてURLを生成
                    $authUrl = $targetEnv === 'test' 
                        ? 'https://ssl.f-regi.com/connecttest/authm.cgi'
                        : 'https://ssl.f-regi.com/connect/authm.cgi';
                    
                    DB::table('payment_events')->insert([
                        'payment_id' => $oneTimePayment->id,
                        'event_type' => 'fregi_authorize_request_one_time',
                        'payload' => json_encode([
                            'url' => $authUrl,
                            'fregi_env' => $targetEnv,
                            'shopid' => $fregiConfig->shopid,
                            'orderid' => $oneTimePayment->orderid,
                            'amount' => $oneTimeAmount,
                            'billing_type' => 'one_time',
                            'has_card_info' => true,
                        ]),
                        'created_at' => now(),
                    ]);

                    // オーソリ処理実行
                    $apiResult = $this->apiService->authorizePayment($apiParams, $fregiConfig);

                    DB::table('payment_events')->insert([
                        'payment_id' => $oneTimePayment->id,
                        'event_type' => 'fregi_authorize_response_one_time',
                        'payload' => json_encode([
                            'result' => $apiResult['result'] ?? 'UNKNOWN',
                            'auth_code' => $apiResult['auth_code'] ?? null,
                            'seqno' => $apiResult['seqno'] ?? null,
                            'error_message' => $apiResult['error_message'] ?? null,
                        ]),
                        'created_at' => now(),
                    ]);

                    if ($apiResult['result'] !== 'OK') {
                        Log::channel('contract_payment')->error('F-REGIオーソリ処理失敗（買い切り商品）', [
                            'payment_id' => $oneTimePayment->id,
                            'error_message' => $apiResult['error_message'] ?? '不明なエラー',
                        ]);
                        
                        // 月額商品のオーソリが成功している場合は警告ログ
                        if ($monthlyPayment && $monthlyPayment->status === 'paid') {
                            Log::channel('contract_payment')->warning('買い切り商品の決済失敗（月額商品は成功）', [
                                'monthly_payment_id' => $monthlyPayment->id,
                                'one_time_payment_id' => $oneTimePayment->id,
                                'customer_id' => $customerId,
                                'note' => '月額商品のオーソリは成功しています。F-REGI側でのキャンセル処理が必要な場合があります。',
                            ]);
                        }
                        
                        DB::table('payment_events')->insert([
                            'payment_id' => $oneTimePayment->id,
                            'event_type' => 'fregi_authorize_failed_one_time',
                            'payload' => json_encode([
                                'error_message' => $apiResult['error_message'] ?? '不明なエラー',
                            ]),
                            'created_at' => now(),
                        ]);
                        
                        throw new \Exception('買い切り商品の決済処理に失敗しました: ' . ($apiResult['error_message'] ?? '不明なエラー'));
                    }

                    // 成功時の処理
                    $oneTimePayment->update([
                        'receiptno' => $apiResult['auth_code'],
                        'slipno' => $apiResult['seqno'],
                        'status' => 'paid',
                        'completed_at' => now(),
                    ]);

                    DB::table('payment_events')->insert([
                        'payment_id' => $oneTimePayment->id,
                        'event_type' => 'fregi_authorize_success_one_time',
                        'payload' => json_encode([
                            'auth_code' => $apiResult['auth_code'],
                            'seqno' => $apiResult['seqno'],
                        ]),
                        'created_at' => now(),
                    ]);

                    Log::channel('contract_payment')->info('F-REGIオーソリ処理成功（買い切り商品）', [
                        'payment_id' => $oneTimePayment->id,
                    ]);
                }

                // 両方の決済が成功した場合、契約を有効化
                $contract->update([
                    'status' => 'active',
                ]);

                // 主決済IDを設定（月額があれば月額、なければ買い切り）
                if ($monthlyPayment) {
                    $contract->update(['payment_id' => $monthlyPayment->id]);
                } elseif ($oneTimePayment) {
                    $contract->update(['payment_id' => $oneTimePayment->id]);
                }

                $processingTime = round((microtime(true) - $startTime) * 1000, 2);

                Log::channel('contract_payment')->info('決済処理完了', [
                    'contract_id' => $contract->id,
                    'monthly_payment_id' => $monthlyPayment?->id,
                    'one_time_payment_id' => $oneTimePayment?->id,
                    'customer_id' => $customerId,
                    'processing_time_ms' => $processingTime,
                ]);

                // 完了画面にリダイレクト（主決済のorderidを使用）
                $primaryOrderId = $monthlyPayment?->orderid ?? $oneTimePayment?->orderid;
                if (!$primaryOrderId) {
                    throw new \Exception('決済情報が見つかりません');
                }
                return redirect()->route('contract.complete', ['orderid' => $primaryOrderId]);
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
     * オプション製品取得API（選択されたベース製品に紐づくオプション製品を取得）
     */
    public function getOptionProducts($contractPlanId)
    {
        try {
            $plan = ContractPlan::findOrFail($contractPlanId);
            
            // このベース製品に紐づくオプション製品を取得
            $optionProducts = $plan->optionProducts()
                ->orderBy('products.display_order')
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'code' => $product->code,
                        'unit_price' => $product->unit_price,
                        'formatted_price' => $product->formatted_price,
                        'description' => $product->description,
                    ];
                });
            
            Log::channel('contract_payment')->info('オプション製品取得', [
                'contract_plan_id' => $contractPlanId,
                'plan_name' => $plan->name,
                'option_count' => $optionProducts->count(),
            ]);
            
            return response()->json([
                'success' => true,
                'option_products' => $optionProducts,
            ]);
        } catch (\Exception $e) {
            Log::channel('contract_payment')->error('オプション製品取得エラー', [
                'contract_plan_id' => $contractPlanId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'オプション製品の取得に失敗しました。',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
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
