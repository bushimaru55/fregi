@extends('layouts.admin')

@section('title', '管理者管理')

@section('content')
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-3"></i>
                <p>{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-3"></i>
                <p>{{ session('error') }}</p>
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

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">管理者管理</h1>
        <a href="{{ route('admin.users.create') }}" class="btn-cta font-bold py-2 px-4 rounded-lg shadow-md transition duration-300">
            <i class="fas fa-plus mr-2"></i>新規作成
        </a>
    </div>

    {{-- 送信先メールアドレス設定 --}}
    <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">
                    <i class="fas fa-envelope mr-2"></i>送信先メールアドレス
                </h2>
                <p class="text-sm text-gray-600">
                    申込受付時の通知メール送信先アドレス
                </p>
                @if(count($notificationEmails) > 0)
                    <ul class="text-gray-800 mt-2 space-y-1">
                        @foreach($notificationEmails as $email)
                            <li class="font-medium">{{ $email }}</li>
                        @endforeach
                    </ul>
                    <p class="text-sm text-gray-500 mt-1">（{{ count($notificationEmails) }}件）</p>
                @else
                    <p class="text-sm text-yellow-600 mt-2">未設定</p>
                @endif
            </div>
            <div class="flex items-center space-x-2">
                @if(count($notificationEmails) > 0)
                    <form action="{{ route('admin.users.send-test-notification-email') }}" method="POST" class="inline inline-confirm-form" data-confirm="登録済みの送信先（{{ count($notificationEmails) }}件）にテストメールを送信します。よろしいですか？">
                        @csrf
                        <button type="submit" class="btn-primary font-bold py-2 px-4 rounded-lg shadow-md transition duration-300">
                            <i class="fas fa-paper-plane mr-2"></i>送信テスト
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.users.edit-notification-email') }}" class="btn-primary font-bold py-2 px-4 rounded-lg shadow-md transition duration-300">
                    <i class="fas fa-edit mr-2"></i>編集
                </a>
            </div>
        </div>
    </div>

    {{-- 管理者一覧 --}}
    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <div class="p-4 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-800">
                <i class="fas fa-users mr-2"></i>管理者一覧
            </h2>
        </div>
        @if($users->isEmpty())
            <div class="p-6 text-center text-gray-600">
                管理者データがありません。
            </div>
        @else
            <table class="min-w-full leading-normal">
                <thead>
                    <tr class="theme-table-header uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left">ID</th>
                        <th class="py-3 px-6 text-left">名前</th>
                        <th class="py-3 px-6 text-left">メールアドレス</th>
                        <th class="py-3 px-6 text-left">登録日時</th>
                        <th class="py-3 px-6 text-center">アクション</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 text-sm font-light">
                    @foreach ($users as $user)
                        <tr class="border-b border-gray-200 hover:bg-gray-100">
                            <td class="py-3 px-6 text-left whitespace-nowrap">{{ $user->id }}</td>
                            <td class="py-3 px-6 text-left">{{ $user->name }}</td>
                            <td class="py-3 px-6 text-left">{{ $user->email }}</td>
                            <td class="py-3 px-6 text-left">{{ $user->created_at->format('Y年m月d日 H:i') }}</td>
                            <td class="py-3 px-6 text-center">
                                <div class="flex item-center justify-center space-x-2">
                                    <a href="{{ route('admin.users.edit', $user->id) }}" class="theme-link font-semibold">
                                        <i class="fas fa-edit mr-1"></i>編集
                                    </a>
                                    @if($user->id !== auth()->id())
                                    <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="inline-block inline-confirm-form" data-confirm="本当に削除しますか？">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 font-semibold">
                                            <i class="fas fa-trash mr-1"></i>削除
                                        </button>
                                    </form>
                                    @else
                                    <span class="text-gray-400 text-sm">（現在のユーザー）</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
