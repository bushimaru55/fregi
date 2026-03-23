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
     * 製品マスター一覧
     */
    public function index(): View
    {
        $masters = ContractPlanMaster::orderBy('display_order')->get();
        return view('admin.contract-plan-masters.index', compact('masters'));
    }

    /**
     * 新規製品マスター作成フォーム
     */
    public function create(): View
    {
        return view('admin.contract-plan-masters.create');
    }

    /**
     * 新規製品マスター保存
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

        return redirect()->route('admin.contract-plan-masters.index')->with('success', '製品マスターが作成されました。');
    }

    /**
     * 製品マスター詳細
     */
    public function show(ContractPlanMaster $contractPlanMaster): View
    {
        $contractPlanMaster->load('contractPlans');
        return view('admin.contract-plan-masters.show', compact('contractPlanMaster'));
    }

    /**
     * 製品マスター編集フォーム
     */
    public function edit(ContractPlanMaster $contractPlanMaster): View
    {
        return view('admin.contract-plan-masters.edit', compact('contractPlanMaster'));
    }

    /**
     * 製品マスター更新
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

        return redirect()->route('admin.contract-plan-masters.index')->with('success', '製品マスターが更新されました。');
    }

    /**
     * 製品マスター削除
     */
    public function destroy(ContractPlanMaster $contractPlanMaster): RedirectResponse
    {
        // 関連する製品がある場合は削除不可
        if ($contractPlanMaster->contractPlans()->count() > 0) {
            return redirect()->route('admin.contract-plan-masters.index')
                ->with('error', 'このマスターに関連する製品が存在するため削除できません。');
        }

        $contractPlanMaster->delete();

        return redirect()->route('admin.contract-plan-masters.index')->with('success', '製品マスターが削除されました。');
    }
}
