<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContractFormUrl;
use App\Models\ContractPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ContractFormController extends Controller
{
    /**
     * 新規申込フォーム管理画面
     */
    public function index(): View
    {
        $plans = ContractPlan::active()
            ->with(['optionProducts' => function ($q) {
                $q->orderBy('products.display_order');
            }])
            ->orderBy('display_order')
            ->get();
        
        // 保存されているURL一覧を取得（新しい順）
        $savedUrls = ContractFormUrl::orderBy('created_at', 'desc')
            ->paginate(20);

        // 一覧に含まれる plan_ids から製品名マップを取得（選択されている製品名表示用）
        $planIds = $savedUrls->pluck('plan_ids')->flatten()->unique()->filter()->values()->all();
        $planNames = ContractPlan::whereIn('id', $planIds)->pluck('name', 'id')->all();
        
        return view('admin.contract-forms.index', compact('plans', 'savedUrls', 'planNames'));
    }

    /**
     * 閲覧画面用URL生成処理
     */
    public function generate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plan_ids'  => 'required|array|min:1',
            'plan_ids.*' => 'required|integer|exists:contract_plans,id',
            'name'      => ['nullable', 'string', 'max:255'],
            'job_type'  => ['required', 'string', 'in:CAPTURE,AUTH'],
        ], [
            'plan_ids.required'  => '少なくとも1つの製品を選択してください。',
            'plan_ids.min'       => '少なくとも1つの製品を選択してください。',
            'plan_ids.*.exists'  => '選択された製品が存在しません。',
            'job_type.required'  => '決済処理方法を選択してください。',
            'job_type.in'        => '決済処理方法の値が不正です。',
        ]);

        $planIds = $validated['plan_ids'];
        sort($planIds);

        $viewUrl = route('contract.create', ['plans' => implode(',', $planIds)]);

        ContractFormUrl::create([
            'token'      => null,
            'url'        => $viewUrl,
            'plan_ids'   => $planIds,
            'name'       => $validated['name'] ?? null,
            'expires_at' => now()->addYears(10),
            'is_active'  => true,
            'job_type'   => $validated['job_type'],
        ]);

        return redirect()->route('admin.contract-forms.index')
            ->with('generated_view_url', $viewUrl)
            ->with('success', 'フォームURLが発行されました。')
            ->with('selected_plan_ids', $planIds);
    }

    /**
     * フォーム編集画面（登録する製品の紐付けも編集可能）
     */
    public function edit(ContractFormUrl $contractFormUrl): View
    {
        $plans = ContractPlan::active()
            ->with(['optionProducts' => function ($q) {
                $q->orderBy('products.display_order');
            }])
            ->orderBy('display_order')
            ->get();

        return view('admin.contract-forms.edit', compact('contractFormUrl', 'plans'));
    }

    /**
     * フォーム更新（フォーム名・登録する製品の紐付け・有効/無効）
     */
    public function update(Request $request, ContractFormUrl $contractFormUrl): RedirectResponse
    {
        $validated = $request->validate([
            'name'       => ['nullable', 'string', 'max:255'],
            'plan_ids'   => ['required', 'array', 'min:1'],
            'plan_ids.*' => ['required', 'integer', 'exists:contract_plans,id'],
            'is_active'  => ['required', 'boolean'],
            'job_type'   => ['required', 'string', 'in:CAPTURE,AUTH'],
        ], [
            'plan_ids.required' => '少なくとも1つの製品を選択してください。',
            'plan_ids.min'      => '少なくとも1つの製品を選択してください。',
            'plan_ids.*.exists' => '選択された製品が存在しません。',
            'job_type.required' => '決済処理方法を選択してください。',
            'job_type.in'       => '決済処理方法の値が不正です。',
        ]);

        $planIds = $validated['plan_ids'];
        sort($planIds);
        $viewUrl = route('contract.create', ['plans' => implode(',', $planIds)]);

        $contractFormUrl->update([
            'name'      => $validated['name'] ?: null,
            'plan_ids'  => $planIds,
            'url'       => $viewUrl,
            'is_active' => (bool) $validated['is_active'],
            'job_type'  => $validated['job_type'],
        ]);

        return redirect()->route('admin.contract-forms.index')
            ->with('success', 'フォームを更新しました。');
    }

    /**
     * フォーム削除
     */
    public function destroy(ContractFormUrl $contractFormUrl): RedirectResponse
    {
        // トークンがある場合のみCacheから削除
        if ($contractFormUrl->token) {
            $cacheKey = 'contract_confirm_view:' . $contractFormUrl->token;
            Cache::forget($cacheKey);
        }
        
        $contractFormUrl->delete();
        
        return redirect()->route('admin.contract-forms.index')
            ->with('success', 'フォームを削除しました。');
    }
}
