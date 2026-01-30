<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContractStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContractStatusController extends Controller
{
    public function index(): View
    {
        $statuses = ContractStatus::orderBy('display_order')->orderBy('id')->get();
        return view('admin.contract-statuses.index', compact('statuses'));
    }

    public function create(): View
    {
        return view('admin.contract-statuses.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:contract_statuses,code|regex:/^[a-z0-9_]+$/',
            'name' => 'required|string|max:100',
            'display_order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        ContractStatus::create($validated);
        return redirect()->route('admin.contract-statuses.index')->with('success', 'ステータスを追加しました。');
    }

    public function edit(ContractStatus $contractStatus): View
    {
        return view('admin.contract-statuses.edit', compact('contractStatus'));
    }

    public function update(Request $request, ContractStatus $contractStatus): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'display_order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        $contractStatus->update($validated);
        return redirect()->route('admin.contract-statuses.index')->with('success', 'ステータスを更新しました。');
    }

    public function destroy(ContractStatus $contractStatus): RedirectResponse
    {
        if ($contractStatus->contracts()->exists()) {
            return redirect()->route('admin.contract-statuses.index')
                ->with('error', 'この契約状態を使用している申込が存在するため削除できません。');
        }
        $contractStatus->delete();
        return redirect()->route('admin.contract-statuses.index')->with('success', 'ステータスを削除しました。');
    }
}
