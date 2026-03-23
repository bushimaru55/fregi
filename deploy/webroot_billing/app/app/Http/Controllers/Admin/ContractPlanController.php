<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\ContractPlan;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ContractPlanController extends Controller
{
    /**
     * 製品一覧（ベース商品 + オプション商品）。申し込み数は contract_items に含まれる契約数。
     */
    public function index(): View
    {
        $plans = ContractPlan::orderBy('display_order')->get();
        $optionProducts = Product::where('type', 'option')
            ->orderBy('display_order')
            ->get();
        $contractCountByPlan = DB::table('contract_items')
            ->whereNotNull('contract_plan_id')
            ->selectRaw('contract_plan_id, count(distinct contract_id) as c')
            ->groupBy('contract_plan_id')
            ->pluck('c', 'contract_plan_id');
        $contractCountByProduct = DB::table('contract_items')
            ->whereNotNull('product_id')
            ->selectRaw('product_id, count(distinct contract_id) as c')
            ->groupBy('product_id')
            ->pluck('c', 'product_id');
        return view('admin.contract-plans.index', compact('plans', 'optionProducts', 'contractCountByPlan', 'contractCountByProduct'));
    }

    /**
     * 新規製品作成フォーム
     */
    public function create(Request $request): View
    {
        // オプション商品作成時にベース商品を選択できるように、有効な製品を取得
        $basePlans = ContractPlan::active()->orderBy('display_order')->get();
        
        // _form.blade.phpでoptional($contractPlan)を使うため、nullを明示的に渡す
        $contractPlan = null;
        
        return view('admin.contract-plans.create', compact('basePlans', 'contractPlan'));
    }

    /**
     * 新規製品保存（オプション商品の場合はproductsテーブルにも保存）
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
            // ベース商品の場合（製品マスターは廃止のため使用しない）
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
                
                // ベース商品（製品）との関連付け
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
     * 製品詳細
     */
    public function show(ContractPlan $contractPlan): View
    {
        return view('admin.contract-plans.show', compact('contractPlan'));
    }

    /**
     * 製品編集フォーム
     */
    public function edit(ContractPlan $contractPlan): View
    {
        // オプション製品一覧を取得（既に紐づいているものは選択済みにする）
        $optionProducts = Product::where('type', 'option')
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();
        
        // このベース製品に既に紐づいているオプション製品のIDを取得
        $linkedOptionProductIds = $contractPlan->optionProducts()->pluck('products.id')->toArray();
        
        return view('admin.contract-plans.edit', compact('contractPlan', 'optionProducts', 'linkedOptionProductIds'));
    }

    /**
     * 製品更新
     */
    public function update(Request $request, ContractPlan $contractPlan): RedirectResponse
    {
        $validated = $request->validate([
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

    /** テスト用製品コード（紐づく契約があっても参照を外して削除可能） */
    private const TEST_PRODUCT_CODES_FORCE_DELETABLE = ['TEST-ONE-TIME', 'TEST-MONTHLY'];

    /**
     * 製品削除
     * 通常は申し込みが1件でも紐づく場合は削除不可。
     * TEST-ONE-TIME / TEST-MONTHLY の場合は紐づく契約の参照を外してから削除する。
     */
    public function destroy(ContractPlan $contractPlan): RedirectResponse
    {
        $isTestForceDeletable = in_array($contractPlan->item, self::TEST_PRODUCT_CODES_FORCE_DELETABLE, true);

        if (! $isTestForceDeletable) {
            $contractCount = Contract::whereHas('contractItems', fn ($q) => $q->where('contract_plan_id', $contractPlan->id))->count();
            if ($contractCount > 0) {
                return redirect()
                    ->route('admin.contract-plans.index')
                    ->withErrors(["この製品は申し込みが{$contractCount}件紐づいているため削除できません。紐づく契約を変更または削除してから再度お試しください。"]);
            }
        }

        DB::transaction(function () use ($contractPlan, $isTestForceDeletable) {
            if ($isTestForceDeletable) {
                ContractItem::where('contract_plan_id', $contractPlan->id)->update(['contract_plan_id' => null]);
                Contract::where('contract_plan_id', $contractPlan->id)->update(['contract_plan_id' => null]);
            }
            $contractPlan->optionProducts()->detach();
            $contractPlan->delete();
        });

        return redirect()->route('admin.contract-plans.index')->with('success', '製品が削除されました。');
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
