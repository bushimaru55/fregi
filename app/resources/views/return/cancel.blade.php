@extends('layouts.app')

@section('title', '決済キャンセル')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl card-shadow p-12 text-center">
        <div class="mb-8">
            <div class="inline-block p-6 bg-yellow-100 rounded-full">
                <i class="fas fa-times-circle text-yellow-600 text-6xl"></i>
            </div>
        </div>
        
        <h2 class="text-3xl font-bold text-gray-800 mb-4">決済がキャンセルされました</h2>
        <p class="text-lg text-gray-600 mb-8">お客様により決済がキャンセルされました</p>
        
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
                    <span class="text-gray-600">ステータス:</span>
                    <span class="font-semibold text-yellow-600">キャンセル</span>
                </div>
            </div>
        </div>
        
        <div class="flex justify-center space-x-4">
            <a href="/billing/" class="inline-block bg-gray-600 text-white px-8 py-4 rounded-lg hover:bg-gray-700 transition shadow-lg">
                <i class="fas fa-home mr-2"></i>トップページへ戻る
            </a>
        </div>
    </div>
</div>
@endsection

