@extends('layouts.admin')

@section('title', '送信先メールアドレス編集')

@section('content')
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-3"></i>
                <p>{{ session('success') }}</p>
            </div>
        </div>
    @endif
    @if($errors->has('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-3"></i>
                <p>{{ $errors->first('error') }}</p>
            </div>
        </div>
    @endif

    <div class="mb-6">
        <a href="{{ route('admin.users.index') }}" class="theme-link font-semibold">
            <i class="fas fa-arrow-left mr-2"></i>管理者管理に戻る
        </a>
    </div>

    <h1 class="text-3xl font-bold text-gray-800 mb-6">送信先メールアドレス編集</h1>

    <div class="bg-white shadow-lg rounded-lg p-6">
        {{-- 更新フォーム --}}
        <form id="notification-email-form" action="{{ route('admin.users.update-notification-email') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <div>
                    <label for="notification_email" class="block text-sm font-semibold text-gray-700 mb-2">
                        送信先メールアドレス <span class="text-red-500">*</span>
                    </label>
                    <textarea name="notification_email" id="notification_email" rows="6"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input focus:outline-none focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary)] @error('notification_email') border-red-500 @enderror"
                        placeholder="例：&#10;admin@example.com&#10;notify@example.com">{{ old('notification_email', $notificationEmail) }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">1行に1件ずつ入力。複数件設定すると、申込受付時に全てのアドレスに通知メールが送信されます。カンマ区切りも利用できます。</p>
                    @error('notification_email')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </form>

        {{-- ボタン領域（フォームの外） --}}
        <div class="flex justify-between items-center mt-6">
            <div>
                {{-- 送信テストフォーム（独立） --}}
                <form action="{{ route('admin.users.send-test-notification-email') }}" method="POST" class="inline inline-confirm-form" data-confirm="登録済みの送信先にテストメールを送信します。よろしいですか？">
                    @csrf
                    <button type="submit" class="btn-primary px-6 py-2 font-bold rounded-lg shadow-md transition duration-300">
                        <i class="fas fa-paper-plane mr-2"></i>送信テスト
                    </button>
                </form>
                <span class="text-sm text-gray-500 ml-2">※先に「更新」でアドレスを保存してから実行してください。メールが届かない場合は .env の MAIL_MAILER（smtp 推奨）と SMTP 設定をご確認ください。</span>
            </div>
            <div>
                <a href="{{ route('admin.users.index') }}" class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white font-bold rounded-lg shadow-md transition duration-300 mr-2">
                    キャンセル
                </a>
                {{-- 更新ボタン（form属性で外部フォームを参照） --}}
                <button type="submit" form="notification-email-form" class="btn-cta px-6 py-2 font-bold rounded-lg shadow-md transition duration-300">
                    更新
                </button>
            </div>
        </div>
    </div>
@endsection
