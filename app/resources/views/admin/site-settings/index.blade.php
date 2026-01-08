@extends('layouts.admin')

@section('title', 'サイト管理')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-800">
            <i class="fas fa-cog text-indigo-600 mr-3"></i>サイト管理
        </h2>
        <p class="text-gray-600 mt-2">サイト設定の管理</p>
    </div>

    <!-- Settings Card -->
    <div class="bg-white rounded-xl card-shadow p-8">
        <div class="space-y-6">
            <!-- Terms of Service Section -->
            <div class="border-b border-gray-200 pb-6">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-file-contract text-indigo-500 mr-2"></i>利用規約
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">新規申込フォームに表示される利用規約の内容を管理します</p>
                    </div>
                    <a href="{{ route('admin.site-settings.edit') }}" 
                       class="gradient-bg text-white px-6 py-3 rounded-lg hover:opacity-90 transition shadow-lg">
                        <i class="fas fa-edit mr-2"></i>編集
                    </a>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    @if($termsOfService)
                        <div class="terms-content text-gray-700">
                            {{ $termsOfService }}
                        </div>
                    @else
                        <p class="text-gray-500 italic">利用規約が設定されていません。編集ボタンから設定してください。</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* 利用規約表示用スタイル - プレーンテキスト表示 */
.terms-content {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
    font-size: 14px;
    line-height: 1.6;
    color: #374151;
    word-wrap: break-word;
    white-space: pre-wrap;
}
</style>
@endpush
