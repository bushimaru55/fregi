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
                @php
                    $defaultTermsTab = 'one_time';
                    $requestedTab = request()->query('billing_selection');
                    if (is_string($requestedTab) && array_key_exists($requestedTab, $billingSelectionLabels)) {
                        $defaultTermsTab = $requestedTab;
                    } else {
                        foreach ($billingSelectionLabels as $sel => $lbl) {
                            if (!empty($termsBySelection[$sel] ?? '')) {
                                $defaultTermsTab = $sel;
                                break;
                            }
                        }
                    }
                @endphp
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-file-contract theme-price mr-2"></i>利用規約
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">決済タイプごとの利用規約を管理します。申込フォームでは選択製品の決済タイプに応じて表示されます</p>
                    </div>
                    <a href="{{ route('admin.site-settings.edit', ['billing_selection' => $defaultTermsTab]) }}" 
                       id="terms-edit-link"
                       class="theme-btn-primary inline-block px-6 py-3 rounded-lg hover:opacity-90 transition shadow-lg no-underline">
                        <i class="fas fa-edit mr-2"></i>編集
                    </a>
                </div>

                <div id="terms-preview-tabs">
                    <nav class="mb-3 flex flex-wrap gap-1 border-b border-gray-200" aria-label="決済タイプ">
                        @foreach($billingSelectionLabels as $selection => $label)
                            <button type="button"
                                    data-terms-tab="{{ $selection }}"
                                    class="terms-tab-btn px-3 py-2 text-sm font-medium border-b-2 transition whitespace-nowrap
                                        {{ $selection === $defaultTermsTab
                                            ? 'border-indigo-600 text-indigo-700'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </nav>

                    @foreach($billingSelectionLabels as $selection => $label)
                        <div data-terms-panel="{{ $selection }}"
                             class="terms-tab-panel bg-gray-50 rounded-lg p-4 border border-gray-200"
                             style="{{ $selection === $defaultTermsTab ? '' : 'display: none;' }}">
                            @if(!empty($termsBySelection[$selection] ?? ''))
                                <div class="prose prose-sm max-w-none terms-html-content">
                                    {!! $termsBySelection[$selection] !!}
                                </div>
                            @else
                                <p class="text-gray-500 italic">「{{ $label }}」の利用規約が設定されていません。編集ボタンから設定してください。</p>
                            @endif
                        </div>
                    @endforeach
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

            <!-- 請求サイクル設定 -->
            <div class="border-t border-gray-200 pt-6 mt-6">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-calendar-alt theme-price mr-2"></i>請求サイクル
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">月末5営業日ルールに基づく請求書発行日・送付日・決済期限の設定（請求管理ロボ API 用）</p>
                    </div>
                    <a href="{{ route('admin.site-settings.billing-cycle.edit') }}"
                       class="theme-btn-primary inline-block px-6 py-3 rounded-lg hover:opacity-90 transition shadow-lg no-underline">
                        <i class="fas fa-edit mr-2"></i>編集
                    </a>
                </div>
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <p class="text-gray-600 text-sm">
                        申込日が「月末5営業日以内」か「以降」かで、発行日・送付日・決済期限の月/日を切り替えます。編集ボタンから各パターンの値を設定してください。
                    </p>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    var root = document.getElementById('terms-preview-tabs');
    if (!root) return;

    var buttons = root.querySelectorAll('.terms-tab-btn');
    var panels = root.querySelectorAll('.terms-tab-panel');
    var editLink = document.getElementById('terms-edit-link');
    var editBaseUrl = @json(route('admin.site-settings.edit'));

    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = btn.getAttribute('data-terms-tab');
            buttons.forEach(function (b) {
                var active = b.getAttribute('data-terms-tab') === target;
                b.classList.toggle('border-indigo-600', active);
                b.classList.toggle('text-indigo-700', active);
                b.classList.toggle('border-transparent', !active);
                b.classList.toggle('text-gray-500', !active);
            });
            panels.forEach(function (panel) {
                panel.style.display = panel.getAttribute('data-terms-panel') === target ? '' : 'none';
            });
            if (editLink) {
                editLink.href = editBaseUrl + '?billing_selection=' + encodeURIComponent(target);
            }
        });
    });
});
</script>
@endpush

