@extends('layouts.app')

@section('title', '決済完了')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl card-shadow p-12 text-center">
        <div class="mb-8">
            <div class="inline-block p-6 bg-green-100 rounded-full">
                <i class="fas fa-check-circle text-green-600 text-6xl"></i>
            </div>
        </div>
        
        <h2 class="text-3xl font-bold text-gray-800 mb-4">決済が完了しました</h2>
        
        @if($payment->status === 'paid')
            <p class="text-lg text-gray-600 mb-8">ご利用ありがとうございます</p>
            
            <div class="bg-gray-50 rounded-lg p-6 mb-8">
                <div class="space-y-3 text-left">
                    <div class="flex justify-between">
                        <span class="text-gray-600">オーダー番号:</span>
                        <span class="font-semibold text-gray-800">{{ $payment->orderid }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">金額:</span>
                        <span class="font-semibold text-gray-800">¥{{ number_format($payment->amount) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">決済日時:</span>
                        <span class="font-semibold text-gray-800">{{ $payment->completed_at ? $payment->completed_at->format('Y/m/d H:i') : '-' }}</span>
                    </div>
                </div>
            </div>
        @elseif($payment->status === 'waiting_notify')
            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-6 mb-8">
                <p class="text-yellow-800">
                    <i class="fas fa-hourglass-half mr-2"></i>
                    決済処理を確認中です。しばらくお待ちください。
                </p>
            </div>
        @else
            <div class="bg-blue-50 border-l-4 border-blue-500 p-6 mb-8">
                <p class="text-blue-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    決済ステータス: {{ $payment->status }}
                </p>
            </div>
        @endif
        
        <a href="/billing/" class="inline-block theme-btn-primary px-8 py-4 rounded-lg hover:opacity-90 transition shadow-lg no-underline">
            <i class="fas fa-home mr-2"></i>トップページへ戻る
        </a>
    </div>
</div>
@endsection

