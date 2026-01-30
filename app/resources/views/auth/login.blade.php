@extends('layouts.public')

@section('title', 'ログイン')

@section('content')
<div class="max-w-md mx-auto">
    <div class="bg-white shadow-lg rounded-lg p-8">
        <div class="text-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">管理者ログイン</h1>
            <p class="text-gray-600">システム管理画面へのログイン</p>
        </div>

        <!-- Session Status -->
        @if (session('status'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <!-- Email Address -->
            <div class="mb-4">
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                    メールアドレス
                </label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('email') border-red-500 @enderror">
                @error('email')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div class="mb-4">
                <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                    パスワード
                </label>
                <input id="password" type="password" name="password" required autocomplete="current-password"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('password') border-red-500 @enderror">
                @error('password')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Remember Me -->
            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="remember" class="rounded border-gray-300 theme-checkbox-accent shadow-sm">
                    <span class="ml-2 text-sm text-gray-600">ログイン状態を保持する</span>
                </label>
            </div>

            <div class="flex items-center justify-between">
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-sm theme-link">
                        パスワードを忘れた方
                    </a>
                @endif

                <button type="submit" class="btn-cta px-6 py-2 font-bold rounded-lg shadow-md transition duration-300">
                    <i class="fas fa-sign-in-alt mr-2"></i>ログイン
                </button>
            </div>
        </form>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600 mb-2">
                <strong>管理者アカウント:</strong>
            </p>
            <p class="text-xs text-gray-500">
                kanri@dschatbot.ai / cs20051101<br>
                dsbrand@example.com / cs20051101
            </p>
        </div>
    </div>
</div>
@endsection
