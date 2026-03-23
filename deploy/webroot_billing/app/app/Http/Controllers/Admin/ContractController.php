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
        $q = Contract::query()->with(['contractPlan', 'contractStatus', 'contractItems.contractPlan', 'contractItems.product']);

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

        // この製品を含む申し込み
        if ($request->filled('contract_plan_id')) {
            $q->whereHas('contractItems', fn (Builder $q) => $q->where('contract_plan_id', $request->input('contract_plan_id')));
        }

        // このオプション商品を含む申し込み
        if ($request->filled('product_id')) {
            $q->whereHas('contractItems', fn (Builder $q) => $q->where('product_id', $request->input('product_id')));
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
        $optionProducts = Product::where('type', 'option')->where('is_active', true)->orderBy('display_order')->orderBy('id')->get();

        return view('admin.contracts.index', compact('contracts', 'statuses', 'plans', 'optionProducts'));
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

            $headers = ['ID', '会社名', '担当者', '選択商品', '金額', 'ステータス', '申込日'];
            fputcsv($stream, $headers);

            $this->queryWithFilters($request)
                ->with(['contractItems.contractPlan', 'contractItems.product'])
                ->orderBy('created_at', 'desc')
                ->chunk(500, function ($contracts) use ($stream) {
                    foreach ($contracts as $contract) {
                        $itemsSummary = $contract->contractItems->map(fn ($i) => $i->product_name ?? $i->contractPlan->name ?? $i->product->name ?? '')->join(', ');
                        $totalAmount = $contract->contractItems->sum('subtotal');
                        fputcsv($stream, [
                            $contract->id,
                            $contract->company_name,
                            $contract->contact_name,
                            $itemsSummary ?: (optional($contract->contractPlan)->name ?? ''),
                            number_format($totalAmount) . '円',
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
        $contract->load(['contractPlan', 'contractItems.contractPlan', 'contractItems.product']);
        return view('admin.contracts.show', compact('contract'));
    }

    /**
     * 管理画面: 契約編集フォーム（選択商品の編集・追加・削除）
     */
    public function edit(Contract $contract): View
    {
        $contract->load(['contractPlan', 'contractItems.contractPlan', 'contractItems.product']);
        $statuses = ContractStatus::where('is_active', true)->orderBy('display_order')->orderBy('id')->get();
        $basePlans = ContractPlan::where('is_active', true)->orderBy('display_order')->orderBy('id')->get();
        $optionProducts = Product::where('type', 'option')->where('is_active', true)->orderBy('display_order')->orderBy('id')->get();
        $currentBasePlanIds = $contract->contractItems->whereNotNull('contract_plan_id')->pluck('contract_plan_id')->unique()->values()->toArray();
        return view('admin.contracts.edit', compact('contract', 'statuses', 'basePlans', 'optionProducts', 'currentBasePlanIds'));
    }

    /**
     * 管理画面: 契約更新（選択商品の同期・代表プラン更新）
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
            'delete_item_ids' => 'nullable|array',
            'delete_item_ids.*' => 'integer|exists:contract_items,id',
            'new_base_plan_ids' => 'nullable|array',
            'new_base_plan_ids.*' => 'exists:contract_plans,id',
            'new_option_product_ids' => 'nullable|array',
            'new_option_product_ids.*' => 'exists:products,id',
        ]);

        $validated['usage_url_domain'] = $validated['usage_url_domain'] ?? '';
        $contract->update(array_diff_key($validated, array_flip(['delete_item_ids', 'new_base_plan_ids', 'new_option_product_ids'])));

        $deleteIds = $validated['delete_item_ids'] ?? [];
        if (!empty($deleteIds)) {
            $contract->contractItems()->whereIn('id', $deleteIds)->delete();
        }

        $newBasePlanIds = array_values(array_unique($validated['new_base_plan_ids'] ?? []));
        $currentBasePlanIds = $contract->contractItems()->whereNotNull('contract_plan_id')->pluck('contract_plan_id')->toArray();
        $toAddBase = array_diff($newBasePlanIds, $currentBasePlanIds);
        foreach ($toAddBase as $planId) {
            $plan = ContractPlan::find($planId);
            if ($plan && $plan->is_active) {
                ContractItem::create([
                    'contract_id' => $contract->id,
                    'contract_plan_id' => $plan->id,
                    'product_id' => null,
                    'product_name' => $plan->name,
                    'product_code' => $plan->item ?? '',
                    'quantity' => 1,
                    'unit_price' => $plan->price,
                    'subtotal' => $plan->price,
                ]);
            }
        }

        $newOptionProductIds = array_values(array_unique($validated['new_option_product_ids'] ?? []));
        $currentOptionProductIds = $contract->contractItems()->whereNotNull('product_id')->pluck('product_id')->toArray();
        $toAddOption = array_diff($newOptionProductIds, $currentOptionProductIds);
        foreach ($toAddOption as $productId) {
            $product = Product::where('id', $productId)->where('type', 'option')->where('is_active', true)->first();
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

        $firstBase = $contract->contractItems()->whereNotNull('contract_plan_id')->orderBy('id')->first();
        $contract->update(['contract_plan_id' => $firstBase ? $firstBase->contract_plan_id : null]);

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

    /**
     * 管理画面: 選択した申し込みの一括削除
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:contracts,id',
        ], [
            'ids.required' => '削除する申し込みを1件以上選択してください。',
        ]);

        $count = Contract::whereIn('id', $validated['ids'])->delete();

        return redirect()
            ->route('admin.contracts.index', $request->only(['keyword', 'status', 'contract_plan_id', 'product_id', 'created_from', 'created_to']))
            ->with('success', $count . '件の申し込みを削除しました。');
    }
}
