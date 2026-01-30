<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContractRequest;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\ContractPlan;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Mail\ContractNotificationMail;
use App\Mail\ContractReplyMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
            
            return view('contracts.confirm', [
                'data' => $validated,
                'plan' => $plan,
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
            
            return view('contracts.confirm', [
                'data' => $viewData,
                'plan' => $plan,
                'termsOfService' => $termsOfService,
                'optionProducts' => $optionProducts,
                'optionTotalAmount' => $optionTotalAmount,
                'isViewOnly' => true, // 閲覧専用フラグ（フォーム送信を無効化）
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
                
                return view('contracts.confirm', [
                    'data' => $viewData,
                    'plan' => $plan,
                    'termsOfService' => $termsOfService,
                    'optionProducts' => $optionProducts,
                    'optionTotalAmount' => $optionTotalAmount,
                    'error' => $errorMessage, // エラーメッセージを渡す
                    'validation_errors' => $validationErrors, // バリデーションエラーを渡す
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
     * 申込を保存（契約＋明細のみ、決済なし）
     */
    public function store(ContractRequest $request): RedirectResponse
    {
        $startTime = microtime(true);

        return DB::transaction(function () use ($request, $startTime) {
            try {
                $validated = $request->validated();

                Log::channel('contract_payment')->info('申込処理開始', [
                    'contract_plan_id' => $validated['contract_plan_id'],
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'timestamp' => now()->toIso8601String(),
                ]);

                $createData = $validated;
                unset(
                    $createData['option_product_ids'],
                    $createData['terms_agreed']
                );

                if (!isset($createData['desired_start_date']) || empty($createData['desired_start_date'])) {
                    $createData['desired_start_date'] = now()->format('Y-m-d');
                }

                $contract = Contract::create([
                    ...$createData,
                    'status' => 'applied',
                ]);

                Log::channel('contract_payment')->info('契約作成完了', [
                    'contract_id' => $contract->id,
                    'contract_plan_id' => $contract->contract_plan_id,
                    'status' => $contract->status,
                ]);

                $plan = $contract->contractPlan;

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

                $contractItems = $contract->contractItems()->with('product')->get();
                $optionItems = $contractItems->filter(fn ($item) => $item->product_id !== null);
                $optionTotalAmount = $optionItems->sum('subtotal');

                // 管理者への通知メール送信
                try {
                    $notificationEmails = SiteSetting::getNotificationEmailsArray();
                    if ($notificationEmails !== []) {
                        foreach ($notificationEmails as $email) {
                            Mail::to($email)->send(
                                new ContractNotificationMail($contract, $optionItems, $optionTotalAmount)
                            );
                        }
                        Log::channel('contract_payment')->info('通知メール送信完了', [
                            'contract_id' => $contract->id,
                            'to' => $notificationEmails,
                        ]);
                        Log::channel('mail')->info('申込受付通知メール送信完了', [
                            'contract_id' => $contract->id,
                            'to' => $notificationEmails,
                        ]);
                    } else {
                        Log::channel('contract_payment')->warning('通知メール送信先が設定されていません', [
                            'contract_id' => $contract->id,
                        ]);
                    }
                } catch (\Throwable $e) {
                    $previous = $e->getPrevious();
                    $notificationEmails = SiteSetting::getNotificationEmailsArray();
                    $mailErrorContext = [
                        'contract_id' => $contract->id,
                        'to' => $notificationEmails,
                        'exception_class' => get_class($e),
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'previous_message' => $previous ? $previous->getMessage() : null,
                        'previous_class' => $previous ? get_class($previous) : null,
                        'trace' => $e->getTraceAsString(),
                    ];
                    Log::channel('contract_payment')->error('通知メール送信エラー', [
                        'contract_id' => $contract->id,
                        'error_message' => $e->getMessage(),
                        'exception_class' => get_class($e),
                        'previous_message' => $previous ? $previous->getMessage() : null,
                        'error_trace' => $e->getTraceAsString(),
                    ]);
                    Log::channel('mail')->error('申込受付通知メール送信エラー', $mailErrorContext);
                }

                // 申込者への返信メール送信
                try {
                    $customerEmail = $contract->email;
                    if ($customerEmail) {
                        Mail::to($customerEmail)->send(
                            new ContractReplyMail($contract, $optionItems, $optionTotalAmount)
                        );
                        Log::channel('contract_payment')->info('申込者への返信メール送信完了', [
                            'contract_id' => $contract->id,
                            'to' => $customerEmail,
                        ]);
                        Log::channel('mail')->info('申込者への返信メール送信完了', [
                            'contract_id' => $contract->id,
                            'to' => $customerEmail,
                        ]);
                    }
                } catch (\Throwable $e) {
                    $previous = $e->getPrevious();
                    $replyMailErrorContext = [
                        'contract_id' => $contract->id,
                        'to' => $contract->email,
                        'exception_class' => get_class($e),
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'previous_message' => $previous ? $previous->getMessage() : null,
                        'previous_class' => $previous ? get_class($previous) : null,
                        'trace' => $e->getTraceAsString(),
                    ];
                    Log::channel('contract_payment')->error('申込者への返信メール送信エラー', [
                        'contract_id' => $contract->id,
                        'to' => $contract->email,
                        'error_message' => $e->getMessage(),
                        'exception_class' => get_class($e),
                        'previous_message' => $previous ? $previous->getMessage() : null,
                        'error_trace' => $e->getTraceAsString(),
                    ]);
                    Log::channel('mail')->error('申込者への返信メール送信エラー', $replyMailErrorContext);
                }

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
                        'billing_type' => $product->billing_type ?? 'one_time',
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
     * 申込完了画面（署名付きURLで契約IDを受け取る）
     */
    public function complete(Contract $contract): View
    {
        $contract->load('contractPlan');
        $contractItems = $contract->contractItems()->with('product')->get();
        $optionItems = $contractItems->filter(fn ($item) => $item->product_id !== null);
        $optionTotalAmount = $optionItems->sum('subtotal');

        return view('contracts.complete', [
            'contract' => $contract,
            'optionItems' => $optionItems,
            'optionTotalAmount' => $optionTotalAmount,
        ]);
    }

}
