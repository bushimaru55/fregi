@extends('layouts.public')

@section('title', '新規申込')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-8 text-center">
        <h1 class="text-4xl font-bold text-gray-800 mb-2">新規申込フォーム</h1>
        <p class="text-gray-600">必要事項をご入力の上、お申し込みください</p>
    </div>

    @if(session('error'))
        <div class="bg-red-50 border-2 border-red-500 rounded-lg p-4 mb-6">
            <p class="text-red-600">
                <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            </p>
        </div>
    @endif

    <form action="{{ route('contract.confirm') }}" method="POST" class="space-y-8">
        @csrf

        {{-- 1. 申込企業情報 --}}
        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-3 border-b-2 border-indigo-500">
                <i class="fas fa-building mr-2"></i>1. 申込企業情報
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- 会社名 --}}
                <div class="md:col-span-2">
                    <label for="company_name" class="block text-sm font-semibold text-gray-700 mb-2">
                        会社名 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="company_name" id="company_name" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('company_name') border-red-500 @enderror" 
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
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('company_name_kana') border-red-500 @enderror" 
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
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" 
                        value="{{ old('department') }}">
                </div>

                {{-- 役職 --}}
                <div>
                    <label for="position" class="block text-sm font-semibold text-gray-700 mb-2">
                        役職
                    </label>
                    <input type="text" name="position" id="position" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" 
                        value="{{ old('position') }}">
                </div>

                {{-- 担当者名 --}}
                <div>
                    <label for="contact_name" class="block text-sm font-semibold text-gray-700 mb-2">
                        担当者名 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="contact_name" id="contact_name" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('contact_name') border-red-500 @enderror" 
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
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('contact_name_kana') border-red-500 @enderror" 
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
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('email') border-red-500 @enderror" 
                        value="{{ old('email') }}" required>
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
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('phone') border-red-500 @enderror" 
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
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('postal_code') border-red-500 @enderror" 
                            value="{{ old('postal_code') }}" placeholder="123-4567">
                        <button type="button" id="search_address_btn" 
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition duration-300">
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
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
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
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" 
                        value="{{ old('city') }}" placeholder="渋谷区">
                </div>

                {{-- 番地 --}}
                <div>
                    <label for="address_line1" class="block text-sm font-semibold text-gray-700 mb-2">
                        番地
                    </label>
                    <input type="text" name="address_line1" id="address_line1" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" 
                        value="{{ old('address_line1') }}" placeholder="渋谷1-2-3">
                </div>

                {{-- 建物名 --}}
                <div>
                    <label for="address_line2" class="block text-sm font-semibold text-gray-700 mb-2">
                        建物名
                    </label>
                    <input type="text" name="address_line2" id="address_line2" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" 
                        value="{{ old('address_line2') }}" placeholder="○○ビル 5F">
                </div>
            </div>
        </div>

        {{-- 2. 契約内容の選択 --}}
        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-3 border-b-2 border-indigo-500">
                <i class="fas fa-file-contract mr-2"></i>2. 契約内容の選択
            </h2>

            {{-- 契約プラン選択 --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-4">
                    契約プラン <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($plans as $plan)
                        <label class="relative cursor-pointer">
                            <input type="radio" name="contract_plan_id" value="{{ $plan->id }}" 
                                class="peer sr-only" 
                                {{ old('contract_plan_id') == $plan->id ? 'checked' : '' }} required>
                            <div class="border-2 border-gray-300 rounded-lg p-4 transition-all peer-checked:border-indigo-500 peer-checked:bg-indigo-50 hover:border-indigo-300">
                                <div class="text-center">
                                    <div class="text-lg font-bold text-gray-800 mb-2">{{ $plan->name }}</div>
                                    <div class="text-3xl font-bold text-indigo-600 mb-2">{{ $plan->formatted_price }}</div>
                                    <div class="text-sm text-gray-600">{{ $plan->description }}</div>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('contract_plan_id')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            {{-- 利用開始希望日 --}}
            <div>
                <label for="desired_start_date" class="block text-sm font-semibold text-gray-700 mb-2">
                    利用開始希望日 <span class="text-red-500">*</span>
                </label>
                <input type="date" name="desired_start_date" id="desired_start_date" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('desired_start_date') border-red-500 @enderror" 
                    value="{{ old('desired_start_date') }}" min="{{ date('Y-m-d') }}" required>
                @error('desired_start_date')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- 3. 利用規約 --}}
        @if($termsOfService)
        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-3 border-b-2 border-indigo-500">
                <i class="fas fa-file-contract mr-2"></i>3. 利用規約
            </h2>

            {{-- 利用規約の表示 --}}
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6" style="max-height: 400px; overflow-y: auto;">
                <div class="quill-content text-gray-700 text-sm">
                    {!! $termsOfService !!}
                </div>
            </div>

            {{-- 同意チェックボックス --}}
            <div class="flex items-center justify-center">
                <input type="checkbox" name="terms_agreed" id="terms_agreed" value="1"
                    class="mr-3 w-5 h-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded @error('terms_agreed') border-red-500 @enderror"
                    {{ old('terms_agreed') ? 'checked' : '' }} required>
                <label for="terms_agreed" class="text-gray-700">
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
        <div class="flex justify-center space-x-4">
            <a href="{{ url('/') }}" class="px-8 py-3 bg-gray-500 hover:bg-gray-600 text-white font-bold rounded-lg shadow-md transition duration-300">
                キャンセル
            </a>
            <button type="submit" class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-md transition duration-300">
                確認画面へ <i class="fas fa-arrow-right ml-2"></i>
            </button>
        </div>
    </form>
</div>

<script>
    // 今日の日付を最小値に設定
    document.getElementById('desired_start_date').min = new Date().toISOString().split('T')[0];

    // 郵便番号から住所を自動入力
    (function() {
        const postalCodeInput = document.getElementById('postal_code');
        const searchBtn = document.getElementById('search_address_btn');
        const prefectureSelect = document.getElementById('prefecture');
        const cityInput = document.getElementById('city');
        const addressLine1Input = document.getElementById('address_line1');
        const errorDiv = document.getElementById('address_error');
        const loadingDiv = document.getElementById('address_loading');

        // 郵便番号を正規化（ハイフンを除去）
        function normalizePostalCode(postalCode) {
            return postalCode.replace(/[ー−‐]/g, '-').replace(/[^\d-]/g, '');
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

            // 郵便番号入力時の自動フォーマット（ハイフン自動挿入）
            postalCodeInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/[^\d]/g, '');
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

