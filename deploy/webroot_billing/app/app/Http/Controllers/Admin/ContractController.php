<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\ContractPlan;
use App\Models\ContractStatus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractController extends Controller
{
    /**
     * 検索条件を適用したクエリを返す（一覧・CSV共通）
     */
    private function queryWithFilters(Request $request): Builder
    {
        $q = Contract::query()->with(['contractPlan', 'contractStatus']);

        // キーワード（会社名・担当者・メールの部分一致）
        $keyword = $request->filled('keyword') ? trim($request->input('keyword')) : null;
        if ($keyword !== null && $keyword !== '') {
            $q->where(function (Builder $q) use ($keyword) {
                $q->where('company_name', 'like', '%' . $keyword . '%')
                    ->orWhere('contact_name', 'like', '%' . $keyword . '%')
                    ->orWhere('email', 'like', '%' . $keyword . '%');
            });
        }

        // ステータス
        if ($request->filled('status')) {
            $q->where('status', $request->input('status'));
        }

        // プラン
        if ($request->filled('contract_plan_id')) {
            $q->where('contract_plan_id', $request->input('contract_plan_id'));
        }

        // 申込日（from）
        if ($request->filled('created_from')) {
            $q->whereDate('created_at', '>=', $request->input('created_from'));
        }

        // 申込日（to）
        if ($request->filled('created_to')) {
            $q->whereDate('created_at', '<=', $request->input('created_to'));
        }

        $q->orderBy('created_at', 'desc');
        return $q;
    }

    /**
     * 管理画面: 申し込み一覧（条件検索対応）
     */
    public function index(Request $request): View
    {
        $contracts = $this->queryWithFilters($request)->paginate(20)->withQueryString();
        $statuses = ContractStatus::where('is_active', true)->orderBy('display_order')->orderBy('id')->get();
        $plans = ContractPlan::where('is_active', true)->orderBy('display_order')->orderBy('id')->get();

        return view('admin.contracts.index', compact('contracts', 'statuses', 'plans'));
    }

    /**
     * 管理画面: 申し込み一覧 CSV出力（現在の検索条件に一致する件のみ出力）
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $filename = '申し込み一覧_' . now()->format('YmdHis') . '.csv';

        return new StreamedResponse(function () use ($request) {
            $stream = fopen('php://output', 'w');
            // BOM for Excel UTF-8
            fprintf($stream, chr(0xEF) . chr(0xBB) . chr(0xBF));

            $headers = ['ID', '会社名', '担当者', 'プラン', '金額', 'ステータス', '申込日'];
            fputcsv($stream, $headers);

            $this->queryWithFilters($request)
                ->orderBy('created_at', 'desc')
                ->chunk(500, function ($contracts) use ($stream) {
                    foreach ($contracts as $contract) {
                        fputcsv($stream, [
                            $contract->id,
                            $contract->company_name,
                            $contract->contact_name,
                            $contract->contractPlan->name ?? '',
                            $contract->contractPlan ? number_format($contract->contractPlan->price) . '円' : '',
                            $contract->status_label,
                            $contract->created_at->format('Y/m/d'),
                        ]);
                    }
                });

            fclose($stream);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * 管理画面: 契約詳細
     */
    public function show(Contract $contract): View
    {
        $contract->load(['contractPlan', 'contractItems.product']);
        return view('admin.contracts.show', compact('contract'));
    }

    /**
     * 管理画面: 契約編集フォーム
     */
    public function edit(Contract $contract): View
    {
        $contract->load(['contractPlan', 'contractItems.product']);
        $statuses = ContractStatus::where('is_active', true)->orderBy('display_order')->orderBy('id')->get();
        // この契約のプランに紐づくオプション商品一覧（編集用チェックボックス用）
        $optionProducts = $contract->contractPlan
            ? $contract->contractPlan->optionProducts()->get()
            : collect();
        // 現在この契約に含まれるオプション商品ID
        $selectedOptionProductIds = $contract->contractItems
            ->whereNotNull('product_id')
            ->pluck('product_id')
            ->unique()
            ->values()
            ->toArray();
        return view('admin.contracts.edit', compact('contract', 'statuses', 'optionProducts', 'selectedOptionProductIds'));
    }

    /**
     * 管理画面: 契約更新
     */
    public function update(Request $request, Contract $contract): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|exists:contract_statuses,code',
            'company_name' => 'required|string|max:255',
            'company_name_kana' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'contact_name' => 'required|string|max:255',
            'contact_name_kana' => 'nullable|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string|max:50',
            'postal_code' => 'nullable|string|max:20',
            'prefecture' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:100',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'usage_url_domain' => 'nullable|string|max:500',
            'desired_start_date' => 'nullable|date',
            'actual_start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'option_product_ids' => 'nullable|array',
            'option_product_ids.*' => 'exists:products,id',
        ]);

        // DB の NOT NULL カラムに null が入らないよう空文字に正規化
        $validated['usage_url_domain'] = $validated['usage_url_domain'] ?? '';

        $contract->update(array_diff_key($validated, array_flip(['option_product_ids'])));

        // オプション商品の有無を契約明細に反映（この契約のプランに紐づくオプションのみ許可）
        $optionProductIds = $validated['option_product_ids'] ?? [];
        $plan = $contract->contractPlan;
        if ($plan) {
            $allowedOptionIds = $plan->optionProducts()->pluck('products.id')->toArray();
            $optionProductIds = array_values(array_intersect($optionProductIds, $allowedOptionIds));
        } else {
            $optionProductIds = [];
        }

        $currentOptionItems = $contract->contractItems()->whereNotNull('product_id')->get();
        $currentIds = $currentOptionItems->pluck('product_id')->toArray();

        // 削除: 選択から外れたオプション明細を削除
        foreach ($currentOptionItems as $item) {
            if (! in_array($item->product_id, $optionProductIds, true)) {
                $item->delete();
            }
        }

        // 追加: 新たにチェックされたオプションの明細を追加
        $toAdd = array_diff($optionProductIds, $currentIds);
        foreach ($toAdd as $productId) {
            $product = Product::where('id', $productId)
                ->where('type', 'option')
                ->where('is_active', true)
                ->first();
            if ($product) {
                ContractItem::create([
                    'contract_id' => $contract->id,
                    'contract_plan_id' => null,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_code' => $product->code ?? '',
                    'quantity' => 1,
                    'unit_price' => $product->unit_price,
                    'subtotal' => $product->unit_price,
                ]);
            }
        }

        return redirect()
            ->route('admin.contracts.show', $contract)
            ->with('success', '契約を更新しました。');
    }

    /**
     * 管理画面: 契約削除
     */
    public function destroy(Contract $contract): RedirectResponse
    {
        $contract->delete();
        return redirect()
            ->route('admin.contracts.index')
            ->with('success', '契約を削除しました。');
    }
}
