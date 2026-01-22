<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * 商品一覧
     */
    public function index()
    {
        $products = Product::orderBy('display_order')
            ->orderBy('id')
            ->get();

        return view('admin.products.index', compact('products'));
    }

    /**
     * 商品新規作成フォーム
     */
    public function create()
    {
        return view('admin.products.create');
    }

    /**
     * 商品新規作成処理
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'unique:products,code'],
            'unit_price' => ['required', 'integer', 'min:0'],
            'type' => ['required', Rule::in(['plan', 'option', 'addon'])],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'display_order' => ['required', 'integer', 'min:0'],
        ]);

        $product = Product::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'unit_price' => $validated['unit_price'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'display_order' => $validated['display_order'] ?? 0,
        ]);

        return redirect()
            ->route('admin.products.index')
            ->with('success', '商品を登録しました。');
    }

    /**
     * 商品編集フォーム
     */
    public function edit(Product $product)
    {
        return view('admin.products.edit', compact('product'));
    }

    /**
     * 商品更新処理
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', Rule::unique('products', 'code')->ignore($product->id)],
            'unit_price' => ['required', 'integer', 'min:0'],
            'type' => ['required', Rule::in(['plan', 'option', 'addon'])],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'display_order' => ['required', 'integer', 'min:0'],
        ]);

        $product->update([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'unit_price' => $validated['unit_price'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'display_order' => $validated['display_order'] ?? 0,
        ]);

        return redirect()
            ->route('admin.products.index')
            ->with('success', '商品を更新しました。');
    }

    /**
     * 商品削除処理
     */
    public function destroy(Product $product)
    {
        // 既に契約で使用されている場合は削除不可
        if ($product->contractItems()->exists()) {
            return redirect()
                ->route('admin.products.index')
                ->with('error', 'この商品は既に契約で使用されているため削除できません。');
        }

        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', '商品を削除しました。');
    }
}
