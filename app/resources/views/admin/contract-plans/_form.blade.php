<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    {{-- 製品種別（新規作成時のみ） --}}
    @if(!isset($contractPlan))
    <div class="md:col-span-2">
        <label for="product_type" class="block text-sm font-semibold text-gray-700 mb-2">
            製品種別 <span class="text-red-500">*</span>
        </label>
        <select name="product_type" id="product_type" 
            class="native-select w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('product_type') border-red-500 @enderror" required>
            <option value="base" {{ old('product_type', 'base') === 'base' ? 'selected' : '' }}>ベース製品</option>
            <option value="option" {{ old('product_type') === 'option' ? 'selected' : '' }}>オプション製品</option>
        </select>
        @error('product_type')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
        <p class="text-xs text-gray-600 mt-1">
            <i class="fas fa-info-circle mr-1"></i>
            <strong>ベース製品</strong>: 基本となる製品（contract_plansテーブルに保存）<br>
            <strong>オプション製品</strong>: ベース製品に追加できるオプション（productsテーブルに保存。1回限り・月額課金は商品マスタで設定）
        </p>
    </div>
    @endif

    {{-- 製品マスター選択（アプリ固有項目・ベース製品のみ） --}}
    <div class="md:col-span-2" id="contract-plan-master-field">
        <label for="contract_plan_master_id" class="block text-sm font-semibold text-gray-700 mb-2">
            製品マスター
            <span class="ml-2 text-xs font-normal text-gray-500 bg-gray-100 px-2 py-0.5 rounded">アプリ固有</span>
        </label>
        <select name="contract_plan_master_id" id="contract_plan_master_id" 
            class="native-select w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('contract_plan_master_id') border-red-500 @enderror">
            <option value="">（マスターなし）</option>
            @if(isset($masters))
                @foreach($masters as $master)
                    <option value="{{ $master->id }}" 
                        {{ old('contract_plan_master_id', optional($contractPlan)->contract_plan_master_id ?? ($selectedMasterId ?? '')) == $master->id ? 'selected' : '' }}>
                        {{ $master->name }}
                    </option>
                @endforeach
            @endif
        </select>
        @error('contract_plan_master_id')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
        <p class="text-xs text-gray-600 mt-1">
            <i class="fas fa-folder-open mr-1"></i>この製品が所属するマスターを選択してください
        </p>
    </div>

    {{-- 製品コード --}}
    <div>
        <label for="item" class="block text-sm font-semibold text-gray-700 mb-2">
            製品コード（ITEM） <span class="text-red-500">*</span>
        </label>
        <input type="text" name="item" id="item" 
            class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg theme-input font-mono @error('item') border-red-500 @enderror" 
            value="{{ old('item', optional($contractPlan)->item ?? '') }}" required placeholder="例: PLAN-050">
        @error('item')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
        <p class="text-xs text-gray-600 mt-1">
            <i class="fas fa-tag mr-1"></i>製品を一意に識別するコード。例: PLAN-050, PLAN-100, PLAN-150
        </p>
    </div>

    {{-- 製品名 --}}
    <div>
        <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
            製品名 <span class="text-red-500">*</span>
        </label>
        <input type="text" name="name" id="name" 
            class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg theme-input @error('name') border-red-500 @enderror" 
            value="{{ old('name', optional($contractPlan)->name ?? '') }}" required placeholder="例: 学習ページ数 50">
        @error('name')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
        <p class="text-xs text-gray-600 mt-1">
            <i class="fas fa-file-signature mr-1"></i>申込画面などに表示する製品名
        </p>
    </div>

    {{-- 料金 --}}
    <div>
        <label for="price" class="block text-sm font-semibold text-gray-700 mb-2">
            料金（税込） <span class="text-red-500">*</span>
        </label>
        <div class="relative">
            <input type="number" name="price" id="price" min="0" step="1"
                class="w-full px-4 py-2 pr-12 border-2 border-gray-300 rounded-lg theme-input @error('price') border-red-500 @enderror" 
                value="{{ old('price', optional($contractPlan)->price ?? '') }}" required placeholder="0">
            <span class="absolute right-4 top-2.5 text-gray-500 font-semibold" id="price-unit">円</span>
        </div>
        @error('price')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
        <p class="text-xs text-gray-600 mt-1">
            <i class="fas fa-yen-sign mr-1"></i>税込金額（整数）
        </p>
    </div>

    {{-- 決済タイプ（ベース製品・オプション製品の両方で表示） --}}
    <div id="billing-type-field">
        <label for="billing_type" class="block text-sm font-semibold text-gray-700 mb-2">
            決済タイプ <span class="text-red-500">*</span>
        </label>
        <select name="billing_type" id="billing_type" 
            class="native-select w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('billing_type') border-red-500 @enderror">
            <option value="one_time" {{ old('billing_type', optional($contractPlan)->billing_type ?? 'one_time') === 'one_time' ? 'selected' : '' }}>
                一回限り
            </option>
            <option value="monthly" {{ old('billing_type', optional($contractPlan)->billing_type ?? 'one_time') === 'monthly' ? 'selected' : '' }}>
                月額課金
            </option>
        </select>
        @error('billing_type')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
        <p class="text-xs text-gray-600 mt-1">
            <i class="fas fa-credit-card mr-1"></i>決済の種類を選択してください（一回限りまたは月額課金）
        </p>
    </div>

    {{-- 表示順 --}}
    <div>
        <label for="display_order" class="block text-sm font-semibold text-gray-700 mb-2">
            表示順 <span class="text-red-500">*</span>
        </label>
        <input type="number" name="display_order" id="display_order" min="0" step="1"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('display_order') border-red-500 @enderror" 
            value="{{ old('display_order', optional($contractPlan)->display_order ?? 0) }}" required>
        @error('display_order')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- 製品説明 --}}
    <div class="md:col-span-2">
        <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">
            製品説明
        </label>
        <textarea name="description" id="description" rows="4"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('description') border-red-500 @enderror">{{ old('description', optional($contractPlan)->description ?? '') }}</textarea>
        @error('description')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- オプション製品選択（ベース製品編集時のみ） --}}
    @if(isset($contractPlan))
    <div class="md:col-span-2">
        <label for="option_product_ids" class="block text-sm font-semibold text-gray-700 mb-2">
            紐づけるオプション製品
        </label>
        <div class="border border-gray-300 rounded-lg p-4 max-h-60 overflow-y-auto bg-gray-50">
            @if(isset($optionProducts) && $optionProducts->isNotEmpty())
                @foreach($optionProducts as $product)
                    <label class="flex items-center mb-2 cursor-pointer hover:bg-gray-100 p-2 rounded">
                        <input type="checkbox" 
                            name="option_product_ids[]" 
                            value="{{ $product->id }}"
                            class="mr-3 w-5 h-5 theme-checkbox-accent border-gray-300 rounded"
                            {{ in_array($product->id, old('option_product_ids', $linkedOptionProductIds ?? [])) ? 'checked' : '' }}>
                        <div class="flex-1">
                            <span class="font-semibold text-gray-800">{{ $product->name }}</span>
                            <span class="text-sm text-gray-600 ml-2">({{ $product->code }})</span>
                            <span class="text-sm text-gray-500 ml-2">
                                <span class="theme-price font-semibold">{{ $product->formatted_price }}</span>
                            </span>
                        </div>
                    </label>
                @endforeach
            @else
                <p class="text-sm text-gray-600">オプション製品が登録されていません。</p>
            @endif
        </div>
        @error('option_product_ids')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
        @error('option_product_ids.*')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
        <p class="text-xs text-gray-600 mt-1">
            <i class="fas fa-info-circle mr-1"></i>
            このベース製品に紐づけるオプション製品を選択してください。複数選択可能です。
        </p>
    </div>
    @endif

    {{-- ベース製品選択（オプション製品作成時のみ） --}}
    @if(!isset($contractPlan))
    <div class="md:col-span-2" id="base-plan-selection-field" style="display: none;">
        <label for="base_plan_ids" class="block text-sm font-semibold text-gray-700 mb-2">
            紐づけるベース製品 <span class="text-red-500">*</span>
        </label>
        <div class="border border-gray-300 rounded-lg p-4 max-h-60 overflow-y-auto bg-gray-50">
            @if(isset($basePlans) && $basePlans->isNotEmpty())
                @foreach($basePlans as $plan)
                    <label class="flex items-center mb-2 cursor-pointer hover:bg-gray-100 p-2 rounded">
                        <input type="checkbox" 
                            name="base_plan_ids[]" 
                            value="{{ $plan->id }}"
                            class="mr-3 w-5 h-5 theme-checkbox-accent border-gray-300 rounded"
                            {{ in_array($plan->id, old('base_plan_ids', [])) ? 'checked' : '' }}>
                        <div class="flex-1">
                            <span class="font-semibold text-gray-800">{{ $plan->name }}</span>
                            <span class="text-sm text-gray-600 ml-2">({{ $plan->item }})</span>
                            <span class="text-sm text-gray-500 ml-2">
                                @if($plan->billing_type === 'monthly')
                                    <span class="px-2 py-0.5 rounded text-xs theme-price" style="background-color: var(--color-primary-soft);">月額</span>
                                @else
                                    <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded text-xs">一回限り</span>
                                @endif
                            </span>
                        </div>
                    </label>
                @endforeach
            @else
                <p class="text-sm text-gray-600">ベース製品が登録されていません。先にベース製品を作成してください。</p>
            @endif
        </div>
        @error('base_plan_ids')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
        <p class="text-xs text-gray-600 mt-1">
            <i class="fas fa-info-circle mr-1"></i>
            このオプション製品をどのベース製品に紐づけるかを選択してください。複数選択可能です。
        </p>
    </div>
    @endif

    {{-- 有効フラグ --}}
    <div class="md:col-span-2">
        <label class="flex items-center">
            <input type="checkbox" name="is_active" value="1" 
                class="mr-2 w-5 h-5 theme-checkbox-accent border-gray-300 rounded"
                {{ old('is_active', optional($contractPlan)->is_active ?? true) ? 'checked' : '' }}>
            <span class="text-sm font-semibold text-gray-700">この製品を有効にする</span>
        </label>
    </div>
</div>

<script>
// 製品種別に応じてフィールドの表示/非表示を切り替え
document.addEventListener('DOMContentLoaded', function() {
    const productTypeSelect = document.getElementById('product_type');
    const contractPlanMasterField = document.getElementById('contract-plan-master-field');
    const billingTypeField = document.getElementById('billing-type-field');
    const billingTypeSelect = document.getElementById('billing_type');
    const priceUnit = document.getElementById('price-unit');
    
    function toggleFields() {
        if (!productTypeSelect) return;
        
        const isOption = productTypeSelect.value === 'option';
        const basePlanSelectionField = document.getElementById('base-plan-selection-field');
        
        // 契約プランマスターはオプション商品の場合は非表示
        if (contractPlanMasterField) {
            contractPlanMasterField.style.display = isOption ? 'none' : 'block';
        }
        // 決済タイプはベース・オプションの両方で表示（常に表示）
        if (billingTypeField) {
            billingTypeField.style.display = 'block';
        }
        if (billingTypeSelect) {
            billingTypeSelect.required = true;
        }
        
        // ベース商品選択はオプション商品の場合のみ表示
        if (basePlanSelectionField) {
            basePlanSelectionField.style.display = isOption ? 'block' : 'none';
        }
    }
    
    // 商品種別変更時にフィールドを切り替え
    if (productTypeSelect) {
        productTypeSelect.addEventListener('change', toggleFields);
        toggleFields(); // 初期状態を反映
    }
    
    // 決済タイプに応じて料金の単位表示を変更
    if (billingTypeSelect && priceUnit) {
        billingTypeSelect.addEventListener('change', function() {
            if (this.value === 'monthly') {
                priceUnit.textContent = '円/月';
            } else {
                priceUnit.textContent = '円';
            }
        });
        
        // 初期状態を反映
        if (billingTypeSelect.value === 'monthly') {
            priceUnit.textContent = '円/月';
        }
    }
});
</script>
