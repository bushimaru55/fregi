<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContractRequest;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\ContractPlan;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Services\BillingRobo\BillingRoboBillingService;
use App\Services\BillingRobo\BillingRoboDemandService;
use App\Services\BillingRobo\BillingScheduleService;
use App\Services\RobotPayment\PurchasePatternService;
use App\Services\ContractMailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class ContractController extends Controller
{
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

            // 製品IDが指定されているのに、該当する製品が見つからない場合は404
            if ($hasPlanIdsParam && $plans->isEmpty()) {
                Log::channel('contract_payment')->warning('指定された製品が見つかりません', [
                    'url' => $request->fullUrl(),
                    'plan_ids' => $planIds,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'timestamp' => now()->toIso8601String(),
                ]);
                abort(404, '指定された製品が見つかりません。製品が存在しないか、現在公開されていません。');
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
                'app_env' => config('app.env', 'unknown'),
                'php_version' => PHP_VERSION,
                'timestamp' => now()->toIso8601String(),
            ]);
            throw $e;
        }
    }

    /**
     * 申込内容確認画面を表示
     * POSTリクエスト時は必ず確認画面を表示する（ROBOT PAYMENT 有効時は決済ページへリダイレクト）
     */
    public function confirm(ContractRequest $request): View|RedirectResponse
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
            $basePlanIds = $validated['base_plan_ids'] ?? [];
            $plans = ContractPlan::whereIn('id', $basePlanIds)->orderBy('display_order')->get();
            $termsOfService = SiteSetting::getValue('terms_of_service', '');
            $baseTotalAmount = $plans->sum('price');

            // ログ記録（個人情報はマスク）
            $logData = [
                'base_plan_ids' => $basePlanIds,
                'company_name' => substr($validated['company_name'], 0, 3) . '***',
                'email' => substr($validated['email'], 0, 3) . '***',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toIso8601String(),
            ];
            Log::channel('contract_payment')->info('確認画面表示（POST）', $logData);

            // ROBOT PAYMENT 有効時は決済ページへリダイレクト（契約・明細は決済実行時に作成）
            if (config('robotpayment.enabled', false)) {
                $request->session()->put('contract_confirm_data', $validated);
                Log::channel('contract_payment')->info('確認送信→決済ページへリダイレクト', [
                    'base_plan_ids' => $basePlanIds,
                ]);
                return redirect()->route('contract.payment');
            }

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

            return view('contracts.confirm', [
                'data' => $validated,
                'plans' => $plans,
                'baseTotalAmount' => $baseTotalAmount,
                'termsOfService' => $termsOfService,
                'optionProducts' => $optionProducts,
                'optionTotalAmount' => $optionTotalAmount,
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

            $basePlanIds = $viewData['base_plan_ids'] ?? (isset($viewData['contract_plan_id']) ? [$viewData['contract_plan_id']] : []);
            if (empty($basePlanIds)) {
                return redirect()->route('contract.create')
                    ->with('error', '選択された製品が見つかりません。');
            }
            $plans = ContractPlan::whereIn('id', $basePlanIds)->orderBy('display_order')->get();
            $baseTotalAmount = $plans->sum('price');
            $termsOfService = SiteSetting::getValue('terms_of_service', '');

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

            return view('contracts.confirm', [
                'data' => $viewData,
                'plans' => $plans,
                'baseTotalAmount' => $baseTotalAmount,
                'termsOfService' => $termsOfService,
                'optionProducts' => $optionProducts,
                'optionTotalAmount' => $optionTotalAmount,
                'isViewOnly' => true,
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
                $basePlanIds = $viewData['base_plan_ids'] ?? (isset($viewData['contract_plan_id']) ? [$viewData['contract_plan_id']] : []);
                if (empty($basePlanIds)) {
                    return redirect()->route('contract.create')
                        ->with('error', '選択された製品が見つかりませんでした。もう一度お試しください。');
                }
                $plans = ContractPlan::whereIn('id', $basePlanIds)->orderBy('display_order')->get();
                if ($plans->isEmpty()) {
                    return redirect()->route('contract.create')
                        ->with('error', '選択された製品が見つかりませんでした。もう一度お試しください。');
                }
                $baseTotalAmount = $plans->sum('price');
                $termsOfService = SiteSetting::getValue('terms_of_service', '');

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

                return view('contracts.confirm', [
                    'data' => $viewData,
                    'plans' => $plans,
                    'baseTotalAmount' => $baseTotalAmount,
                    'termsOfService' => $termsOfService,
                    'optionProducts' => $optionProducts,
                    'optionTotalAmount' => $optionTotalAmount,
                    'error' => $errorMessage,
                    'validation_errors' => $validationErrors,
                ]);
            } catch (\Exception $e) {
                Log::channel('contract_payment')->error('確認画面表示：製品が見つかりません（詳細）', [
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'error_class' => get_class($e),
                    'stack_trace' => $e->getTraceAsString(),
                    'base_plan_ids' => $viewData['base_plan_ids'] ?? null,
                    'contract_plan_id' => $viewData['contract_plan_id'] ?? null,
                    'view_data_keys' => array_keys($viewData ?? []),
                    'request_method' => $request->method(),
                    'request_url' => $request->fullUrl(),
                    'session_id' => $request->session()->getId(),
                    'app_env' => config('app.env', 'unknown'),
                    'timestamp' => now()->toIso8601String(),
                ]);
                return redirect()->route('contract.create')
                    ->with('error', '選択された製品が見つかりませんでした。もう一度お試しください。');
            }
        }
        
        // トークンもセッションデータもない場合は申込フォームへリダイレクト（エンドユーザーには技術的な文言を見せない）
        return redirect()->route('contract.create');
    }

    /**
     * 申込を保存（契約＋明細のみ、決済なし）
     */
    public function store(ContractRequest $request): RedirectResponse
    {
        $startTime = microtime(true);

        return DB::transaction(function () use ($request, $startTime) {
            try {
                $validated = $request->validated();
                $basePlanIds = $validated['base_plan_ids'] ?? [];
                $representativePlanId = !empty($basePlanIds) ? $basePlanIds[0] : null;

                Log::channel('contract_payment')->info('申込処理開始', [
                    'base_plan_ids' => $basePlanIds,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'timestamp' => now()->toIso8601String(),
                ]);

                $createData = $validated;
                unset(
                    $createData['base_plan_ids'],
                    $createData['option_product_ids'],
                    $createData['terms_agreed']
                );
                $createData['contract_plan_id'] = $representativePlanId;

                if (!isset($createData['desired_start_date']) || empty($createData['desired_start_date'])) {
                    $createData['desired_start_date'] = now()->format('Y-m-d');
                }

                $contract = Contract::create([
                    ...$createData,
                    'status' => 'applied',
                    'billing_robo_mode' => Contract::BILLING_ROBO_MODE_API3_STANDARD,
                ]);

                Log::channel('contract_payment')->info('契約作成完了', [
                    'contract_id' => $contract->id,
                    'contract_plan_id' => $contract->contract_plan_id,
                    'status' => $contract->status,
                ]);

                foreach ($basePlanIds as $planId) {
                    $plan = ContractPlan::findOrFail($planId);
                    ContractItem::create([
                        'contract_id' => $contract->id,
                        'contract_plan_id' => $plan->id,
                        'product_id' => null,
                        'product_name' => $plan->name,
                        'product_code' => $plan->item,
                        'quantity' => 1,
                        'unit_price' => $plan->price,
                        'subtotal' => $plan->price,
                        'billing_type' => $plan->billing_type ?? 'one_time',
                    ]);
                }

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
                        'billing_type' => $product->billing_type ?? 'one_time',
                    ]);
                }

                Log::channel('contract_payment')->info('契約明細作成完了', [
                    'contract_id' => $contract->id,
                    'base_plan_count' => count($basePlanIds),
                    'option_count' => count($optionProductIds),
                ]);

                if (config('billing_robo.base_url') && config('billing_robo.user_id')) {
                    try {
                        $billingService = app(BillingRoboBillingService::class);
                        $schedule = $contract->billing_robo_mode === Contract::BILLING_ROBO_MODE_API3_STANDARD
                            ? app(BillingScheduleService::class)->getScheduleForApplication($contract)
                            : null;
                        $api1Result = $billingService->upsertBillingFromContract($contract, $schedule);
                        if ($api1Result['success']) {
                            Log::channel('contract_payment')->info('請求管理ロボ API 1 請求先登録完了（申込保存時）', [
                                'contract_id' => $contract->id,
                                'billing_code' => $api1Result['billing_code'],
                            ]);
                            // API3 標準運用: 請求情報を Billing-Robo に登録する（申込保存導線で API3 まで実行）
                            if ($contract->billing_robo_mode === Contract::BILLING_ROBO_MODE_API3_STANDARD) {
                                $contract->refresh();
                                try {
                                    $demandService = app(BillingRoboDemandService::class);
                                    $api3Result = $demandService->upsertDemandFromContract($contract, $schedule);
                                    if ($api3Result['success']) {
                                        Log::channel('contract_payment')->info('請求管理ロボ API 3 請求情報登録完了（申込保存時）', [
                                            'contract_id' => $contract->id,
                                        ]);
                                    } else {
                                        Log::channel('contract_payment')->warning('請求管理ロボ API 3 失敗（申込保存時）', [
                                            'contract_id' => $contract->id,
                                            'error' => $api3Result['error'] ?? '',
                                        ]);
                                    }
                                } catch (\Throwable $e) {
                                    Log::channel('contract_payment')->warning('請求管理ロボ API 3 例外（申込保存時）', [
                                        'contract_id' => $contract->id,
                                        'message' => $e->getMessage(),
                                    ]);
                                }
                            }
                        } else {
                            Log::channel('contract_payment')->warning('請求管理ロボ API 1 失敗（申込保存時）', [
                                'contract_id' => $contract->id,
                                'error' => $api1Result['error'] ?? '',
                            ]);
                        }
                    } catch (\Throwable $e) {
                        Log::channel('contract_payment')->warning('請求管理ロボ API 1 例外（申込保存時）', [
                            'contract_id' => $contract->id,
                            'message' => $e->getMessage(),
                        ]);
                    }
                }

                app(ContractMailService::class)->sendOnce($contract);

                $processingTime = round((microtime(true) - $startTime) * 1000, 2);
                Log::channel('contract_payment')->info('申込処理完了', [
                    'contract_id' => $contract->id,
                    'processing_time_ms' => $processingTime,
                ]);

                return redirect()->away(
                    URL::temporarySignedRoute('contract.complete', now()->addMinutes(60), ['contract' => $contract->id])
                );
            } catch (\Exception $e) {
                $processingTime = round((microtime(true) - $startTime) * 1000, 2);
                Log::channel('contract_payment')->error('申込処理エラー（詳細）', [
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'error_class' => get_class($e),
                    'stack_trace' => $e->getTraceAsString(),
                    'request_path' => $request->path(),
                    'processing_time_ms' => $processingTime,
                    'timestamp' => now()->toIso8601String(),
                ]);
                try {
                    $validated = $request->validated();
                    $request->session()->put('contract_confirm_data', $validated);
                    $request->session()->put('contract_confirm_error', $e->getMessage());
                    return redirect()->route('contract.confirm.get');
                } catch (\Illuminate\Validation\ValidationException $validationException) {
                    return back()->withErrors($validationException->errors())->withInput();
                }
            }
        });
    }

    /**
     * 決済ページ（ROBOT PAYMENT トークン+3DS）。セッションに contract_confirm_data がある場合のみ表示。
     */
    public function payment(Request $request)
    {
        $data = $request->session()->get('contract_confirm_data');
        $storeId = config('robotpayment.store_id', '');
        $enabled = config('robotpayment.enabled', false);
        if (!$data) {
            return redirect()->route('contract.create')->with('error', '申込内容が見つかりません。最初から入力し直してください。');
        }
        $basePlanIds = $data['base_plan_ids'] ?? (isset($data['contract_plan_id']) ? [$data['contract_plan_id']] : []);
        if (empty($basePlanIds)) {
            return redirect()->route('contract.create')->with('error', '申込内容にベース製品が含まれていません。最初から入力し直してください。');
        }
        $optionProductIds = $data['option_product_ids'] ?? [];
        $desiredStartDate = $data['desired_start_date'] ?? now()->format('Y-m-d');

        $patternService = app(PurchasePatternService::class);
        $amounts = $patternService->getAmountsFromPlansAndOptions($basePlanIds, $optionProductIds, $desiredStartDate);

        if (!$storeId || !$enabled) {
            return redirect()->route('contract.create')->with('error', '決済は現在ご利用いただけません。');
        }

        $paymentError = $request->session()->get('payment_error');
        $request->session()->forget('payment_error');

        $logData = [
            'base_plan_ids' => $basePlanIds,
            'pattern' => $amounts['pattern'],
            'am' => $amounts['am'],
            'tx' => $amounts['tx'],
            'sf' => $amounts['sf'],
            'ta' => $amounts['ta'] ?? null,
        ];
        if ($amounts['pattern'] !== \App\Services\RobotPayment\PurchasePatternService::PATTERN_ONE_TIME_ONLY) {
            $logData['amount_recurring'] = $amounts['amount_recurring'] ?? null;
            $logData['ac4'] = $amounts['ac4'] ?? null;
        }
        Log::channel('contract_payment')->info('決済ページ表示', $logData);

        $customerPhone = $data['phone'] ?? '';
        $customerPhoneDigits = preg_replace('/\D/', '', $customerPhone);

        return view('contracts.payment', [
            'amounts' => $amounts,
            'store_id' => $storeId,
            'customer_email' => $data['email'] ?? '',
            'customer_phone' => $customerPhoneDigits,
            'error' => $paymentError,
            'use_zero_amount_for_3ds' => true,
        ]);
    }

    /**
     * オプション製品取得API（選択されたベース製品（複数可）に紐づくオプション製品の和集合を取得）
     */
    public function getOptionProducts(Request $request)
    {
        try {
            $planIds = $request->input('plan_ids', []);
            if (!is_array($planIds)) {
                $planIds = array_filter([$planIds]);
            }
            $planIds = array_values(array_unique(array_map('intval', $planIds)));

            if (empty($planIds)) {
                return response()->json([
                    'success' => true,
                    'option_products' => [],
                ]);
            }

            $productsById = [];
            foreach ($planIds as $planId) {
                $plan = ContractPlan::find($planId);
                if (!$plan) {
                    continue;
                }
                foreach ($plan->optionProducts()->orderBy('products.display_order')->get() as $product) {
                    $productsById[$product->id] = $product;
                }
            }
            $optionProducts = collect($productsById)->values()->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'unit_price' => $product->unit_price,
                    'formatted_price' => $product->formatted_price,
                    'billing_type' => $product->billing_type ?? 'one_time',
                    'description' => $product->description,
                ];
            })->values()->all();

            Log::channel('contract_payment')->info('オプション製品取得', [
                'plan_ids' => $planIds,
                'option_count' => count($optionProducts),
            ]);

            return response()->json([
                'success' => true,
                'option_products' => $optionProducts,
            ]);
        } catch (\Exception $e) {
            Log::channel('contract_payment')->error('オプション製品取得エラー', [
                'plan_ids' => $request->input('plan_ids', []),
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
     * 申込完了画面（署名付きURLで契約IDを受け取る）
     */
    public function complete(Contract $contract): View
    {
        $contract->load(['contractItems.contractPlan', 'contractItems.product']);
        $contractItems = $contract->contractItems;
        $baseItems = $contractItems->filter(fn ($item) => $item->contract_plan_id !== null);
        $optionItems = $contractItems->filter(fn ($item) => $item->product_id !== null);
        $optionTotalAmount = $optionItems->sum('subtotal');
        $totalAmount = $contractItems->sum('subtotal');

        return view('contracts.complete', [
            'contract' => $contract,
            'contractItems' => $contractItems,
            'baseItems' => $baseItems,
            'optionItems' => $optionItems,
            'optionTotalAmount' => $optionTotalAmount,
            'totalAmount' => $totalAmount,
        ]);
    }

}
