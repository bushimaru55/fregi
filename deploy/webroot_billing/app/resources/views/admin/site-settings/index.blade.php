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
                        {{-- サニタイズ済みHTMLを表示（{!! !!}で出力） --}}
                        <div class="prose prose-sm max-w-none terms-html-content">
                            {!! $termsOfService !!}
                        </div>
                    @else
                        <p class="text-gray-500 italic">利用規約が設定されていません。編集ボタンから設定してください。</p>
                    @endif
                </div>
            </div>

            <!-- Top Page URL Section -->
            <div class="border-b border-gray-200 pb-6 pt-6">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-home text-indigo-500 mr-2"></i>トップページのURL
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">決済完了画面の「トップへ戻る」ボタンのリンク先URLを設定します</p>
                    </div>
                    <a href="{{ route('admin.site-settings.top-page-url.edit') }}" 
                       class="gradient-bg text-white px-6 py-3 rounded-lg hover:opacity-90 transition shadow-lg">
                        <i class="fas fa-edit mr-2"></i>編集
                    </a>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    @if($topPageUrl)
                        <div class="flex items-center">
                            <i class="fas fa-link text-indigo-500 mr-2"></i>
                            <a href="{{ $topPageUrl }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 break-all">
                                {{ $topPageUrl }}
                            </a>
                        </div>
                    @else
                        <p class="text-gray-500 italic">
                            <i class="fas fa-info-circle mr-2"></i>
                            トップページのURLが設定されていません。編集ボタンから設定してください。<br>
                            <span class="text-xs">（未設定の場合は、デフォルトでトップページ（{{ url('/') }}）にリンクします）</span>
                        </p>
                    @endif
                </div>
            </div>

            <!-- Product Page URL Section -->
            <div class="pt-6">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-globe text-indigo-500 mr-2"></i>製品ページのURL
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">公開ページヘッダーの「製品ページへ戻る」ボタンのリンク先URLを設定します</p>
                    </div>
                    <a href="{{ route('admin.site-settings.product-page-url.edit') }}" 
                       class="gradient-bg text-white px-6 py-3 rounded-lg hover:opacity-90 transition shadow-lg">
                        <i class="fas fa-edit mr-2"></i>編集
                    </a>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    @if($productPageUrl)
                        <div class="flex items-center">
                            <i class="fas fa-link text-indigo-500 mr-2"></i>
                            <a href="{{ $productPageUrl }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 break-all">
                                {{ $productPageUrl }}
                            </a>
                        </div>
                    @else
                        <p class="text-gray-500 italic">
                            <i class="fas fa-info-circle mr-2"></i>
                            製品ページのURLが設定されていません。編集ボタンから設定してください。<br>
                            <span class="text-xs">（未設定の場合は、デフォルトで https://www.dschatbot.ai/ にリンクします）</span>
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* 利用規約表示用スタイル - HTML表示（RichEditor出力対応） */
.terms-html-content {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
    font-size: 14px;
    line-height: 1.8;
    color: #374151;
    word-wrap: break-word;
}
.terms-html-content p {
    margin-bottom: 1em;
}
.terms-html-content h1, .terms-html-content h2, .terms-html-content h3, .terms-html-content h4 {
    font-weight: bold;
    margin-top: 1.5em;
    margin-bottom: 0.5em;
    color: #1f2937;
}
.terms-html-content h1 { font-size: 1.5em; }
.terms-html-content h2 { font-size: 1.3em; }
.terms-html-content h3 { font-size: 1.15em; }
.terms-html-content ul, .terms-html-content ol {
    margin-left: 1.5em;
    margin-bottom: 1em;
}
.terms-html-content ul { list-style-type: disc; }
.terms-html-content ol { list-style-type: decimal; }
.terms-html-content li { margin-bottom: 0.25em; }
.terms-html-content blockquote {
    border-left: 4px solid #d1d5db;
    padding-left: 1em;
    margin: 1em 0;
    color: #6b7280;
    font-style: italic;
}
.terms-html-content a {
    color: #4f46e5;
    text-decoration: underline;
}
.terms-html-content strong, .terms-html-content b {
    font-weight: bold;
}
.terms-html-content em, .terms-html-content i {
    font-style: italic;
}
.terms-html-content u {
    text-decoration: underline;
}
.terms-html-content s {
    text-decoration: line-through;
}
.terms-html-content pre, .terms-html-content code {
    background-color: #f3f4f6;
    padding: 0.2em 0.4em;
    border-radius: 0.25em;
    font-family: monospace;
    font-size: 0.9em;
}
.terms-html-content pre {
    padding: 1em;
    overflow-x: auto;
    margin: 1em 0;
}
.terms-html-content table {
    width: 100%;
    border-collapse: collapse;
    margin: 1em 0;
}
.terms-html-content th, .terms-html-content td {
    border: 1px solid #d1d5db;
    padding: 0.5em;
    text-align: left;
}
.terms-html-content th {
    background-color: #f3f4f6;
    font-weight: bold;
}
</style>
@endpush
