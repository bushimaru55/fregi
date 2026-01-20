@extends('layouts.public')

@section('title', '申込内容確認')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-8 text-center">
        <h1 class="text-4xl font-bold text-gray-800 mb-2">申込内容確認</h1>
        @if(isset($isViewOnly) && $isViewOnly)
            <div class="bg-blue-50 border-2 border-blue-400 rounded-lg p-4 mb-4">
                <p class="text-blue-800 font-semibold">
                    <i class="fas fa-eye mr-2"></i>閲覧画面（この画面は閲覧専用です。実際の申込処理には使用できません）
                </p>
            </div>
        @else
            <p class="text-gray-600">以下の内容でお申し込みします。よろしければ「決済へ進む」ボタンをクリックしてください。</p>
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
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach

        {{-- 1. 申込企業情報 --}}
        <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-3 border-b-2 border-indigo-500">
                <i class="fas fa-building mr-2"></i>申込企業情報
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
        <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-3 border-b-2 border-indigo-500">
                <i class="fas fa-file-contract mr-2"></i>契約内容
            </h2>

            <div class="space-y-4">
                <div class="bg-indigo-50 border-2 border-indigo-500 rounded-lg p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-600 mb-1">契約プラン</p>
                            <p class="text-2xl font-bold text-gray-800">{{ $plan->name }}</p>
                            <p class="text-sm text-gray-600 mt-2">{{ $plan->description }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-600 mb-1">料金</p>
                            <p class="text-4xl font-bold text-indigo-600">{{ $plan->formatted_price }}</p>
                            <p class="text-xs text-gray-500 mt-1">（税込）</p>
                        </div>
                    </div>
                </div>

                <div>
                    <p class="text-sm text-gray-600 mb-1">利用開始希望日</p>
                    <p class="text-lg font-semibold text-gray-800">{{ \Carbon\Carbon::parse($data['desired_start_date'])->format('Y年m月d日') }}</p>
                </div>
            </div>
        </div>

        {{-- 3. 利用規約への同意 --}}
        @if($termsOfService && !empty($data['terms_agreed']))
        <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-3 border-b-2 border-indigo-500">
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
        <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-3 border-b-2 border-indigo-500">
                <i class="fas fa-credit-card mr-2"></i>お支払い情報
            </h2>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-gray-700">
                    <i class="fas fa-shield-alt text-yellow-500 mr-2"></i>
                    クレジットカード情報はSSL暗号化通信により安全に送信されます。
                </p>
            </div>

            <div class="space-y-6">
                {{-- カード番号 --}}
                <div>
                    <label for="card_number" class="block text-sm font-semibold text-gray-700 mb-2">
                        カード番号 <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-2 items-center">
                        <input type="text" name="pan1" id="pan1" maxlength="4" pattern="\d{4}" 
                            class="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('pan1') border-red-500 @enderror" 
                            placeholder="1234" required autocomplete="cc-number">
                        <span class="text-gray-400">-</span>
                        <input type="text" name="pan2" id="pan2" maxlength="4" pattern="\d{4}" 
                            class="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('pan2') border-red-500 @enderror" 
                            placeholder="5678" required>
                        <span class="text-gray-400">-</span>
                        <input type="text" name="pan3" id="pan3" maxlength="4" pattern="\d{4}" 
                            class="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('pan3') border-red-500 @enderror" 
                            placeholder="9012" required>
                        <span class="text-gray-400">-</span>
                        <input type="text" name="pan4" id="pan4" maxlength="4" pattern="\d{4}" 
                            class="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('pan4') border-red-500 @enderror" 
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
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('card_expiry_month') border-red-500 @enderror" 
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
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('card_expiry_year') border-red-500 @enderror" 
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
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('card_name') border-red-500 @enderror" 
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
                        class="w-32 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('scode') border-red-500 @enderror" 
                        placeholder="123" autocomplete="cc-csc">
                    @error('scode')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1">カード裏面の3桁または4桁の数字（任意）</p>
                </div>
            </div>
        </div>

        {{-- ボタン --}}
        <div class="flex justify-center space-x-4">
            @if(isset($isViewOnly) && $isViewOnly)
                <a href="{{ route('contract.create') }}" class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-md transition duration-300">
                    <i class="fas fa-file-alt mr-2"></i>申込フォームへ
                </a>
            @else
                <a href="{{ route('contract.create') }}" class="px-8 py-3 bg-gray-500 hover:bg-gray-600 text-white font-bold rounded-lg shadow-md transition duration-300">
                    <i class="fas fa-arrow-left mr-2"></i>戻る
                </a>
                <button type="submit" class="px-8 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg shadow-md transition duration-300">
                    <i class="fas fa-lock mr-2"></i>決済へ進む
                </button>
            @endif
        </div>
    @endif
    @if(!isset($isViewOnly) || !$isViewOnly)
    </form>
    @endif
</div>
@endsection

