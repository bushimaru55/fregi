<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContractPlanMaster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContractPlanMasterController extends Controller
{
    /**
     * 契約プランマスター一覧
     */
    public function index(): View
    {
        $masters = ContractPlanMaster::orderBy('display_order')->get();
        return view('admin.contract-plan-masters.index', compact('masters'));
    }

    /**
     * 新規契約プランマスター作成フォーム
     */
    public function create(): View
    {
        return view('admin.contract-plan-masters.create');
    }

    /**
     * 新規契約プランマスター保存
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'display_order' => 'required|integer|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');

        ContractPlanMaster::create($validated);

        return redirect()->route('admin.contract-plan-masters.index')->with('success', '契約プランマスターが作成されました。');
    }

    /**
     * 契約プランマスター詳細
     */
    public function show(ContractPlanMaster $contractPlanMaster): View
    {
        $contractPlanMaster->load('contractPlans');
        return view('admin.contract-plan-masters.show', compact('contractPlanMaster'));
    }

    /**
     * 契約プランマスター編集フォーム
     */
    public function edit(ContractPlanMaster $contractPlanMaster): View
    {
        return view('admin.contract-plan-masters.edit', compact('contractPlanMaster'));
    }

    /**
     * 契約プランマスター更新
     */
    public function update(Request $request, ContractPlanMaster $contractPlanMaster): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'display_order' => 'required|integer|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $contractPlanMaster->update($validated);

        return redirect()->route('admin.contract-plan-masters.index')->with('success', '契約プランマスターが更新されました。');
    }

    /**
     * 契約プランマスター削除
     */
    public function destroy(ContractPlanMaster $contractPlanMaster): RedirectResponse
    {
        // 関連する契約プランがある場合は削除不可
        if ($contractPlanMaster->contractPlans()->count() > 0) {
            return redirect()->route('admin.contract-plan-masters.index')
                ->with('error', 'このマスターに関連する契約プランが存在するため削除できません。');
        }

        $contractPlanMaster->delete();

        return redirect()->route('admin.contract-plan-masters.index')->with('success', '契約プランマスターが削除されました。');
    }
}
