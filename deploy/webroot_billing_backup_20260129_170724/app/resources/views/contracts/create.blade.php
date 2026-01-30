@extends('layouts.public')

@section('title', '新規申込')

@section('content')
<div class="max-w-4xl mx-auto px-4 md:px-0">
    <div class="mb-6 md:mb-8 text-center">
        <h1 class="text-2xl md:text-4xl font-bold text-gray-800 mb-2">新規申込フォーム</h1>
        <p class="text-sm md:text-base text-gray-600">必要事項をご入力の上、お申し込みください</p>
    </div>

    @if(session('error'))
        <div class="bg-red-50 border-2 border-red-500 rounded-lg p-4 mb-6">
            <p class="text-red-600">
                <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            </p>
        </div>
    @endif

    <form action="{{ route('contract.confirm') }}" method="POST" class="space-y-6 md:space-y-8">
        @csrf

        {{-- 1. 申込企業情報 --}}
        <div class="bg-white shadow-lg rounded-lg p-4 md:p-6 mb-6 md:mb-8">
            <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-4 md:mb-6 pb-2 md:pb-3 border-b-2 border-indigo-500">
                <i class="fas fa-building mr-2"></i>1. 申込企業情報
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                {{-- 会社名 --}}
                <div class="md:col-span-2">
                    <label for="company_name" class="block text-sm font-semibold text-gray-700 mb-2">
                        会社名 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="company_name" id="company_name" 
                        class="w-full px-3 md:px-4 py-3 md:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-base @error('company_name') border-red-500 @enderror" 
                        value="{{ old('company_name') }}" required>
                    @error('company_name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 会社名（フリガナ） --}}
                <div class="md:col-span-2">
                    <label for="company_name_kana" class="block text-sm font-semibold text-gray-700 mb-2">
                        会社名（フリガナ）
                    </label>
                    <input type="text" name="company_name_kana" id="company_name_kana" 
                        class="w-full px-3 md:px-4 py-3 md:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-base @error('company_name_kana') border-red-500 @enderror" 
                        value="{{ old('company_name_kana') }}" placeholder="カブシキガイシャ サンプル">
                    @error('company_name_kana')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 部署名 --}}
                <div>
                    <label for="department" class="block text-sm font-semibold text-gray-700 mb-2">
                        部署名
                    </label>
                    <input type="text" name="department" id="department" 
                        class="w-full px-3 md:px-4 py-3 md:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-base" 
                        value="{{ old('department') }}">
                </div>

                {{-- 役職 --}}
                <div>
                    <label for="position" class="block text-sm font-semibold text-gray-700 mb-2">
                        役職
                    </label>
                    <input type="text" name="position" id="position" 
                        class="w-full px-3 md:px-4 py-3 md:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-base" 
                        value="{{ old('position') }}">
                </div>

                {{-- 担当者名 --}}
                <div>
                    <label for="contact_name" class="block text-sm font-semibold text-gray-700 mb-2">
                        担当者名 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="contact_name" id="contact_name" 
                        class="w-full px-3 md:px-4 py-3 md:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-base @error('contact_name') border-red-500 @enderror" 
                        value="{{ old('contact_name') }}" required>
                    @error('contact_name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 担当者名（フリガナ） --}}
                <div>
                    <label for="contact_name_kana" class="block text-sm font-semibold text-gray-700 mb-2">
                        担当者名（フリガナ）
                    </label>
                    <input type="text" name="contact_name_kana" id="contact_name_kana" 
                        class="w-full px-3 md:px-4 py-3 md:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-base @error('contact_name_kana') border-red-500 @enderror" 
                        value="{{ old('contact_name_kana') }}" placeholder="ヤマダ タロウ">
                    @error('contact_name_kana')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- メールアドレス --}}
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                        メールアドレス <span class="text-red-500">*</span>
                    </label>
                    <input type="email" name="email" id="email" 
                        class="w-full px-3 md:px-4 py-3 md:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-base @error('email') border-red-500 @enderror" 
                        value="{{ old('email') }}" inputmode="email" autocapitalize="none" autocorrect="off" spellcheck="false" style="ime-mode: disabled;" required>
                    @error('email')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 電話番号 --}}
                <div>
                    <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">
                        電話番号 <span class="text-red-500">*</span>
                    </label>
                    <input type="tel" name="phone" id="phone" 
                        class="w-full px-3 md:px-4 py-3 md:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-base @error('phone') border-red-500 @enderror" 
                        value="{{ old('phone') }}" placeholder="03-1234-5678" required>
                    @error('phone')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 郵便番号 --}}
                <div class="md:col-span-2">
                    <label for="postal_code" class="block text-sm font-semibold text-gray-700 mb-2">
                        郵便番号
                    </label>
                    <div class="flex gap-2">
                        <input type="text" name="postal_code" id="postal_code" maxlength="8"
                            class="flex-1 px-3 md:px-4 py-3 md:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-base @error('postal_code') border-red-500 @enderror" 
                            value="{{ old('postal_code') }}" placeholder="123-4567">
                        <button type="button" id="search_address_btn" 
                            class="px-3 md:px-4 py-3 md:py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition duration-300 text-sm md:text-base whitespace-nowrap">
                            <i class="fas fa-search mr-2"></i>検索
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">郵便番号を入力して「検索」ボタンをクリックすると、住所が自動入力されます</p>
                    <div id="address_error" class="hidden text-red-500 text-sm mt-1"></div>
                    <div id="address_loading" class="hidden text-blue-500 text-sm mt-1">
                        <i class="fas fa-spinner fa-spin mr-1"></i>検索中...
                    </div>
                    @error('postal_code')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 都道府県 --}}
                <div>
                    <label for="prefecture" class="block text-sm font-semibold text-gray-700 mb-2">
                        都道府県
                    </label>
                    <select name="prefecture" id="prefecture" 
                        class="w-full px-3 md:px-4 py-3 md:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-base">
                        <option value="">選択してください</option>
                        @foreach(['北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県', '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県', '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県', '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県', '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県', '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県', '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'] as $pref)
                            <option value="{{ $pref }}" {{ old('prefecture') == $pref ? 'selected' : '' }}>{{ $pref }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- 市区町村 --}}
                <div>
                    <label for="city" class="block text-sm font-semibold text-gray-700 mb-2">
                        市区町村
                    </label>
                    <input type="text" name="city" id="city" 
                        class="w-full px-3 md:px-4 py-3 md:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-base" 
                        value="{{ old('city') }}" placeholder="渋谷区">
                </div>

                {{-- 番地 --}}
                <div>
                    <label for="address_line1" class="block text-sm font-semibold text-gray-700 mb-2">
                        番地
                    </label>
                    <input type="text" name="address_line1" id="address_line1" 
                        class="w-full px-3 md:px-4 py-3 md:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-base" 
                        value="{{ old('address_line1') }}" placeholder="渋谷1-2-3">
                </div>

                {{-- 建物名 --}}
                <div>
                    <label for="address_line2" class="block text-sm font-semibold text-gray-700 mb-2">
                        建物名
                    </label>
                    <input type="text" name="address_line2" id="address_line2" 
                        class="w-full px-3 md:px-4 py-3 md:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-base" 
                        value="{{ old('address_line2') }}" placeholder="○○ビル 5F">
                </div>
            </div>
        </div>

        {{-- 2. ご利用情報 --}}
        <div class="bg-white shadow-lg rounded-lg p-4 md:p-6 mb-6 md:mb-8">
            <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-4 md:mb-6 pb-2 md:pb-3 border-b-2 border-indigo-500">
                <i class="fas fa-globe mr-2"></i>2. ご利用情報
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                {{-- ご利用URL・ドメイン --}}
                <div class="md:col-span-2">
                    <label for="usage_url_domain" class="block text-sm font-semibold text-gray-700 mb-2">
                        ご利用URL・ドメイン <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="usage_url_domain" id="usage_url_domain" 
                        class="w-full px-3 md:px-4 py-3 md:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-base @error('usage_url_domain') border-red-500 @enderror" 
                        value="{{ old('usage_url_domain') }}" placeholder="https://example.com または example.com" required>
                    @error('usage_url_domain')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 体験版からのインポートを希望する --}}
                <div class="md:col-span-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="import_from_trial" id="import_from_trial" value="1"
                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 @error('import_from_trial') border-red-500 @enderror"
                            {{ old('import_from_trial') ? 'checked' : '' }}>
                        <span class="ml-2 text-sm font-semibold text-gray-700">体験版からのインポートを希望する</span>
                    </label>
                    @error('import_from_trial')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- 3. 契約内容の選択 --}}
        <div class="bg-white shadow-lg rounded-lg p-4 md:p-6 mb-6 md:mb-8">
            <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-4 md:mb-6 pb-2 md:pb-3 border-b-2 border-indigo-500">
                <i class="fas fa-file-contract mr-2"></i>3. 契約内容の選択
            </h2>

            {{-- 製品選択 --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-4">
                    製品 <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4">
                    @foreach($plans as $plan)
                        <label class="relative cursor-pointer">
                            <input type="radio" name="contract_plan_id" value="{{ $plan->id }}" 
                                class="peer sr-only" 
                                {{ old('contract_plan_id') == $plan->id ? 'checked' : '' }} required>
                            <div class="border-2 border-gray-300 rounded-lg p-3 md:p-4 transition-all peer-checked:border-indigo-500 peer-checked:bg-indigo-50 hover:border-indigo-300">
                                <div class="text-center">
                                    <div class="text-base md:text-lg font-bold text-gray-800 mb-2">{{ $plan->name }}</div>
                                    <div class="text-2xl md:text-3xl font-bold text-indigo-600 mb-2">{{ $plan->formatted_price }}</div>
                                    <div class="text-xs md:text-sm text-gray-600">{{ $plan->description }}</div>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('contract_plan_id')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            {{-- オプション製品選択（動的に表示） --}}
            <div class="mb-6" id="option-products-section" style="display: none;">
                <label class="block text-sm font-semibold text-gray-700 mb-4">
                    オプション製品
                </label>
                <div id="option-products-container" class="space-y-3">
                    {{-- JavaScriptで動的に追加 --}}
                </div>
                <p id="option-products-empty" class="text-sm text-gray-500 mt-2" style="display: none;">
                    この製品に対応するオプション製品はありません。
                </p>
                @error('option_product_ids')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
                @error('option_product_ids.*')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
                <p class="text-xs text-gray-500 mt-2">必要なオプション製品にチェックを入れてください</p>
            </div>
        </div>

        {{-- 4. 利用規約 --}}
        @if($termsOfService)
        <div class="bg-white shadow-lg rounded-lg p-4 md:p-6 mb-6 md:mb-8">
            <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-4 md:mb-6 pb-2 md:pb-3 border-b-2 border-indigo-500">
                <i class="fas fa-file-contract mr-2"></i>4. 利用規約
            </h2>

            {{-- 利用規約の表示 --}}
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 md:p-4 mb-4 md:mb-6" style="max-height: 300px; overflow-y: auto;">
                <div class="quill-content text-gray-700 text-xs md:text-sm">
                    {!! $termsOfService !!}
                </div>
            </div>

            {{-- 同意チェックボックス --}}
            <div class="flex items-center justify-center flex-wrap">
                <input type="checkbox" name="terms_agreed" id="terms_agreed" value="1"
                    class="mr-2 md:mr-3 w-5 h-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded @error('terms_agreed') border-red-500 @enderror"
                    {{ old('terms_agreed') ? 'checked' : '' }} required>
                <label for="terms_agreed" class="text-gray-700 text-sm md:text-base">
                    <span class="text-red-500">*</span>
                    <span class="font-semibold">利用規約に同意します</span>
                </label>
            </div>
            @error('terms_agreed')
                <p class="text-red-500 text-sm mt-2 text-center">{{ $message }}</p>
            @enderror
        </div>
        @endif

        {{-- 送信ボタン --}}
        <div class="flex flex-col sm:flex-row justify-center gap-3 md:gap-4 mb-6 md:mb-8">
            <a href="{{ url('/') }}" class="px-6 md:px-8 py-3 md:py-3 bg-gray-500 hover:bg-gray-600 text-white font-bold rounded-lg shadow-md transition duration-300 text-center text-base">
                キャンセル
            </a>
            <button type="submit" class="px-6 md:px-8 py-3 md:py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-md transition duration-300 text-base">
                確認画面へ <i class="fas fa-arrow-right ml-2"></i>
            </button>
        </div>
    </form>
</div>

<script>
// オプション製品の動的表示
(function() {
    const planRadios = document.querySelectorAll('input[name="contract_plan_id"]');
    const optionProductsSection = document.getElementById('option-products-section');
    const optionProductsContainer = document.getElementById('option-products-container');
    const optionProductsEmpty = document.getElementById('option-products-empty');
    
    if (!planRadios.length || !optionProductsSection) return;
    
    // 製品選択時の処理
    planRadios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            if (this.checked) {
                loadOptionProducts(this.value);
            }
        });
        
        // 初期状態で選択されている場合はオプション製品を読み込む
        if (radio.checked) {
            loadOptionProducts(radio.value);
        }
    });
    
    // オプション製品を取得して表示
    function loadOptionProducts(contractPlanId) {
        if (!contractPlanId) {
            optionProductsSection.style.display = 'none';
            return;
        }
        
        // ローディング表示
        optionProductsContainer.innerHTML = '<p class="text-sm text-gray-500">読み込み中...</p>';
        optionProductsSection.style.display = 'block';
        optionProductsEmpty.style.display = 'none';
        
        // APIからオプション製品を取得
        // URLを動的に構築（url()ヘルパーを使用してベースパスを取得）
        const apiBaseUrlTemplate = '{{ url("/contract/api/option-products") }}';
        // 絶対URLからパス部分のみを取得（ドメイン部分を除く）
        const apiBaseUrl = apiBaseUrlTemplate.replace(window.location.origin, '');
        const apiPath = apiBaseUrl + '/' + contractPlanId;
        
        console.log('オプション製品取得API呼び出し:', {
            apiBaseUrlTemplate: apiBaseUrlTemplate,
            apiBaseUrl: apiBaseUrl,
            apiPath: apiPath,
            contractPlanId: contractPlanId,
            fullUrl: window.location.origin + apiPath,
            currentPath: window.location.pathname
        });
        
        fetch(apiPath, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
            },
        })
        .then(response => {
            console.log('APIレスポンス:', response.status, response.statusText, response.url);
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('APIエラーレスポンス:', text);
                    let errorData;
                    try {
                        errorData = JSON.parse(text);
                    } catch (e) {
                        errorData = { message: 'HTTP error! status: ' + response.status };
                    }
                    throw new Error(errorData.message || 'HTTP error! status: ' + response.status);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('APIレスポンスデータ:', data);
            if (data.success && data.option_products && data.option_products.length > 0) {
                console.log('オプション製品数:', data.option_products.length);
                // オプション製品を表示
                optionProductsContainer.innerHTML = '';
                data.option_products.forEach(function(product) {
                    console.log('オプション製品:', product);
                    const label = document.createElement('label');
                    label.className = 'flex items-start cursor-pointer border border-gray-300 rounded-lg p-4 hover:bg-gray-50 hover:border-indigo-300 transition';
                    
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.name = 'option_product_ids[]';
                    checkbox.value = product.id;
                    checkbox.className = 'mt-1 mr-3 w-5 h-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded';
                    
                    // old()の値を復元
                    const oldValues = @json(old('option_product_ids', []));
                    if (oldValues.includes(parseInt(product.id))) {
                        checkbox.checked = true;
                    }
                    
                    const div = document.createElement('div');
                    div.className = 'flex-1';
                    div.innerHTML = `
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-semibold text-gray-800">${escapeHtml(product.name)}</p>
                                ${product.description ? `<p class="text-sm text-gray-600 mt-1">${escapeHtml(product.description)}</p>` : ''}
                            </div>
                            <div class="text-right ml-4">
                                <p class="text-lg font-bold text-indigo-600">${formatNumber(product.unit_price)}円</p>
                            </div>
                        </div>
                    `;
                    
                    label.appendChild(checkbox);
                    label.appendChild(div);
                    optionProductsContainer.appendChild(label);
                });
                optionProductsEmpty.style.display = 'none';
            } else {
                // オプション製品がない場合
                optionProductsContainer.innerHTML = '';
                optionProductsEmpty.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('オプション製品の取得に失敗しました:', error);
            console.error('エラー詳細:', error.message, error.stack);
            optionProductsContainer.innerHTML = '<p class="text-sm text-red-500">オプション製品の取得に失敗しました: ' + escapeHtml(error.message) + '。ページを再読み込みしてください。</p>';
            optionProductsEmpty.style.display = 'none';
        });
    }
    
    // HTMLエスケープ関数
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // 数値フォーマット関数
    function formatNumber(num) {
        return new Intl.NumberFormat('ja-JP').format(num);
    }
})();

    // 会社名から会社名（フリガナ）への自動入力（カタカナ変換）
    (function() {
        const nameInput = document.getElementById('company_name');
        const kanaInput = document.getElementById('company_name_kana');
        
        if (!nameInput || !kanaInput) {
            return;
        }
        
        // ひらがなをカタカナに変換する関数
        const hiraToKata = (s) => {
            return s.replace(/[\u3041-\u3096]/g, (ch) => {
                return String.fromCharCode(ch.charCodeAt(0) + 0x60);
            });
        };
        
        // カタカナ正規化（ひらがな→カタカナ、半角長音記号→全角長音記号）
        const normalizeKana = (s) => {
            if (!s) return '';
            // ひらがなをカタカナに変換
            let result = hiraToKata(s);
            // 半角長音記号（ｰ）を全角長音記号（ー）に変換
            result = result.replace(/\uFF70/g, '\u30FC');
            return result;
        };
        
        // 状態管理
        let composing = false;
        let lastComposition = '';
        let accumulatedKana = ''; // 確定済みのカタカナを蓄積
        let kanaTouched = false; // ユーザーが手動でフリガナを編集したかどうか
        let compositionStartValue = ''; // compositionstart時の値を記録
        
        // フリガナフィールドの手動編集を検出
        // 注意: このイベントは、kanaInputの直接入力イベント（後述）の前に実行される
        // 直接入力の場合は、後続のイベントでaccumulatedKanaが更新されるため、
        // ここではkanaTouchedを設定しない（直接入力と自動反映を区別できないため）
        kanaInput.addEventListener('input', function() {
            // このイベントではkanaTouchedを設定しない
            // 直接入力の場合は、後続のイベントでaccumulatedKanaが更新される
        });
        
        // IME入力開始
        nameInput.addEventListener('compositionstart', function() {
            composing = true;
            lastComposition = '';
            compositionStartValue = nameInput.value; // 開始時の値を記録
        });
        
        // IME入力中（変換前の読み仮名を取得）
        nameInput.addEventListener('compositionupdate', function(e) {
            if (kanaTouched) {
                return; // 手動編集後は自動反映しない
            }
            
            // e.dataが利用可能な場合はそれを使用（未確定文字列）
            // ただし、e.dataが漢字を含んでいる場合は無視（漢字変換候補表示時）
            let data = '';
            let useData = false;
            if (e.data !== undefined && e.data !== null && e.data !== '') {
                // e.dataがひらがなのみかチェック（漢字が含まれていないか）
                const hasKanji = /[\u4E00-\u9FAF]/.test(e.data);
                if (!hasKanji) {
                    data = e.data;
                    useData = true;
                }
            }
            
            if (useData && data) {
                lastComposition = data;
                const kana = normalizeKana(data);
                if (kana) {
                    // 確定済みのカタカナ + 現在入力中のカタカナ
                    kanaInput.value = accumulatedKana + kana;
                }
            } else {
                // e.dataが利用できない場合のフォールバック
                // compositionstart時の値と比較して新規入力部分を抽出
                const currentValue = e.target.value;
                if (currentValue.length > compositionStartValue.length) {
                    // 新規入力部分を抽出
                    const newPart = currentValue.slice(compositionStartValue.length);
                    // 新規部分からひらがなと長音記号を抽出
                    const hiraganaMatch = newPart.match(/[\u3041-\u3096\u30FC\uFF70]+/);
                    if (hiraganaMatch) {
                        const hiragana = hiraganaMatch[0];
                        const kana = normalizeKana(hiragana);
                        if (kana) {
                            lastComposition = hiragana;
                            kanaInput.value = accumulatedKana + kana;
                        }
                    }
                } else {
                    // 値が減少した場合や置換が発生した場合
                    // currentValueからひらがな部分を抽出（最後の手段）
                    const hiraganaMatch = currentValue.match(/[\u3041-\u3096\u30FC\uFF70]+/g);
                    if (hiraganaMatch) {
                        // compositionstart時の値から既に確定された部分を除く
                        const startHiragana = compositionStartValue.match(/[\u3041-\u3096\u30FC\uFF70]+/g);
                        const startHiraganaStr = startHiragana ? startHiragana.join('') : '';
                        const allHiragana = hiraganaMatch.join('');
                        if (allHiragana.length > startHiraganaStr.length) {
                            const newHiragana = allHiragana.slice(startHiraganaStr.length);
                            const kana = normalizeKana(newHiragana);
                            if (kana) {
                                lastComposition = newHiragana;
                                kanaInput.value = accumulatedKana + kana;
                            }
                        }
                    }
                }
            }
        });
        
        // IME入力終了（変換確定）
        nameInput.addEventListener('compositionend', function() {
            composing = false;
            // 確定文字（漢字）では kanaInput を更新しない
            // lastComposition をカタカナ化して蓄積に追加
            // ただし、lastCompositionが漢字を含んでいる場合は無視（漢字変換確定時）
            if (lastComposition && !kanaTouched) {
                // lastCompositionが漢字を含んでいるかチェック
                const hasKanji = /[\u4E00-\u9FAF]/.test(lastComposition);
                if (!hasKanji) {
                    const kana = normalizeKana(lastComposition);
                    if (kana) {
                        accumulatedKana += kana;
                        kanaInput.value = accumulatedKana;
                    }
                }
            }
            lastComposition = '';
        });
        
        // 通常の入力時（IME変換後は処理しない）
        nameInput.addEventListener('input', function(e) {
            if (kanaTouched) return; // 手動編集後は自動反映しない
            
            // IME変換中だけ kana を更新（確定後は更新しない）
            if (e.isComposing || composing) {
                // input中に value 全体を取ると漢字が混ざる場合があるので
                // lastComposition を優先し、無ければ nameInput.value を控えめに使う
                const base = lastComposition || '';
                const kana = normalizeKana(base);
                if (kana) {
                    kanaInput.value = accumulatedKana + kana;
                }
            } else {
                // IME変換確定後（isComposing === false）は kana を更新しない
                // 漢字が入らないように、蓄積されたカタカナを保持
                // カタカナ・スペース・数字（半角・全角）は許可。会社名に数字を含む場合があるため。
                if (accumulatedKana && kanaInput.value !== accumulatedKana) {
                    const currentKana = kanaInput.value;
                    const cleanKana = currentKana.replace(/[^\u30A1-\u30F6\u30FC\u0020\u30000-9\uFF10-\uFF19]/g, '');
                    if (currentKana !== cleanKana) {
                        kanaInput.value = accumulatedKana;
                    }
                }
                // 会社名に末尾の数字があればフリガナに付与（会社名に数字を含む場合の自動反映）
                const trail = nameInput.value.match(/[0-9０-９]+$/);
                if (trail && !kanaTouched) {
                    kanaInput.value = kanaInput.value.replace(/[0-9０-９]+$/g, '') + trail[0];
                }
            }
        });
        
        // 会社名フィールドがクリアされた場合の処理
        nameInput.addEventListener('focus', function() {
            if (!nameInput.value) {
                accumulatedKana = '';
                if (!kanaTouched) {
                    kanaInput.value = '';
                }
            }
        });
            
        // フリガナフィールド自体への直接入力を制限（カタカナのみ）
        let kanaFieldLastValue = ''; // フリガナフィールドの前回の値を記録
        
        // フリガナフィールドのIME入力開始
        kanaInput.addEventListener('compositionstart', function(e) {
            kanaFieldLastValue = e.target.value;
        });
        
        // フリガナフィールドのIME入力中
        // 直接入力の場合はそのまま入れるため、何も処理しない
        kanaInput.addEventListener('compositionupdate', function(e) {
            // 直接入力の場合はそのまま入れる（入力補助ではない）
        });
        
        // フリガナフィールドのIME入力終了（変換確定）
        // 直接入力の場合はそのまま入れるため、何も処理しない
        kanaInput.addEventListener('compositionend', function(e) {
            // 直接入力の場合はそのまま入れる（入力補助ではない）
            // 状態を更新（会社名からの自動反映機能で使用）
            accumulatedKana = e.target.value;
            kanaFieldLastValue = e.target.value;
            kanaTouched = false;
        });
        
        // フリガナフィールドの通常入力
        // 直接入力の場合はそのまま入れるため、何も処理しない
        kanaInput.addEventListener('input', function(e) {
            // 直接入力の場合はそのまま入れる（入力補助ではない）
            // 状態を更新（会社名からの自動反映機能で使用）
            accumulatedKana = e.target.value;
            kanaFieldLastValue = e.target.value;
            kanaTouched = false;
        });
    })();

    // 担当者名から担当者名（フリガナ）への自動入力（カタカナ変換）
    (function() {
        const contactNameInput = document.getElementById('contact_name');
        const contactKanaInput = document.getElementById('contact_name_kana');
        
        if (!contactNameInput || !contactKanaInput) {
            return;
        }
        
        // ひらがなをカタカナに変換する関数
        const hiraToKata = (s) => {
            return s.replace(/[\u3041-\u3096]/g, (ch) => {
                return String.fromCharCode(ch.charCodeAt(0) + 0x60);
            });
        };
        
        // カタカナ正規化（ひらがな→カタカナ、半角長音記号→全角長音記号）
        const normalizeKana = (s) => {
            if (!s) return '';
            // ひらがなをカタカナに変換
            let result = hiraToKata(s);
            // 半角長音記号（ｰ）を全角長音記号（ー）に変換
            result = result.replace(/\uFF70/g, '\u30FC');
            return result;
        };
        
        // 状態管理
        let composing = false;
        let lastComposition = '';
        let accumulatedKana = ''; // 確定済みのカタカナを蓄積
        let kanaTouched = false; // ユーザーが手動でフリガナを編集したかどうか
        let compositionStartValue = ''; // compositionstart時の値を記録
        
        // フリガナフィールドの手動編集を検出
        contactKanaInput.addEventListener('input', function() {
            // このイベントではkanaTouchedを設定しない
            // 直接入力の場合は、後続のイベントでaccumulatedKanaが更新される
        });
        
        // IME入力開始
        contactNameInput.addEventListener('compositionstart', function() {
            composing = true;
            lastComposition = '';
            compositionStartValue = contactNameInput.value; // 開始時の値を記録
        });
        
        // IME入力中（変換前の読み仮名を取得）
        contactNameInput.addEventListener('compositionupdate', function(e) {
            if (kanaTouched) {
                return; // 手動編集後は自動反映しない
            }
            
            // e.dataが利用可能な場合はそれを使用（未確定文字列）
            // ただし、e.dataが漢字を含んでいる場合は無視（漢字変換候補表示時）
            let data = '';
            let useData = false;
            if (e.data !== undefined && e.data !== null && e.data !== '') {
                // e.dataがひらがなのみかチェック（漢字が含まれていないか）
                const hasKanji = /[\u4E00-\u9FAF]/.test(e.data);
                if (!hasKanji) {
                    data = e.data;
                    useData = true;
                }
            }
            
            if (useData && data) {
                lastComposition = data;
                const kana = normalizeKana(data);
                if (kana) {
                    // 確定済みのカタカナ + 現在入力中のカタカナ
                    contactKanaInput.value = accumulatedKana + kana;
                }
            } else {
                // e.dataが利用できない場合のフォールバック
                // compositionstart時の値と比較して新規入力部分を抽出
                const currentValue = e.target.value;
                if (currentValue.length > compositionStartValue.length) {
                    // 新規入力部分を抽出
                    const newPart = currentValue.slice(compositionStartValue.length);
                    // 新規部分からひらがなと長音記号を抽出
                    const hiraganaMatch = newPart.match(/[\u3041-\u3096\u30FC\uFF70]+/);
                    if (hiraganaMatch) {
                        const hiragana = hiraganaMatch[0];
                        const kana = normalizeKana(hiragana);
                        if (kana) {
                            lastComposition = hiragana;
                            contactKanaInput.value = accumulatedKana + kana;
                        }
                    }
                } else {
                    // 値が減少した場合や置換が発生した場合
                    // currentValueからひらがな部分を抽出（最後の手段）
                    const hiraganaMatch = currentValue.match(/[\u3041-\u3096\u30FC\uFF70]+/g);
                    if (hiraganaMatch) {
                        // compositionstart時の値から既に確定された部分を除く
                        const startHiragana = compositionStartValue.match(/[\u3041-\u3096\u30FC\uFF70]+/g);
                        const startHiraganaStr = startHiragana ? startHiragana.join('') : '';
                        const allHiragana = hiraganaMatch.join('');
                        if (allHiragana.length > startHiraganaStr.length) {
                            const newHiragana = allHiragana.slice(startHiraganaStr.length);
                            const kana = normalizeKana(newHiragana);
                            if (kana) {
                                lastComposition = newHiragana;
                                contactKanaInput.value = accumulatedKana + kana;
                            }
                        }
                    }
                }
            }
        });
        
        // IME入力終了（変換確定）
        contactNameInput.addEventListener('compositionend', function() {
            composing = false;
            // 確定文字（漢字）では contactKanaInput を更新しない
            // lastComposition をカタカナ化して蓄積に追加
            // ただし、lastCompositionが漢字を含んでいる場合は無視（漢字変換確定時）
            if (lastComposition && !kanaTouched) {
                // lastCompositionが漢字を含んでいるかチェック
                const hasKanji = /[\u4E00-\u9FAF]/.test(lastComposition);
                if (!hasKanji) {
                    const kana = normalizeKana(lastComposition);
                    if (kana) {
                        accumulatedKana += kana;
                        contactKanaInput.value = accumulatedKana;
                    }
                }
            }
            lastComposition = '';
        });
        
        // 通常の入力時（IME変換後は処理しない）
        contactNameInput.addEventListener('input', function(e) {
            if (kanaTouched) return; // 手動編集後は自動反映しない
            
            // IME変換中だけ kana を更新（確定後は更新しない）
            if (e.isComposing || composing) {
                // input中に value 全体を取ると漢字が混ざる場合があるので
                // lastComposition を優先し、無ければ contactNameInput.value を控えめに使う
                const base = lastComposition || '';
                const kana = normalizeKana(base);
                if (kana) {
                    contactKanaInput.value = accumulatedKana + kana;
                }
            } else {
                // IME変換確定後（isComposing === false）は kana を更新しない
                // 漢字が入らないように、蓄積されたカタカナを保持
                if (accumulatedKana && contactKanaInput.value !== accumulatedKana) {
                    // フリガナフィールドにカタカナ以外が入っていた場合は、蓄積された値を復元
                    const currentKana = contactKanaInput.value;
                    const cleanKana = currentKana.replace(/[^\u30A1-\u30F6\u30FC\u0020\u3000]/g, '');
                    if (currentKana !== cleanKana || /[^\u30A1-\u30F6\u30FC\u0020\u3000]/.test(currentKana)) {
                        contactKanaInput.value = accumulatedKana;
                    }
                }
            }
        });
        
        // 担当者名フィールドがクリアされた場合の処理
        contactNameInput.addEventListener('focus', function() {
            if (!contactNameInput.value) {
                accumulatedKana = '';
                if (!kanaTouched) {
                    contactKanaInput.value = '';
                }
            }
        });
            
        // フリガナフィールド自体への直接入力を制限（カタカナのみ）
        let kanaFieldLastValue = ''; // フリガナフィールドの前回の値を記録
        
        // フリガナフィールドのIME入力開始
        contactKanaInput.addEventListener('compositionstart', function(e) {
            kanaFieldLastValue = e.target.value;
        });
        
        // フリガナフィールドのIME入力中
        // 直接入力の場合はそのまま入れるため、何も処理しない
        contactKanaInput.addEventListener('compositionupdate', function(e) {
            // 直接入力の場合はそのまま入れる（入力補助ではない）
        });
        
        // フリガナフィールドのIME入力終了（変換確定）
        // 直接入力の場合はそのまま入れるため、何も処理しない
        contactKanaInput.addEventListener('compositionend', function(e) {
            // 直接入力の場合はそのまま入れる（入力補助ではない）
            // 状態を更新（担当者名からの自動反映機能で使用）
            accumulatedKana = e.target.value;
            kanaFieldLastValue = e.target.value;
            kanaTouched = false;
        });
        
        // フリガナフィールドの通常入力
        // 直接入力の場合はそのまま入れるため、何も処理しない
        contactKanaInput.addEventListener('input', function(e) {
            // 直接入力の場合はそのまま入れる（入力補助ではない）
            // 状態を更新（担当者名からの自動反映機能で使用）
            accumulatedKana = e.target.value;
            kanaFieldLastValue = e.target.value;
            kanaTouched = false;
        });
    })();

    // 電話番号の自動ハイフン挿入と全角数字→半角数字変換
    (function() {
        const phoneInput = document.getElementById('phone');
        if (phoneInput) {
            // 全角数字を半角数字に変換する関数
            function toHalfWidthNumber(str) {
                return str.replace(/[０-９]/g, function(s) {
                    return String.fromCharCode(s.charCodeAt(0) - 0xFEE0);
                });
            }
            
            phoneInput.addEventListener('input', function(e) {
                // 全角数字を半角数字に変換
                let value = toHalfWidthNumber(e.target.value);
                // 数字とハイフンのみを残す
                value = value.replace(/[^\d-]/g, '');
                
                // ハイフンを一旦削除してから再フォーマット
                const digits = value.replace(/-/g, '');
                
                // 電話番号のフォーマット（携帯電話: 11桁、固定電話: 10桁）
                if (digits.length <= 3) {
                    value = digits;
                } else if (digits.length <= 7) {
                    // 市外局番-市内局番
                    value = digits.slice(0, 3) + '-' + digits.slice(3);
                } else if (digits.length <= 10) {
                    // 固定電話: 03-1234-5678
                    value = digits.slice(0, 2) + '-' + digits.slice(2, 6) + '-' + digits.slice(6);
                } else if (digits.length <= 11) {
                    // 携帯電話: 090-1234-5678
                    value = digits.slice(0, 3) + '-' + digits.slice(3, 7) + '-' + digits.slice(7);
                } else {
                    // 11桁を超える場合は切り詰め
                    value = digits.slice(0, 3) + '-' + digits.slice(3, 7) + '-' + digits.slice(7, 11);
                }
                
                e.target.value = value;
            });
            
            // フォーカスアウト時にもフォーマット
            phoneInput.addEventListener('blur', function(e) {
                // 全角数字を半角数字に変換
                let value = toHalfWidthNumber(e.target.value);
                // 数字とハイフンのみを残す
                value = value.replace(/[^\d-]/g, '');
                const digits = value.replace(/-/g, '');
                
                if (digits.length > 0 && digits.length < 10) {
                    // 10桁未満の場合は固定電話フォーマットを試行
                    if (digits.length <= 3) {
                        value = digits;
                    } else if (digits.length <= 7) {
                        value = digits.slice(0, 3) + '-' + digits.slice(3);
                    } else {
                        value = digits.slice(0, 2) + '-' + digits.slice(2, 6) + '-' + digits.slice(6);
                    }
                    e.target.value = value;
                }
            });
        }
    })();

    // メールアドレスの全角→半角変換とIME無効化（デスクトップ・モバイル対応）
    (function() {
        const emailInput = document.getElementById('email');
        if (emailInput) {
            // IMEを無効化する関数（デスクトップブラウザ向け）
            function disableIME(input) {
                // CSSでIMEを無効化（非標準だが一部のデスクトップブラウザで動作）
                input.style.setProperty('ime-mode', 'disabled', 'important');
                // 属性でも設定（一部のブラウザで動作）
                input.setAttribute('ime-mode', 'disabled');
            }
            
            // モバイルデバイスかどうかを判定
            const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
            
            // フォーカス時にIMEを無効化（デスクトップブラウザ向け）
            // モバイルでは inputmode="email" により適切なキーボードが自動表示される
            emailInput.addEventListener('focus', function(e) {
                if (!isMobile) {
                    disableIME(e.target);
                }
                // モバイルでも念のため属性を設定（一部のAndroidブラウザで動作する可能性）
                e.target.setAttribute('ime-mode', 'disabled');
            });
            
            // 全角文字を半角に変換する関数
            function toHalfWidth(str) {
                return str.replace(/[Ａ-Ｚａ-ｚ０-９＠．]/g, function(s) {
                    return String.fromCharCode(s.charCodeAt(0) - 0xFEE0);
                });
            }
            
            emailInput.addEventListener('input', function(e) {
                const originalValue = e.target.value;
                const halfWidthValue = toHalfWidth(originalValue);
                
                // カーソル位置を保持
                const cursorPosition = e.target.selectionStart;
                
                if (originalValue !== halfWidthValue) {
                    e.target.value = halfWidthValue;
                    // カーソル位置を調整（変換された文字数分）
                    const diff = originalValue.length - halfWidthValue.length;
                    e.target.setSelectionRange(cursorPosition - diff, cursorPosition - diff);
                }
            });
            
            // フォーカスアウト時にも変換
            emailInput.addEventListener('blur', function(e) {
                const halfWidthValue = toHalfWidth(e.target.value);
                if (e.target.value !== halfWidthValue) {
                    e.target.value = halfWidthValue;
                }
            });
        }
    })();

    // 郵便番号から住所を自動入力
    (function() {
        const postalCodeInput = document.getElementById('postal_code');
        const searchBtn = document.getElementById('search_address_btn');
        const prefectureSelect = document.getElementById('prefecture');
        const cityInput = document.getElementById('city');
        const addressLine1Input = document.getElementById('address_line1');
        const errorDiv = document.getElementById('address_error');
        const loadingDiv = document.getElementById('address_loading');

        // 全角数字を半角数字に変換する関数
        function toHalfWidthNumber(str) {
            return str.replace(/[０-９]/g, function(s) {
                return String.fromCharCode(s.charCodeAt(0) - 0xFEE0);
            });
        }

        // 郵便番号を正規化（全角数字→半角数字変換、ハイフンを除去）
        function normalizePostalCode(postalCode) {
            // 全角数字を半角数字に変換
            let normalized = toHalfWidthNumber(postalCode);
            // ハイフン記号を統一
            normalized = normalized.replace(/[ー−‐]/g, '-');
            // 数字とハイフンのみを残す
            return normalized.replace(/[^\d-]/g, '');
        }

        // 住所検索関数
        async function searchAddress() {
            const postalCode = normalizePostalCode(postalCodeInput.value);
            const zipcode = postalCode.replace(/-/g, '');

            // 郵便番号のバリデーション（7桁の数字）
            if (!/^\d{7}$/.test(zipcode)) {
                errorDiv.textContent = '郵便番号は7桁の数字で入力してください（例: 123-4567）';
                errorDiv.classList.remove('hidden');
                loadingDiv.classList.add('hidden');
                return;
            }

            // UI更新
            errorDiv.classList.add('hidden');
            loadingDiv.classList.remove('hidden');
            searchBtn.disabled = true;

            try {
                // zipcloud APIを呼び出し
                const response = await fetch(`https://zipcloud.ibsnet.co.jp/api/search?zipcode=${zipcode}`);
                const data = await response.json();

                loadingDiv.classList.add('hidden');
                searchBtn.disabled = false;

                if (data.status !== 200 || !data.results || data.results.length === 0) {
                    errorDiv.textContent = '郵便番号が見つかりませんでした。正しい郵便番号を入力してください。';
                    errorDiv.classList.remove('hidden');
                    return;
                }

                const result = data.results[0];
                
                // 都道府県を設定
                if (prefectureSelect) {
                    const prefecture = result.address1; // 都道府県
                    for (let option of prefectureSelect.options) {
                        if (option.value === prefecture) {
                            prefectureSelect.value = prefecture;
                            break;
                        }
                    }
                }

                // 市区町村を設定
                if (cityInput) {
                    cityInput.value = result.address2 || ''; // 市区町村
                }

                // 町域名を設定（番地フィールドに）
                if (addressLine1Input) {
                    addressLine1Input.value = result.address3 || ''; // 町域名
                }

                // 郵便番号にハイフンを追加（表示用）
                if (postalCodeInput.value.replace(/-/g, '').length === 7 && !postalCodeInput.value.includes('-')) {
                    postalCodeInput.value = zipcode.slice(0, 3) + '-' + zipcode.slice(3);
                }

                // 成功メッセージを表示（オプション）
                errorDiv.textContent = '';
                errorDiv.classList.add('hidden');

            } catch (error) {
                console.error('住所検索エラー:', error);
                loadingDiv.classList.add('hidden');
                searchBtn.disabled = false;
                errorDiv.textContent = '住所の取得に失敗しました。しばらくしてから再度お試しください。';
                errorDiv.classList.remove('hidden');
            }
        }

        // 検索ボタンクリック時
        if (searchBtn) {
            searchBtn.addEventListener('click', searchAddress);
        }

        // Enterキーで検索
        if (postalCodeInput) {
            postalCodeInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchAddress();
                }
            });

            // 郵便番号入力時の自動フォーマット（全角数字→半角数字変換、ハイフン自動挿入）
            postalCodeInput.addEventListener('input', function(e) {
                // 全角数字を半角数字に変換
                let value = toHalfWidthNumber(e.target.value);
                // 数字のみを残す
                value = value.replace(/[^\d]/g, '');
                // ハイフン自動挿入
                if (value.length > 3) {
                    value = value.slice(0, 3) + '-' + value.slice(3, 7);
                }
                if (value.length <= 8) {
                    e.target.value = value;
                } else {
                    e.target.value = value.slice(0, 8);
                }
            });
        }
    })();

    // ご利用URL・ドメインの全角英数字→半角英数字変換
    (function() {
        const usageUrlDomainInput = document.getElementById('usage_url_domain');
        
        // 全角英数字を半角英数字に変換する関数
        function toHalfWidthAlphanumeric(str) {
            return str.replace(/[Ａ-Ｚａ-ｚ０-９]/g, function(s) {
                return String.fromCharCode(s.charCodeAt(0) - 0xFEE0);
            });
        }
        
        if (usageUrlDomainInput) {
            usageUrlDomainInput.addEventListener('input', function(e) {
                // 全角英数字を半角英数字に変換
                e.target.value = toHalfWidthAlphanumeric(e.target.value);
            });
            
            usageUrlDomainInput.addEventListener('blur', function(e) {
                // 全角英数字を半角英数字に変換
                e.target.value = toHalfWidthAlphanumeric(e.target.value);
            });
        }
    })();
</script>
@endsection

@push('styles')
<!-- Quill Editor CSS for displaying content -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<style>
.quill-content {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
}
.quill-content p {
    margin-bottom: 1em;
    line-height: 1.6;
}
.quill-content h1,
.quill-content h2,
.quill-content h3,
.quill-content h4,
.quill-content h5,
.quill-content h6 {
    font-weight: bold;
    margin-top: 1.5em;
    margin-bottom: 0.5em;
    line-height: 1.3;
}
.quill-content h1 { font-size: 2em; }
.quill-content h2 { font-size: 1.5em; }
.quill-content h3 { font-size: 1.25em; }
.quill-content h4 { font-size: 1.1em; }
.quill-content h5 { font-size: 1em; }
.quill-content h6 { font-size: 0.9em; }
.quill-content ul,
.quill-content ol {
    margin-left: 1.5em;
    margin-bottom: 1em;
    padding-left: 1.5em;
}
.quill-content li {
    margin-bottom: 0.5em;
}
.quill-content strong {
    font-weight: bold;
}
.quill-content em {
    font-style: italic;
}
.quill-content u {
    text-decoration: underline;
}
.quill-content s {
    text-decoration: line-through;
}
.quill-content[class*="ql-align-center"],
.quill-content .ql-align-center {
    text-align: center;
}
.quill-content[class*="ql-align-right"],
.quill-content .ql-align-right {
    text-align: right;
}
.quill-content[class*="ql-align-justify"],
.quill-content .ql-align-justify {
    text-align: justify;
}
</style>
@endpush

