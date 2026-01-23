@extends('layouts.public')

@section('title', '申込内容確認')

@section('content')
<div class="max-w-4xl mx-auto px-4 md:px-0">
    <div class="mb-6 md:mb-8 text-center">
        <h1 class="text-2xl md:text-4xl font-bold text-gray-800 mb-2">申込内容確認</h1>
        @if(isset($isViewOnly) && $isViewOnly)
            <div class="bg-blue-50 border-2 border-blue-400 rounded-lg p-3 md:p-4 mb-4">
                <p class="text-blue-800 font-semibold text-sm md:text-base">
                    <i class="fas fa-eye mr-2"></i>閲覧画面（この画面は閲覧専用です。実際の申込処理には使用できません）
                </p>
            </div>
        @else
            <p class="text-sm md:text-base text-gray-600">以下の内容でお申し込みします。よろしければ「決済へ進む」ボタンをクリックしてください。</p>
        @endif
    </div>

    {{-- エラーメッセージ表示（決済処理失敗時など） --}}
    @if(isset($error) && $error)
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-3"></i>
                <p class="font-semibold">{{ $error }}</p>
            </div>
        </div>
    @endif

    {{-- バリデーションエラー表示 --}}
    @if(isset($validation_errors) && $validation_errors)
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r">
            <div class="flex items-start">
                <i class="fas fa-exclamation-circle mr-3 mt-1"></i>
                <div>
                    <p class="font-semibold mb-2">入力内容に誤りがあります。以下の項目を確認してください：</p>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($validation_errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    @if(!isset($isViewOnly) || !$isViewOnly)
    <form action="{{ route('contract.store') }}" method="POST">
        @csrf

        {{-- 隠しフィールドで全データを送信 --}}
        @foreach($data as $key => $value)
            @if(is_array($value))
                {{-- 配列の場合は各要素を個別のhiddenフィールドとして送信 --}}
                @foreach($value as $item)
                    <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                @endforeach
            @else
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach

        {{-- 1. 申込企業情報 --}}
        <div class="bg-white shadow-lg rounded-lg p-4 md:p-6 mb-4 md:mb-6">
            <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-4 md:mb-6 pb-2 md:pb-3 border-b-2 border-indigo-500">
                <i class="fas fa-building mr-2"></i>申込企業情報
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                <div>
                    <p class="text-sm text-gray-600 mb-1">会社名</p>
                    <p class="text-lg font-semibold text-gray-800">{{ $data['company_name'] }}</p>
                </div>

                @if(!empty($data['company_name_kana']))
                <div>
                    <p class="text-sm text-gray-600 mb-1">会社名（フリガナ）</p>
                    <p class="text-lg font-semibold text-gray-800">{{ $data['company_name_kana'] }}</p>
                </div>
                @endif

                @if(!empty($data['department']))
                <div>
                    <p class="text-sm text-gray-600 mb-1">部署名</p>
                    <p class="text-lg font-semibold text-gray-800">{{ $data['department'] }}</p>
                </div>
                @endif

                @if(!empty($data['position']))
                <div>
                    <p class="text-sm text-gray-600 mb-1">役職</p>
                    <p class="text-lg font-semibold text-gray-800">{{ $data['position'] }}</p>
                </div>
                @endif

                <div>
                    <p class="text-sm text-gray-600 mb-1">担当者名</p>
                    <p class="text-lg font-semibold text-gray-800">{{ $data['contact_name'] }}</p>
                </div>

                @if(!empty($data['contact_name_kana']))
                <div>
                    <p class="text-sm text-gray-600 mb-1">担当者名（フリガナ）</p>
                    <p class="text-lg font-semibold text-gray-800">{{ $data['contact_name_kana'] }}</p>
                </div>
                @endif

                <div>
                    <p class="text-sm text-gray-600 mb-1">メールアドレス</p>
                    <p class="text-lg font-semibold text-gray-800">{{ $data['email'] }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-600 mb-1">電話番号</p>
                    <p class="text-lg font-semibold text-gray-800">{{ $data['phone'] }}</p>
                </div>

                @if(!empty($data['postal_code']) || !empty($data['prefecture']) || !empty($data['city']) || !empty($data['address_line1']) || !empty($data['address_line2']))
                <div class="md:col-span-2">
                    <p class="text-sm text-gray-600 mb-1">住所</p>
                    <p class="text-lg font-semibold text-gray-800">
                        @if(!empty($data['postal_code']))
                            〒{{ $data['postal_code'] }}<br>
                        @endif
                        {{ $data['prefecture'] ?? '' }}
                        {{ $data['city'] ?? '' }}
                        {{ $data['address_line1'] ?? '' }}
                        @if(!empty($data['address_line2']))
                            <br>{{ $data['address_line2'] }}
                        @endif
                    </p>
                </div>
                @endif
            </div>
        </div>

        {{-- 2. 契約内容 --}}
        <div class="bg-white shadow-lg rounded-lg p-4 md:p-6 mb-4 md:mb-6">
            <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-4 md:mb-6 pb-2 md:pb-3 border-b-2 border-indigo-500">
                <i class="fas fa-file-contract mr-2"></i>契約内容
            </h2>

            <div class="space-y-3 md:space-y-4">
                <div class="bg-indigo-50 border-2 border-indigo-500 rounded-lg p-4 md:p-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 md:gap-0">
                        <div>
                            <p class="text-xs md:text-sm text-gray-600 mb-1">製品</p>
                            <p class="text-xl md:text-2xl font-bold text-gray-800">{{ $plan->name ?? '' }}</p>
                            @if(!empty($plan->description))
                            <p class="text-xs md:text-sm text-gray-600 mt-2">{{ $plan->description }}</p>
                            @endif
                        </div>
                        <div class="text-left md:text-right">
                            <p class="text-xs md:text-sm text-gray-600 mb-1">料金</p>
                            <p class="text-2xl md:text-3xl font-bold text-indigo-600">{{ number_format($plan->price ?? 0) }}円</p>
                            @if(isset($plan->billing_type) && $plan->billing_type === 'monthly')
                                <p class="text-xs text-gray-500 mt-1">（税込・月額）</p>
                            @else
                                <p class="text-xs text-gray-500 mt-1">（税込）</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- オプション製品の表示 --}}
                @if(isset($optionProducts) && $optionProducts->isNotEmpty())
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <p class="text-sm text-gray-600 mb-3 font-semibold">オプション製品</p>
                    <div class="space-y-2">
                        @foreach($optionProducts as $option)
                            <div class="flex justify-between items-center bg-gray-50 rounded-lg p-3">
                                <span class="text-gray-800">{{ $option->name }}</span>
                                <span class="font-semibold text-gray-800">{{ number_format($option->unit_price) }}円</span>
                            </div>
                        @endforeach
                        <div class="flex justify-between items-center pt-2 border-t border-gray-300">
                            <span class="text-sm text-gray-600">オプション製品合計</span>
                            <span class="font-semibold text-gray-800">{{ number_format($optionTotalAmount ?? 0) }}円</span>
                        </div>
                    </div>
                </div>
                @endif

                {{-- 合計金額の表示 --}}
                @php
                    $baseAmount = $plan->price ?? 0;
                    $optionTotal = $optionTotalAmount ?? 0;
                    $totalAmount = $baseAmount + $optionTotal;
                @endphp
                <div class="mt-4 pt-4 border-t-2 border-indigo-500">
                    <div class="flex justify-between items-center">
                        <span class="text-base md:text-lg font-semibold text-gray-800">合計金額</span>
                        <span class="text-2xl md:text-3xl font-bold text-indigo-600">{{ number_format($totalAmount) }}円</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1 text-right">（税込）</p>
                </div>

                <div>
                    <p class="text-sm text-gray-600 mb-1">利用開始希望日</p>
                    <p class="text-lg font-semibold text-gray-800">{{ \Carbon\Carbon::parse($data['desired_start_date'])->format('Y年m月d日') }}</p>
                </div>
            </div>
        </div>

        {{-- 3. 利用規約への同意 --}}
        @if($termsOfService && !empty($data['terms_agreed']))
        <div class="bg-white shadow-lg rounded-lg p-4 md:p-6 mb-4 md:mb-6">
            <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-4 md:mb-6 pb-2 md:pb-3 border-b-2 border-indigo-500">
                <i class="fas fa-file-contract mr-2"></i>利用規約への同意
            </h2>

            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                    <p class="text-sm font-semibold text-gray-700">
                        利用規約に同意いただきました。
                    </p>
                </div>
            </div>
        </div>
        @endif

        {{-- 4. お支払い情報（カード情報入力） --}}
        <div class="bg-white shadow-lg rounded-lg p-4 md:p-6 mb-4 md:mb-6">
            <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-4 md:mb-6 pb-2 md:pb-3 border-b-2 border-indigo-500">
                <i class="fas fa-credit-card mr-2"></i>お支払い情報
            </h2>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 md:p-4 mb-4 md:mb-6">
                <p class="text-xs md:text-sm text-gray-700">
                    <i class="fas fa-shield-alt text-yellow-500 mr-2"></i>
                    クレジットカード情報はSSL暗号化通信により安全に送信されます。
                </p>
            </div>

            <div class="space-y-4 md:space-y-6">
                {{-- カード番号 --}}
                <div>
                    <label for="card_number" class="block text-sm font-semibold text-gray-700 mb-2">
                        カード番号 <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-1 md:gap-2 items-center flex-wrap">
                        <input type="text" name="pan1" id="pan1" maxlength="4" pattern="\d{4}" 
                            class="w-20 md:w-24 px-2 md:px-3 py-3 md:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-base @error('pan1') border-red-500 @enderror" 
                            placeholder="1234" required autocomplete="cc-number">
                        <span class="text-gray-400">-</span>
                        <input type="text" name="pan2" id="pan2" maxlength="4" pattern="\d{4}" 
                            class="w-20 md:w-24 px-2 md:px-3 py-3 md:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-base @error('pan2') border-red-500 @enderror" 
                            placeholder="5678" required>
                        <span class="text-gray-400">-</span>
                        <input type="text" name="pan3" id="pan3" maxlength="4" pattern="\d{4}" 
                            class="w-20 md:w-24 px-2 md:px-3 py-3 md:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-base @error('pan3') border-red-500 @enderror" 
                            placeholder="9012" required>
                        <span class="text-gray-400">-</span>
                        <input type="text" name="pan4" id="pan4" maxlength="4" pattern="\d{4}" 
                            class="w-20 md:w-24 px-2 md:px-3 py-3 md:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-base @error('pan4') border-red-500 @enderror" 
                            placeholder="3456" required>
                    </div>
                    @error('pan1')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    @error('pan2')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    @error('pan3')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    @error('pan4')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1">16桁のカード番号を4桁ずつ入力してください</p>
                </div>

                {{-- 有効期限 --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="card_expiry_month" class="block text-sm font-semibold text-gray-700 mb-2">
                            有効期限（月） <span class="text-red-500">*</span>
                        </label>
                        <select name="card_expiry_month" id="card_expiry_month" 
                            class="w-full px-3 md:px-3 py-3 md:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-base @error('card_expiry_month') border-red-500 @enderror" 
                            required>
                            <option value="">--</option>
                            @for($i = 1; $i <= 12; $i++)
                                <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}">{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                            @endfor
                        </select>
                        @error('card_expiry_month')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="card_expiry_year" class="block text-sm font-semibold text-gray-700 mb-2">
                            有効期限（年） <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="card_expiry_year" id="card_expiry_year" maxlength="4" pattern="\d{2,4}" 
                            class="w-full px-3 md:px-3 py-3 md:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-base @error('card_expiry_year') border-red-500 @enderror" 
                            placeholder="25 または 2025" required autocomplete="cc-exp-year">
                        @error('card_expiry_year')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1">2桁または4桁で入力（例: 25 または 2025）</p>
                    </div>
                </div>

                {{-- カード名義 --}}
                <div>
                    <label for="card_name" class="block text-sm font-semibold text-gray-700 mb-2">
                        カード名義 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="card_name" id="card_name" maxlength="45" 
                        class="w-full px-3 md:px-3 py-3 md:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-base @error('card_name') border-red-500 @enderror" 
                        placeholder="TARO YAMADA" required autocomplete="cc-name">
                    @error('card_name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1">カード表面に記載されている名義をアルファベット（大文字）で入力してください（45文字以内）</p>
                </div>

                {{-- セキュリティコード --}}
                <div>
                    <label for="scode" class="block text-sm font-semibold text-gray-700 mb-2">
                        セキュリティコード
                    </label>
                    <input type="text" name="scode" id="scode" maxlength="4" pattern="\d{3,4}" 
                        class="w-32 md:w-32 px-3 md:px-3 py-3 md:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-base @error('scode') border-red-500 @enderror" 
                        placeholder="123" autocomplete="cc-csc">
                    @error('scode')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1">カード裏面の3桁または4桁の数字（任意）</p>
                </div>
            </div>
        </div>

        {{-- ボタン --}}
        <div class="flex flex-col sm:flex-row justify-center gap-3 md:gap-4 mb-6 md:mb-8">
            @if(isset($isViewOnly) && $isViewOnly)
                <a href="{{ route('contract.create') }}" class="px-6 md:px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-md transition duration-300 text-center text-base">
                    <i class="fas fa-file-alt mr-2"></i>申込フォームへ
                </a>
            @else
                <a href="{{ route('contract.create') }}" class="px-6 md:px-8 py-3 bg-gray-500 hover:bg-gray-600 text-white font-bold rounded-lg shadow-md transition duration-300 text-center text-base">
                    <i class="fas fa-arrow-left mr-2"></i>戻る
                </a>
                <button type="submit" class="px-6 md:px-8 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg shadow-md transition duration-300 text-base">
                    <i class="fas fa-lock mr-2"></i>決済へ進む
                </button>
            @endif
        </div>
    @endif
    @if(!isset($isViewOnly) || !$isViewOnly)
    </form>
    @endif
</div>

<script>
// カード番号の自動フォーカス移動（4桁入力で次のフィールドへ）
(function() {
    const pan1 = document.getElementById('pan1');
    const pan2 = document.getElementById('pan2');
    const pan3 = document.getElementById('pan3');
    const pan4 = document.getElementById('pan4');
    
    if (pan1 && pan2 && pan3 && pan4) {
        // 数字のみ入力可能にする
        [pan1, pan2, pan3, pan4].forEach(function(input) {
            input.addEventListener('input', function(e) {
                // 数字以外を削除
                e.target.value = e.target.value.replace(/[^\d]/g, '');
            });
            
            // 4桁入力で次のフィールドにフォーカス移動
            input.addEventListener('input', function(e) {
                if (e.target.value.length === 4) {
                    if (e.target === pan1) {
                        pan2.focus();
                    } else if (e.target === pan2) {
                        pan3.focus();
                    } else if (e.target === pan3) {
                        pan4.focus();
                    }
                }
            });
            
            // Backspaceで前のフィールドに戻る（空の場合）
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && e.target.value.length === 0) {
                    if (e.target === pan2) {
                        pan1.focus();
                    } else if (e.target === pan3) {
                        pan2.focus();
                    } else if (e.target === pan4) {
                        pan3.focus();
                    }
                }
            });
        });
    }
})();
</script>
@endsection

