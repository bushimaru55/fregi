@extends('layouts.app')

@section('title', '決済処理中')

@section('content')
<div class="max-w-2xl mx-auto text-center">
    <div class="bg-white rounded-xl card-shadow p-12">
        <div class="mb-8">
            <div class="inline-block p-6 bg-indigo-100 rounded-full">
                <i class="fas fa-credit-card text-indigo-600 text-5xl"></i>
            </div>
        </div>
        
        <h2 class="text-2xl font-bold text-gray-800 mb-4">決済ページへリダイレクトしています</h2>
        <p class="text-gray-600 mb-8">しばらくお待ちください...</p>
        
        <div class="flex justify-center mb-8">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
        </div>
        
        <p class="text-sm text-gray-500">
            自動的に遷移しない場合は、下のボタンをクリックしてください
        </p>
        
        <form action="{{ $endpoint }}" method="POST" id="payment-form" class="mt-6">
            @foreach($params as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            
            <button type="submit" class="theme-btn-primary px-8 py-4 rounded-lg hover:opacity-90 transition shadow-lg border-0 cursor-pointer">
                <i class="fas fa-arrow-right mr-2"></i>決済ページへ進む
            </button>
        </form>
    </div>
</div>

<script>
    // 自動submit
    setTimeout(function() {
        document.getElementById('payment-form').submit();
    }, 1500);
</script>
@endsection

