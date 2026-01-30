@extends('layouts.app')

@section('title', '決済エラー')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl card-shadow p-12 text-center">
        <div class="mb-8">
            <div class="inline-block p-6 bg-red-100 rounded-full">
                <i class="fas fa-exclamation-triangle text-red-600 text-6xl"></i>
            </div>
        </div>
        
        <h2 class="text-3xl font-bold text-gray-800 mb-4">決済に失敗しました</h2>
        <p class="text-lg text-gray-600 mb-8">決済処理中にエラーが発生しました</p>
        
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
                @if($payment->failure_reason)
                <div class="pt-3 border-t">
                    <span class="text-gray-600 block mb-2">エラー詳細:</span>
                    <span class="text-red-600 text-sm">{{ $payment->failure_reason }}</span>
                </div>
                @endif
            </div>
        </div>
        
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-8 text-left">
            <p class="text-sm text-blue-800">
                <i class="fas fa-info-circle mr-2"></i>
                お困りの場合は、サポートセンターまでお問い合わせください。
            </p>
        </div>
        
        <div class="flex justify-center space-x-4">
            <a href="/billing/" class="inline-block bg-gray-600 text-white px-8 py-4 rounded-lg hover:bg-gray-700 transition shadow-lg">
                <i class="fas fa-home mr-2"></i>トップページへ戻る
            </a>
        </div>
    </div>
</div>
@endsection

