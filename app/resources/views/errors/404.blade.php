@extends('layouts.public')

@section('title', 'ページが見つかりません')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white shadow-lg rounded-lg p-12 text-center">
        <div class="mb-8">
            <div class="inline-block p-6 bg-red-100 rounded-full">
                <i class="fas fa-exclamation-circle text-red-600 text-6xl"></i>
            </div>
        </div>
        
        <h1 class="text-4xl font-bold text-gray-800 mb-4">404</h1>
        <h2 class="text-2xl font-bold text-gray-800 mb-4">ページが見つかりません</h2>
        <p class="text-lg text-gray-600 mb-8">
            @if(isset($exception) && $exception->getMessage())
                {{ $exception->getMessage() }}
            @else
                お探しのページは見つかりませんでした。<br>
                ページが移動または削除された可能性があります。
            @endif
        </p>
        
        <div class="flex justify-center space-x-4">
            <a href="{{ url('/') }}" class="inline-block bg-gray-600 text-white px-8 py-3 rounded-lg hover:bg-gray-700 transition shadow-md">
                <i class="fas fa-home mr-2"></i>トップページへ戻る
            </a>
            <a href="{{ route('contract.create') }}" class="inline-block bg-indigo-600 text-white px-8 py-3 rounded-lg hover:bg-indigo-700 transition shadow-md">
                <i class="fas fa-file-contract mr-2"></i>申込フォームへ
            </a>
        </div>
    </div>
</div>
@endsection
