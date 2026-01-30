<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContractPlan;
use App\Models\ContractPlanMaster;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ContractPlanController extends Controller
{
    /**
     * 契約プラン一覧（ベース商品 + オプション商品）
     */
    public function index(): View
    {
        $plans = ContractPlan::orderBy('display_order')->get();
        $optionProducts = Product::where('type', 'option')
            ->orderBy('display_order')
            ->get();
        return view('admin.contract-plans.index', compact('plans', 'optionProducts'));
    }

    /**
     * 新規契約プラン作成フォーム
     */
    public function create(Request $request): View
    {
        $masters = ContractPlanMaster::active()->orderBy('display_order')->get();
        $selectedMasterId = $request->input('master_id');
        
        // オプション商品作成時にベース商品を選択できるように、有効な契約プランを取得
        $basePlans = ContractPlan::active()->orderBy('display_order')->get();
        
        // _form.blade.phpでoptional($contractPlan)を使うため、nullを明示的に渡す
        $contractPlan = null;
        
        return view('admin.contract-plans.create', compact('masters', 'selectedMasterId', 'basePlans', 'contractPlan'));
    }

    /**
     * 新規契約プラン保存（オプション商品の場合はproductsテーブルにも保存）
     */
    public function store(Request $request): RedirectResponse
    {
        $productType = $request->input('product_type', 'base');
        
        // バリデーションルールを動的に設定
        $rules = [
            'name' => 'required|string|max:255',
            'price' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'display_order' => 'required|integer|min:0',
            'product_type' => 'nullable|in:base,option',
        ];

        if ($productType === 'base') {
            // ベース商品の場合
            $rules['contract_plan_master_id'] = 'nullable|exists:contract_plan_masters,id';
            $rules['item'] = 'required|string|max:50|unique:contract_plans,item';
            $rules['billing_type'] = 'required|in:one_time,monthly';
        } else {
            // オプション商品の場合
            $rules['item'] = 'required|string|max:50|unique:products,code';
            $rules['base_plan_ids'] = 'required|array|min:1';
            $rules['base_plan_ids.*'] = 'required|exists:contract_plans,id';
            $rules['billing_type'] = 'required|in:one_time,monthly';
        }

        $validated = $request->validate($rules);
        $validated['is_active'] = $request->has('is_active');

        DB::transaction(function () use ($validated, $productType, $request) {
            // ベース商品の場合はcontract_plansに保存
            if ($productType === 'base') {
                ContractPlan::create($validated);
            } 
            // オプション商品の場合はproductsテーブルに保存し、ベース商品と関連付け
            elseif ($productType === 'option') {
                $product = Product::create([
                    'code' => $validated['item'],
                    'name' => $validated['name'],
                    'unit_price' => $validated['price'],
                    'type' => 'option',
                    'billing_type' => $validated['billing_type'],
                    'description' => $validated['description'] ?? null,
                    'is_active' => $validated['is_active'],
                    'display_order' => $validated['display_order'],
                ]);
                
                // ベース商品（契約プラン）との関連付け
                $basePlanIds = $request->input('base_plan_ids', []);
                if (!empty($basePlanIds)) {
                    $product->contractPlans()->attach($basePlanIds);
                }
            }
        });

        $message = $productType === 'option' 
            ? 'オプション製品が作成されました。' 
            : '製品が作成されました。';

        return redirect()->route('admin.contract-plans.index')->with('success', $message);
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
        
        // オプション製品一覧を取得（既に紐づいているものは選択済みにする）
        $optionProducts = Product::where('type', 'option')
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();
        
        // このベース製品に既に紐づいているオプション製品のIDを取得
        $linkedOptionProductIds = $contractPlan->optionProducts()->pluck('products.id')->toArray();
        
        return view('admin.contract-plans.edit', compact('contractPlan', 'masters', 'optionProducts', 'linkedOptionProductIds'));
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
            'option_product_ids' => 'nullable|array',
            'option_product_ids.*' => 'exists:products,id',
        ]);

        $validated['is_active'] = $request->has('is_active');

        DB::transaction(function () use ($validated, $contractPlan, $request) {
            // ベース製品情報を更新
            $contractPlan->update($validated);
            
            // オプション製品の紐づけを更新
            $optionProductIds = $request->input('option_product_ids', []);
            $contractPlan->optionProducts()->sync($optionProductIds);
        });

        return redirect()->route('admin.contract-plans.index')->with('success', '製品が更新されました。');
    }

    /**
     * 契約プラン削除
     * このプランを参照する契約が1件でもある場合は削除不可とする。
     */
    public function destroy(ContractPlan $contractPlan): RedirectResponse
    {
        if ($contractPlan->contracts()->exists()) {
            $count = $contractPlan->contracts()->count();
            return redirect()
                ->route('admin.contract-plans.index')
                ->withErrors(["この契約プランは契約が{$count}件紐づいているため削除できません。紐づく契約を変更または削除してから再度お試しください。"]);
        }

        // オプション製品との紐づけを解除（外部キー制約を避ける）
        $contractPlan->optionProducts()->detach();

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
