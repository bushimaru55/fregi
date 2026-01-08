@extends('layouts.public')

@section('title', '申込内容確認')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-8 text-center">
        <h1 class="text-4xl font-bold text-gray-800 mb-2">申込内容確認</h1>
        <p class="text-gray-600">以下の内容でお申し込みします。よろしければ「決済へ進む」ボタンをクリックしてください。</p>
    </div>

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
                            <p class="text-4xl font-bold text-indigo-600">{{ number_format($plan->price) }}<span class="text-xl">円</span></p>
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

        {{-- 4. お支払い情報 --}}
        <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-3 border-b-2 border-indigo-500">
                <i class="fas fa-credit-card mr-2"></i>お支払い情報
            </h2>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p class="text-sm text-gray-700">
                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                    この後、決済画面（F-REGI）へ移動します。クレジットカード情報は安全な決済代行システムで入力いただきます。
                </p>
            </div>
        </div>

        {{-- ボタン --}}
        <div class="flex justify-center space-x-4">
            <a href="{{ route('contract.create') }}" class="px-8 py-3 bg-gray-500 hover:bg-gray-600 text-white font-bold rounded-lg shadow-md transition duration-300">
                <i class="fas fa-arrow-left mr-2"></i>戻る
            </a>
            <button type="submit" class="px-8 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg shadow-md transition duration-300">
                <i class="fas fa-lock mr-2"></i>決済へ進む
            </button>
        </div>
    </form>
</div>
@endsection

