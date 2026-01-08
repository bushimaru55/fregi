<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    {{-- 商品名 --}}
    <div class="md:col-span-2">
        <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
            商品名 <span class="text-red-500">*</span>
        </label>
        <input type="text" name="name" id="name" 
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('name') border-red-500 @enderror" 
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
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono @error('code') border-red-500 @enderror" 
            value="{{ old('code', $product->code ?? '') }}" required>
        @error('code')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- 単価 --}}
    <div>
        <label for="price" class="block text-sm font-semibold text-gray-700 mb-2">
            単価（税込） <span class="text-red-500">*</span>
        </label>
        <input type="number" name="price" id="price" min="0" step="1"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('price') border-red-500 @enderror" 
            value="{{ old('price', $product->price ?? '') }}" required>
        @error('price')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- 商品種別 --}}
    <div>
        <label for="type" class="block text-sm font-semibold text-gray-700 mb-2">
            商品種別 <span class="text-red-500">*</span>
        </label>
        <select name="type" id="type" 
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('type') border-red-500 @enderror" required>
            <option value="plan" {{ old('type', $product->type ?? '') == 'plan' ? 'selected' : '' }}>プラン</option>
            <option value="option" {{ old('type', $product->type ?? '') == 'option' ? 'selected' : '' }}>オプション</option>
            <option value="addon" {{ old('type', $product->type ?? '') == 'addon' ? 'selected' : '' }}>追加商品</option>
        </select>
        @error('type')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- 表示順 --}}
    <div>
        <label for="display_order" class="block text-sm font-semibold text-gray-700 mb-2">
            表示順 <span class="text-red-500">*</span>
        </label>
        <input type="number" name="display_order" id="display_order" min="0" step="1"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('display_order') border-red-500 @enderror" 
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
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('description') border-red-500 @enderror">{{ old('description', $product->description ?? '') }}</textarea>
        @error('description')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- 有効フラグ --}}
    <div class="md:col-span-2">
        <label class="flex items-center">
            <input type="checkbox" name="is_active" value="1" 
                class="mr-2 w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }}>
            <span class="text-sm font-semibold text-gray-700">この商品を有効にする</span>
        </label>
    </div>
</div>

