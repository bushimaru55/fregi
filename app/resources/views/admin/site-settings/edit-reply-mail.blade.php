@extends('layouts.admin')

@section('title', '返信メール設定')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-800">
            <i class="fas fa-envelope-open-text theme-price mr-3"></i>返信メール設定
        </h2>
        <p class="text-gray-600 mt-2">申込完了時に申込者のメールアドレスに送信される返信メールの内容を設定します</p>
    </div>

    <script type="application/json" id="reply-mail-initial">{!! json_encode(['header' => old('reply_mail_header', $replyMailHeader), 'footer' => old('reply_mail_footer', $replyMailFooter)]) !!}</script>
    <!-- Form Card -->
    <div class="bg-white rounded-xl card-shadow p-8">
        <form action="{{ route('admin.site-settings.reply-mail.update') }}" method="POST">
            @csrf
            @method('PUT')

            <!-- 説明 -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <h4 class="font-semibold text-blue-800 mb-2">
                    <i class="fas fa-info-circle mr-2"></i>返信メールの構成
                </h4>
                <p class="text-blue-700 text-sm">
                    返信メールは以下の順序で構成されます：
                </p>
                <ol class="list-decimal list-inside text-blue-700 text-sm mt-2 space-y-1">
                    <li><strong>上部文章</strong> - 挨拶や案内など</li>
                    <li><strong>申込内容</strong> - 自動的に挿入されます（会社名、プラン、金額など）</li>
                    <li><strong>下部文章</strong> - 署名や連絡先など</li>
                </ol>
            </div>

            <div class="space-y-6">
                <!-- 上部文章 -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-arrow-up theme-price mr-2"></i>上部文章
                    </label>
                    <p class="text-xs text-gray-500 mb-2">
                        メール本文の冒頭に表示される文章を入力してください。（挨拶や案内文など）
                    </p>
                    <textarea name="reply_mail_header" 
                              id="reply_mail_header"
                              rows="8"
                              style="white-space: pre-wrap;"
                              placeholder="例:&#10;この度は、DSchatbotサービスへのお申し込み、誠にありがとうございます。&#10;&#10;以下の内容でお申し込みを受け付けました。&#10;ご確認ください。"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg theme-input @error('reply_mail_header') border-red-500 @enderror"></textarea>
                    @error('reply_mail_header')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 申込内容（プレビュー） -->
                <div class="bg-gray-100 rounded-lg p-4 border border-gray-300">
                    <div class="flex items-center justify-center text-gray-600">
                        <i class="fas fa-file-alt mr-2"></i>
                        <span class="font-semibold">【ここに申込内容が自動で挿入されます】</span>
                    </div>
                    <p class="text-xs text-gray-500 text-center mt-2">
                        会社名・担当者名・プラン名・料金などの申込内容
                    </p>
                </div>

                <!-- 下部文章 -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-arrow-down theme-price mr-2"></i>下部文章
                    </label>
                    <p class="text-xs text-gray-500 mb-2">
                        メール本文の末尾に表示される文章を入力してください。（署名や連絡先など）
                    </p>
                    <textarea name="reply_mail_footer" 
                              id="reply_mail_footer"
                              rows="8"
                              style="white-space: pre-wrap;"
                              placeholder="例:&#10;ご不明な点がございましたら、下記までお問い合わせください。&#10;&#10;-------------------------------------------&#10;株式会社○○○&#10;DSchatbot サポートチーム&#10;Email: support@example.com&#10;Tel: 03-1234-5678&#10;-------------------------------------------"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg theme-input @error('reply_mail_footer') border-red-500 @enderror"></textarea>
                    @error('reply_mail_footer')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Success Message -->
                @if(session('success'))
                    <div class="theme-alert-success rounded-lg p-4">
                        <p class="text-green-800">
                            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                        </p>
                    </div>
                @endif

                <!-- Error Message -->
                @if($errors->has('error'))
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <p class="text-red-800">
                            <i class="fas fa-exclamation-circle mr-2"></i>{{ $errors->first('error') }}
                        </p>
                    </div>
                @endif
            </div>

            <div class="mt-8 flex justify-end space-x-4">
                <a href="{{ route('admin.site-settings.index') }}" 
                   class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                    <i class="fas fa-times mr-2"></i>キャンセル
                </a>
                <button type="submit" 
                        class="theme-btn-primary inline-block px-6 py-3 rounded-lg hover:opacity-90 transition shadow-lg no-underline border-0 cursor-pointer">
                    <i class="fas fa-save mr-2"></i>更新する
                </button>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
(function(){
    var el = document.getElementById('reply-mail-initial');
    if (!el) return;
    try {
        var d = JSON.parse(el.textContent);
        var h = document.getElementById('reply_mail_header');
        var f = document.getElementById('reply_mail_footer');
        if (h && d.header != null) h.value = d.header;
        if (f && d.footer != null) f.value = d.footer;
    } catch(e){}
})();
</script>
@endpush
@endsection
