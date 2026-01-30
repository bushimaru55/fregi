@extends('layouts.admin')

@section('title', 'サイト管理')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-800">
            <i class="fas fa-cog theme-price mr-3"></i>サイト管理
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
                            <i class="fas fa-file-contract theme-price mr-2"></i>利用規約
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">新規申込フォームに表示される利用規約の内容を管理します</p>
                    </div>
                    <a href="{{ route('admin.site-settings.edit') }}" 
                       class="theme-btn-primary inline-block px-6 py-3 rounded-lg hover:opacity-90 transition shadow-lg no-underline">
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
                            <i class="fas fa-home theme-price mr-2"></i>トップページのURL
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">決済完了画面の「トップへ戻る」ボタンのリンク先URLを設定します</p>
                    </div>
                    <a href="{{ route('admin.site-settings.top-page-url.edit') }}" 
                       class="theme-btn-primary inline-block px-6 py-3 rounded-lg hover:opacity-90 transition shadow-lg no-underline">
                        <i class="fas fa-edit mr-2"></i>編集
                    </a>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    @if($topPageUrl)
                        <div class="flex items-center">
                            <i class="fas fa-link theme-price mr-2"></i>
                            <a href="{{ $topPageUrl }}" target="_blank" class="theme-link break-all">
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
            <div class="border-b border-gray-200 pb-6 pt-6">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-globe theme-price mr-2"></i>製品ページのURL
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">公開ページヘッダーの「製品ページへ戻る」ボタンのリンク先URLを設定します</p>
                    </div>
                    <a href="{{ route('admin.site-settings.product-page-url.edit') }}" 
                       class="theme-btn-primary inline-block px-6 py-3 rounded-lg hover:opacity-90 transition shadow-lg no-underline">
                        <i class="fas fa-edit mr-2"></i>編集
                    </a>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    @if($productPageUrl)
                        <div class="flex items-center">
                            <i class="fas fa-link theme-price mr-2"></i>
                            <a href="{{ $productPageUrl }}" target="_blank" class="theme-link break-all">
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

            <!-- Reply Mail Settings Section -->
            <div class="pt-6">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-envelope-open-text theme-price mr-2"></i>返信メール設定
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">申込完了時に申込者のメールアドレスに送信される返信メールの内容を設定します</p>
                    </div>
                    <a href="{{ route('admin.site-settings.reply-mail.edit') }}" 
                       class="theme-btn-primary inline-block px-6 py-3 rounded-lg hover:opacity-90 transition shadow-lg no-underline">
                        <i class="fas fa-edit mr-2"></i>編集
                    </a>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 space-y-4">
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-2">
                            <i class="fas fa-arrow-up theme-price mr-1"></i>上部文章
                        </h4>
                        @if($replyMailHeader)
                            <div class="bg-white rounded p-3 border border-gray-200 whitespace-pre-wrap text-sm">{{ $replyMailHeader }}</div>
                        @else
                            <p class="text-gray-500 italic text-sm">
                                <i class="fas fa-info-circle mr-1"></i>
                                上部文章が設定されていません。
                            </p>
                        @endif
                    </div>
                    
                    <div class="bg-blue-50 rounded p-3 border border-blue-200 text-center">
                        <i class="fas fa-file-alt text-blue-500 mr-1"></i>
                        <span class="text-blue-700 text-sm">【ここに申込内容が表示されます】</span>
                    </div>
                    
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-2">
                            <i class="fas fa-arrow-down theme-price mr-1"></i>下部文章
                        </h4>
                        @if($replyMailFooter)
                            <div class="bg-white rounded p-3 border border-gray-200 whitespace-pre-wrap text-sm">{{ $replyMailFooter }}</div>
                        @else
                            <p class="text-gray-500 italic text-sm">
                                <i class="fas fa-info-circle mr-1"></i>
                                下部文章が設定されていません。
                            </p>
                        @endif
                    </div>
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
    color: var(--color-text);
    word-wrap: break-word;
}
.terms-html-content p {
    margin-bottom: 1em;
}
.terms-html-content h1, .terms-html-content h2, .terms-html-content h3, .terms-html-content h4 {
    font-weight: bold;
    margin-top: 1.5em;
    margin-bottom: 0.5em;
    color: var(--color-text);
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
    border-left: 4px solid var(--color-border);
    padding-left: 1em;
    margin: 1em 0;
    color: var(--color-text-muted);
    font-style: italic;
}
.terms-html-content a {
    color: var(--color-primary);
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
    background-color: var(--color-bg);
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
    border: 1px solid var(--color-border);
    padding: 0.5em;
    text-align: left;
}
.terms-html-content th {
    background-color: var(--color-bg);
    font-weight: bold;
}
</style>
@endpush

@push('scripts')
{{-- #region agent log --}}
<script>
(function() {
    function log(payload) {
        fetch('http://127.0.0.1:7244/ingest/b08cb211-1fd0-430c-99ee-57cc534497b6', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(Object.assign({timestamp: Date.now(), sessionId: 'debug-session'}, payload))
        }).catch(function(){});
    }
    document.addEventListener('DOMContentLoaded', function() {
        var btns = document.querySelectorAll('a.theme-btn-primary');
        btns.forEach(function(btn, i) {
            var cs = window.getComputedStyle(btn);
            log({
                hypothesisId: 'A',
                runId: 'post-fix',
                location: 'site-settings/index:button-check',
                message: 'theme-btn-primary button styles',
                data: {
                    index: i,
                    innerHTML: btn.innerHTML,
                    innerText: btn.innerText,
                    textContent: btn.textContent,
                    backgroundColor: cs.backgroundColor,
                    color: cs.color
                }
            });
        });
        var faTest = document.querySelector('.fa, .fas, .far, .fab');
        log({
            hypothesisId: 'B',
            location: 'site-settings/index:fontawesome-check',
            message: 'Font Awesome loaded',
            data: {
                hasFaElement: !!faTest,
                faFontFamily: faTest ? window.getComputedStyle(faTest).fontFamily : null
            }
        });
    });
})();
</script>
{{-- #endregion agent log --}}
@endpush
