<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    {{-- 商品名 --}}
    <div class="md:col-span-2">
        <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
            商品名 <span class="text-red-500">*</span>
        </label>
        <input type="text" name="name" id="name" 
            class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('name') border-red-500 @enderror" 
            value="{{ old('name', $product->name ?? '') }}" required>
        @error('name')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- 商品コード --}}
    <div>
        <label for="code" class="block text-sm font-semibold text-gray-700 mb-2">
            商品コード <span class="text-red-500">*</span>
        </label>
        <input type="text" name="code" id="code" 
            class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input font-mono @error('code') border-red-500 @enderror" 
            value="{{ old('code', $product->code ?? '') }}" required>
        @error('code')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- 単価 --}}
    <div>
        <label for="unit_price" class="block text-sm font-semibold text-gray-700 mb-2">
            単価（税込） <span class="text-red-500">*</span>
        </label>
        <input type="number" name="unit_price" id="unit_price" min="0" step="1"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('unit_price') border-red-500 @enderror" 
            value="{{ old('unit_price', $product->unit_price ?? '') }}" required>
        @error('unit_price')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- 商品種別 --}}
    <div>
        <label for="type" class="block text-sm font-semibold text-gray-700 mb-2">
            商品種別 <span class="text-red-500">*</span>
        </label>
        <select name="type" id="type" 
            class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('type') border-red-500 @enderror" required>
            <option value="plan" {{ old('type', $product->type ?? '') == 'plan' ? 'selected' : '' }}>プラン</option>
            <option value="option" {{ old('type', $product->type ?? '') == 'option' ? 'selected' : '' }}>オプション</option>
            <option value="addon" {{ old('type', $product->type ?? '') == 'addon' ? 'selected' : '' }}>追加商品</option>
        </select>
        @error('type')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- 決済タイプ（オプション製品の場合のみ表示） --}}
    <div id="product-billing-type-field">
        <label for="billing_type" class="block text-sm font-semibold text-gray-700 mb-2">
            決済タイプ <span class="text-red-500">*</span>
        </label>
        <select name="billing_type" id="billing_type" 
            class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('billing_type') border-red-500 @enderror">
            <option value="one_time" {{ old('billing_type', $product->billing_type ?? 'one_time') === 'one_time' ? 'selected' : '' }}>一回限り</option>
            <option value="monthly" {{ old('billing_type', $product->billing_type ?? 'one_time') === 'monthly' ? 'selected' : '' }}>月額課金</option>
        </select>
        @error('billing_type')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
        <p class="text-xs text-gray-600 mt-1">
            <i class="fas fa-credit-card mr-1"></i>オプション製品の課金種別（一回限りまたは月額課金）。決済API連携は行いません。
        </p>
    </div>

    {{-- 表示順 --}}
    <div>
        <label for="display_order" class="block text-sm font-semibold text-gray-700 mb-2">
            表示順 <span class="text-red-500">*</span>
        </label>
        <input type="number" name="display_order" id="display_order" min="0" step="1"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('display_order') border-red-500 @enderror" 
            value="{{ old('display_order', $product->display_order ?? 0) }}" required>
        @error('display_order')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- 商品説明 --}}
    <div class="md:col-span-2">
        <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">
            商品説明
        </label>
        <textarea name="description" id="description" rows="4"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('description') border-red-500 @enderror">{{ old('description', $product->description ?? '') }}</textarea>
        @error('description')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- 有効フラグ --}}
    <div class="md:col-span-2">
        <label class="flex items-center">
            <input type="checkbox" name="is_active" value="1" 
                class="mr-2 w-5 h-5 theme-checkbox-accent border-gray-300 rounded"
                {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }}>
            <span class="text-sm font-semibold text-gray-700">この商品を有効にする</span>
        </label>
    </div>
</div>

<script>
// 商品種別がオプションの場合のみ決済タイプを表示
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const billingTypeField = document.getElementById('product-billing-type-field');
    const billingTypeSelect = document.getElementById('billing_type');

    function toggleBillingTypeField() {
        if (!typeSelect || !billingTypeField) return;
        const isOption = typeSelect.value === 'option';
        billingTypeField.style.display = isOption ? 'block' : 'none';
        if (billingTypeSelect) {
            billingTypeSelect.required = isOption;
        }
    }

    if (typeSelect) {
        typeSelect.addEventListener('change', toggleBillingTypeField);
        toggleBillingTypeField();
    }
});
</script>

