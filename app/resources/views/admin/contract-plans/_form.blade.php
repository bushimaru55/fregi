<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    {{-- 契約プランマスター選択（アプリ固有項目） --}}
    <div class="md:col-span-2">
        <label for="contract_plan_master_id" class="block text-sm font-semibold text-gray-700 mb-2">
            契約プランマスター
            <span class="ml-2 text-xs font-normal text-gray-500 bg-gray-100 px-2 py-0.5 rounded">アプリ固有</span>
        </label>
        <select name="contract_plan_master_id" id="contract_plan_master_id" 
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('contract_plan_master_id') border-red-500 @enderror">
            <option value="">（マスターなし）</option>
            @if(isset($masters))
                @foreach($masters as $master)
                    <option value="{{ $master->id }}" 
                        {{ old('contract_plan_master_id', $contractPlan->contract_plan_master_id ?? ($selectedMasterId ?? '')) == $master->id ? 'selected' : '' }}>
                        {{ $master->name }}
                    </option>
                @endforeach
            @endif
        </select>
        @error('contract_plan_master_id')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
        <p class="text-xs text-gray-600 mt-1">
            <i class="fas fa-folder-open mr-1"></i>このプランが所属するマスターを選択してください
        </p>
    </div>

    {{-- F-REGI標準項目セクション --}}
    <div class="md:col-span-2 mb-4">
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
            <h3 class="text-sm font-bold text-blue-800 mb-2">
                <i class="fas fa-info-circle mr-2"></i>F-REGI標準項目
            </h3>
            <p class="text-xs text-blue-700">以下の項目はF-REGI決済システムとの連携で使用されます</p>
        </div>
    </div>

    {{-- プランコード（ITEM） - F-REGI標準項目 --}}
    <div>
        <label for="item" class="block text-sm font-semibold text-gray-700 mb-2">
            プランコード（ITEM） <span class="text-red-500">*</span>
            <span class="ml-2 text-xs font-normal text-blue-600 bg-blue-100 px-2 py-0.5 rounded">F-REGI標準</span>
        </label>
        <input type="text" name="item" id="item" 
            class="w-full px-4 py-2 border-2 border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono @error('item') border-red-500 @enderror" 
            value="{{ old('item', $contractPlan->item ?? '') }}" required placeholder="例: PLAN-050">
        @error('item')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
        <p class="text-xs text-gray-600 mt-1">
            <i class="fas fa-tag mr-1"></i>F-REGI標準: <strong>ITEM</strong> - 商品コード（一意の識別子）
            <br>例: PLAN-050, PLAN-100, PLAN-150
        </p>
    </div>

    {{-- プラン名（ITEMNAME相当） - F-REGI標準項目 --}}
    <div>
        <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
            プラン名（ITEMNAME相当） <span class="text-red-500">*</span>
            <span class="ml-2 text-xs font-normal text-blue-600 bg-blue-100 px-2 py-0.5 rounded">F-REGI標準</span>
        </label>
        <input type="text" name="name" id="name" 
            class="w-full px-4 py-2 border-2 border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror" 
            value="{{ old('name', $contractPlan->name ?? '') }}" required placeholder="例: 学習ページ数 50">
        @error('name')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
        <p class="text-xs text-gray-600 mt-1">
            <i class="fas fa-file-signature mr-1"></i>F-REGI標準: <strong>ITEMNAME</strong> - 商品名（決済画面に表示）
        </p>
    </div>

    {{-- 料金（AMOUNT相当） - F-REGI標準項目 --}}
    <div>
        <label for="price" class="block text-sm font-semibold text-gray-700 mb-2">
            料金（AMOUNT相当・税込） <span class="text-red-500">*</span>
            <span class="ml-2 text-xs font-normal text-blue-600 bg-blue-100 px-2 py-0.5 rounded">F-REGI標準</span>
        </label>
        <div class="relative">
            <input type="number" name="price" id="price" min="0" step="1"
                class="w-full px-4 py-2 pr-12 border-2 border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('price') border-red-500 @enderror" 
                value="{{ old('price', $contractPlan->price ?? '') }}" required placeholder="0">
            <span class="absolute right-4 top-2.5 text-gray-500 font-semibold">円</span>
        </div>
        @error('price')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
        <p class="text-xs text-gray-600 mt-1">
            <i class="fas fa-yen-sign mr-1"></i>F-REGI標準: <strong>AMOUNT</strong> - 決済金額（税込、整数値）
        </p>
    </div>

    {{-- 表示順 --}}
    <div>
        <label for="display_order" class="block text-sm font-semibold text-gray-700 mb-2">
            表示順 <span class="text-red-500">*</span>
        </label>
        <input type="number" name="display_order" id="display_order" min="0" step="1"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('display_order') border-red-500 @enderror" 
            value="{{ old('display_order', $contractPlan->display_order ?? 0) }}" required>
        @error('display_order')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- プラン説明 --}}
    <div class="md:col-span-2">
        <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">
            プラン説明
        </label>
        <textarea name="description" id="description" rows="4"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('description') border-red-500 @enderror">{{ old('description', $contractPlan->description ?? '') }}</textarea>
        @error('description')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- 有効フラグ --}}
    <div class="md:col-span-2">
        <label class="flex items-center">
            <input type="checkbox" name="is_active" value="1" 
                class="mr-2 w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                {{ old('is_active', $contractPlan->is_active ?? true) ? 'checked' : '' }}>
            <span class="text-sm font-semibold text-gray-700">このプランを有効にする</span>
        </label>
    </div>
</div>

