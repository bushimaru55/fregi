@extends('layouts.public')

@section('title', '申込内容確認')

@push('styles')
<style>
    body {
        background: linear-gradient(135deg, #a8e6cf 0%, #88d8c0 50%, #b8e6d3 100%);
        min-height: 100vh;
    }
    .payment-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }
    .btn-orange {
        background: #ff6b6b;
        color: white;
    }
    .btn-orange:hover {
        background: #ff5252;
    }
    .btn-teal {
        background: #4ecdc4;
        color: white;
    }
    .btn-teal:hover {
        background: #3db8b0;
    }
    .section-title {
        border-bottom: 2px solid #4ecdc4;
    }
</style>
@endpush

@section('content')
<div class="max-w-4xl mx-auto px-4 md:px-0 py-8">
    <div class="mb-6 md:mb-8 text-center">
        <h1 class="text-2xl md:text-4xl font-bold text-gray-800 mb-2">申込内容確認</h1>
        @if(isset($isViewOnly) && $isViewOnly)
            <div class="bg-blue-50 border-2 border-blue-400 rounded-lg p-3 md:p-4 mb-4">
                <p class="text-blue-800 font-semibold text-sm md:text-base">
                    <i class="fas fa-eye mr-2"></i>閲覧画面（この画面は閲覧専用です。実際の申込処理には使用できません）
                </p>
            </div>
        @else
            <p class="text-sm md:text-base text-gray-600">以下の内容でお申し込みします。よろしければ「申し込む」ボタンをクリックしてください。</p>
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
        <div class="payment-card p-4 md:p-6 mb-4 md:mb-6">
            <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-4 md:mb-6 pb-2 md:pb-3 section-title">
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

        {{-- 2. ご利用情報 --}}
        <div class="payment-card p-4 md:p-6 mb-4 md:mb-6">
            <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-4 md:mb-6 pb-2 md:pb-3 section-title">
                <i class="fas fa-globe mr-2"></i>ご利用情報
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                @if(!empty($data['usage_url_domain']))
                <div class="md:col-span-2">
                    <p class="text-sm text-gray-600 mb-1">ご利用URL・ドメイン</p>
                    <p class="text-lg font-semibold text-gray-800">{{ $data['usage_url_domain'] }}</p>
                </div>
                @endif

                @if(!empty($data['import_from_trial']))
                <div class="md:col-span-2">
                    <p class="text-sm text-gray-600 mb-1">体験版からのインポートを希望する</p>
                    <p class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>希望する
                    </p>
                </div>
                @endif
            </div>
        </div>

        {{-- 3. 契約内容 --}}
        <div class="payment-card p-4 md:p-6 mb-4 md:mb-6">
            <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-4 md:mb-6 pb-2 md:pb-3 section-title">
                <i class="fas fa-file-contract mr-2"></i>契約内容
            </h2>

            <div class="space-y-3 md:space-y-4">
                <div class="bg-teal-50 border-2 border-teal-300 rounded-lg p-4 md:p-6">
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
                            <p class="text-2xl md:text-3xl font-bold text-teal-600">{{ number_format($plan->price ?? 0) }}円</p>
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
                <div class="mt-4 pt-4 border-t-2 border-teal-300">
                    <div class="flex justify-between items-center">
                        <span class="text-base md:text-lg font-semibold text-gray-800">合計金額</span>
                        <span class="text-2xl md:text-3xl font-bold text-teal-600">{{ number_format($totalAmount) }}円</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1 text-right">（税込）</p>
                </div>

            </div>
        </div>

        {{-- 4. 利用規約への同意 --}}
        @if($termsOfService && !empty($data['terms_agreed']))
        <div class="payment-card p-4 md:p-6 mb-4 md:mb-6">
            <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-4 md:mb-6 pb-2 md:pb-3 section-title">
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

        {{-- ボタン --}}
        <div class="flex flex-col sm:flex-row justify-center gap-3 md:gap-4 mb-6 md:mb-8">
            @if(isset($isViewOnly) && $isViewOnly)
                <a href="{{ route('contract.create') }}" class="px-6 md:px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-md transition duration-300 text-center text-base">
                    <i class="fas fa-file-alt mr-2"></i>申込フォームへ
                </a>
            @else
                <a href="{{ route('contract.create') }}" class="px-6 md:px-8 py-3 btn-teal font-bold rounded-lg shadow-md transition duration-300 text-center text-base">
                    <i class="fas fa-arrow-left mr-2"></i>戻る
                </a>
                <button type="submit" class="px-6 md:px-8 py-3 btn-orange font-bold rounded-lg shadow-md transition duration-300 text-base">
                    <i class="fas fa-paper-plane mr-2"></i>申し込む
                </button>
            @endif
        </div>
    @endif
    @if(!isset($isViewOnly) || !$isViewOnly)
    </form>
    @endif
</div>
@endsection

