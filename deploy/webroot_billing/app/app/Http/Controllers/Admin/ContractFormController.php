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
            ->with('contractPlanMaster')
            ->orderBy('display_order')
            ->get();
        
        // 保存されているURL一覧を取得（新しい順）
        $savedUrls = ContractFormUrl::orderBy('created_at', 'desc')
            ->paginate(20);

        // 一覧に含まれる plan_ids からプラン名マップを取得（選択されているプラン名表示用）
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
            'plan_ids' => 'required|array|min:1',
            'plan_ids.*' => 'required|integer|exists:contract_plans,id',
        ], [
            'plan_ids.required' => '少なくとも1つの契約プランを選択してください。',
            'plan_ids.min' => '少なくとも1つの契約プランを選択してください。',
            'plan_ids.*.exists' => '選択されたプランが存在しません。',
        ]);

        $planIds = $validated['plan_ids'];
        sort($planIds); // IDをソートしてURLを統一
        
        // 閲覧画面用URL（申込フォームの閲覧用）
        // 選択されたプランIDをクエリパラメータに含めた申込フォームURLを生成
        $viewUrl = route('contract.create', ['plans' => implode(',', $planIds)]);
        
        // データベースに保存（有効期限は設定しないが、管理用に保存）
        $contractFormUrl = ContractFormUrl::create([
            'token' => null, // トークンは使用しない（申込フォームなので）
            'url' => $viewUrl,
            'plan_ids' => $planIds,
            'name' => null, // 後で編集可能にする
            'expires_at' => now()->addYears(10), // 実質無期限（フォームは常に有効）
            'is_active' => true,
        ]);
        
        return redirect()->route('admin.contract-forms.index')
            ->with('generated_view_url', $viewUrl)
            ->with('success', '閲覧画面用URLが生成され、保存されました。')
            ->with('selected_plan_ids', $planIds);
    }

    /**
     * URLを削除
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
            ->with('success', 'URLを削除しました。');
    }
}
