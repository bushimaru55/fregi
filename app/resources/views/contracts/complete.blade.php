@extends('layouts.public')

@section('title', '申込完了')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="text-center mb-8">
        <div class="inline-block bg-green-100 rounded-full p-6 mb-4">
            <i class="fas fa-check-circle text-green-600 text-6xl"></i>
        </div>
        <h1 class="text-4xl font-bold text-gray-800 mb-2">お申し込みありがとうございます！</h1>
        <p class="text-gray-600">決済が完了しました。ご登録のメールアドレスに確認メールをお送りしております。</p>
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
                        <p class="text-xl font-bold text-green-600">{{ number_format($contract->contractPlan->price) }}円（税込）</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">利用開始希望日</p>
                        <p class="text-lg font-semibold text-gray-800">{{ $contract->desired_start_date->format('Y年m月d日') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">契約ステータス</p>
                        <span class="inline-block bg-green-200 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">
                            {{ $contract->status_label }}
                        </span>
                    </div>
                </div>
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

    {{-- 決済情報 --}}
    <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-3 border-b-2 border-blue-500">
            <i class="fas fa-credit-card mr-2"></i>決済情報
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-600 mb-1">注文番号</p>
                <p class="text-lg font-mono font-semibold text-gray-800">{{ $payment->orderid }}</p>
            </div>
            @if($payment->receiptno)
            <div>
                <p class="text-sm text-gray-600 mb-1">F-REGI受付番号</p>
                <p class="text-lg font-mono font-semibold text-gray-800">{{ $payment->receiptno }}</p>
            </div>
            @endif
            <div>
                <p class="text-sm text-gray-600 mb-1">決済金額</p>
                <p class="text-lg font-bold text-gray-800">{{ number_format($payment->amount) }}円</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">決済日時</p>
                <p class="text-lg font-semibold text-gray-800">{{ $payment->completed_at?->format('Y年m月d日 H:i') ?? '処理中' }}</p>
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
            <li>利用開始希望日の前日までに、アカウント情報をメールでお知らせします。</li>
            <li>利用開始日よりサービスをご利用いただけます。</li>
        </ol>
    </div>

    {{-- ボタン --}}
    <div class="flex justify-center">
        <a href="{{ url('/') }}" class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-md transition duration-300">
            <i class="fas fa-home mr-2"></i>トップページへ
        </a>
    </div>
</div>
@endsection

