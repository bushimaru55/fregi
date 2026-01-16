<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContractPlan;
use App\Models\ContractPlanMaster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContractPlanController extends Controller
{
    /**
     * 契約プラン一覧
     */
    public function index(): View
    {
        $plans = ContractPlan::orderBy('display_order')->get();
        return view('admin.contract-plans.index', compact('plans'));
    }

    /**
     * 新規契約プラン作成フォーム
     */
    public function create(Request $request): View
    {
        $masters = ContractPlanMaster::active()->orderBy('display_order')->get();
        $selectedMasterId = $request->input('master_id');
        return view('admin.contract-plans.create', compact('masters', 'selectedMasterId'));
    }

    /**
     * 新規契約プラン保存
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'contract_plan_master_id' => 'nullable|exists:contract_plan_masters,id',
            'item' => 'required|string|max:50|unique:contract_plans,item',
            'name' => 'required|string|max:255',
            'price' => 'required|integer|min:0',
            'billing_type' => 'required|in:one_time,monthly',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'display_order' => 'required|integer|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');

        ContractPlan::create($validated);

        return redirect()->route('admin.contract-plans.index')->with('success', '契約プランが作成されました。');
    }

    /**
     * 契約プラン詳細
     */
    public function show(ContractPlan $contractPlan): View
    {
        return view('admin.contract-plans.show', compact('contractPlan'));
    }

    /**
     * 契約プラン編集フォーム
     */
    public function edit(ContractPlan $contractPlan): View
    {
        $masters = ContractPlanMaster::active()->orderBy('display_order')->get();
        return view('admin.contract-plans.edit', compact('contractPlan', 'masters'));
    }

    /**
     * 契約プラン更新
     */
    public function update(Request $request, ContractPlan $contractPlan): RedirectResponse
    {
        $validated = $request->validate([
            'contract_plan_master_id' => 'nullable|exists:contract_plan_masters,id',
            'item' => 'required|string|max:50|unique:contract_plans,item,' . $contractPlan->id,
            'name' => 'required|string|max:255',
            'price' => 'required|integer|min:0',
            'billing_type' => 'required|in:one_time,monthly',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'display_order' => 'required|integer|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $contractPlan->update($validated);

        return redirect()->route('admin.contract-plans.index')->with('success', '契約プランが更新されました。');
    }

    /**
     * 契約プラン削除
     */
    public function destroy(ContractPlan $contractPlan): RedirectResponse
    {
        $contractPlan->delete();
        return redirect()->route('admin.contract-plans.index')->with('success', '契約プランが削除されました。');
    }

    /**
     * 表示順を更新
     */
    public function updateOrder(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'required|integer|exists:contract_plans,id',
        ]);

        foreach ($validated['order'] as $index => $id) {
            ContractPlan::where('id', $id)->update(['display_order' => $index]);
        }

        return response()->json(['success' => true, 'message' => '表示順を更新しました。']);
    }
}
