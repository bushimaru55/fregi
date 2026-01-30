@extends('layouts.public')

@section('title', '申込完了')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="text-center mb-8">
        <div class="inline-block bg-green-100 rounded-full p-6 mb-4">
            <i class="fas fa-check-circle text-green-600 text-6xl"></i>
        </div>
        <h1 class="text-4xl font-bold text-gray-800 mb-2">お申し込みありがとうございます！</h1>
        <p class="text-gray-600">申込を受け付けました。ご登録のメールアドレスに確認メールをお送りしております。</p>
    </div>

    {{-- 契約情報 --}}
    <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-3 border-b-2 border-green-500">
            <i class="fas fa-file-contract mr-2"></i>契約情報
        </h2>

        <div class="space-y-4">
            <div class="bg-green-50 border-2 border-green-500 rounded-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">契約プラン</p>
                        <p class="text-xl font-bold text-gray-800">{{ $contract->contractPlan->name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">料金</p>
                        <p class="text-xl font-bold text-green-600">{{ $contract->contractPlan->formatted_price }}（税込）</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">契約ステータス</p>
                        <span class="inline-block bg-green-200 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">
                            {{ $contract->status_label }}
                        </span>
                    </div>
                </div>
                
                {{-- オプション製品の表示 --}}
                @if(isset($optionItems) && $optionItems->isNotEmpty())
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <p class="text-sm text-gray-600 mb-3 font-semibold">オプション製品</p>
                    <div class="space-y-2">
                        @foreach($optionItems as $item)
                            <div class="flex justify-between items-center bg-gray-50 rounded-lg p-3">
                                <span class="text-gray-800">{{ $item->product_name ?? ($item->product->name ?? '') }}</span>
                                <span class="font-semibold text-gray-800">{{ number_format($item->subtotal ?? $item->unit_price ?? 0) }}円</span>
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
                    $baseAmount = $contract->contractPlan->price ?? 0;
                    $optionTotal = $optionTotalAmount ?? 0;
                    $totalAmount = $baseAmount + $optionTotal;
                @endphp
                @if(isset($optionItems) && $optionItems->isNotEmpty())
                <div class="mt-4 pt-4 border-t-2 border-green-300">
                    <div class="flex justify-between items-center">
                        <span class="text-base md:text-lg font-semibold text-gray-800">合計金額</span>
                        <span class="text-2xl md:text-3xl font-bold text-green-600">{{ number_format($totalAmount) }}円</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1 text-right">（税込）</p>
                </div>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600 mb-1">会社名</p>
                    <p class="text-lg font-semibold text-gray-800">{{ $contract->company_name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1">担当者名</p>
                    <p class="text-lg font-semibold text-gray-800">{{ $contract->contact_name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1">メールアドレス</p>
                    <p class="text-lg font-semibold text-gray-800">{{ $contract->email }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1">電話番号</p>
                    <p class="text-lg font-semibold text-gray-800">{{ $contract->phone }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- 今後の流れ --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">
            <i class="fas fa-info-circle text-blue-500 mr-2"></i>今後の流れ
        </h3>
        <ol class="list-decimal list-inside space-y-2 text-gray-700">
            <li>ご登録のメールアドレスに契約内容の確認メールをお送りします。</li>
            <li>アカウント情報をメールでお知らせします。</li>
            <li>サービスをご利用いただけます。</li>
        </ol>
    </div>

    {{-- ボタン --}}
    <div class="flex justify-center">
        @php
            // 管理画面で設定されたトップページのURLを取得（設定がない場合はデフォルトでトップページ）
            $topPageUrl = \App\Models\SiteSetting::getTextValue('top_page_url', url('/'));
        @endphp
        <a href="{{ $topPageUrl }}" class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-md transition duration-300">
            <i class="fas fa-home mr-2"></i>トップページへ
        </a>
    </div>
</div>
@endsection

