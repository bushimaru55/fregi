<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\View\View;

class ContractController extends Controller
{
    /**
     * 管理画面: 契約一覧
     */
    public function index(): View
    {
        $contracts = Contract::with(['contractPlan'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('admin.contracts.index', compact('contracts'));
    }

    /**
     * 管理画面: 契約詳細
     */
    public function show(Contract $contract): View
    {
        $contract->load(['contractPlan', 'contractItems.product']);
        return view('admin.contracts.show', compact('contract'));
    }
}
