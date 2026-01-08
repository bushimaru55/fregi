@extends('layouts.app')

@section('title', 'エラー')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl card-shadow p-12 text-center">
        <div class="mb-8">
            <div class="inline-block p-6 bg-red-100 rounded-full">
                <i class="fas fa-exclamation-circle text-red-600 text-6xl"></i>
            </div>
        </div>
        
        <h2 class="text-3xl font-bold text-gray-800 mb-4">エラーが発生しました</h2>
        <p class="text-lg text-gray-600 mb-8">{{ $message }}</p>
        
        <a href="/billing/" class="inline-block gradient-bg text-white px-8 py-4 rounded-lg hover:opacity-90 transition shadow-lg">
            <i class="fas fa-home mr-2"></i>トップページへ戻る
        </a>
    </div>
</div>
@endsection

