@extends('layouts.public')

@section('title', 'トップページ')

@section('content')
<div class="text-center">
    <div class="mb-12">
        <h1 class="text-5xl font-bold text-gray-800 mb-4">Billing System</h1>
        <p class="text-xl text-gray-600">F-REGI決済連携システム</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
        {{-- 新規申込 --}}
        <a href="{{ route('contract.create') }}" class="bg-gradient-to-br from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white rounded-lg shadow-lg p-8 transform hover:scale-105 transition duration-300">
            <div class="text-6xl mb-4">
                <i class="fas fa-file-signature"></i>
            </div>
            <h2 class="text-2xl font-bold mb-2">新規申込</h2>
            <p class="text-indigo-100">サービスの新規お申し込みはこちら</p>
        </a>

        {{-- 管理画面: ダッシュボード（ログイン済みのみ） --}}
        @auth
        <a href="{{ route('admin.dashboard') }}" class="bg-gradient-to-br from-green-500 to-teal-600 hover:from-green-600 hover:to-teal-700 text-white rounded-lg shadow-lg p-8 transform hover:scale-105 transition duration-300">
            <div class="text-6xl mb-4">
                <i class="fas fa-tachometer-alt"></i>
            </div>
            <h2 class="text-2xl font-bold mb-2">ダッシュボード</h2>
            <p class="text-green-100">管理画面トップ</p>
        </a>
        @else
        <a href="{{ route('login') }}" class="bg-gradient-to-br from-green-500 to-teal-600 hover:from-green-600 hover:to-teal-700 text-white rounded-lg shadow-lg p-8 transform hover:scale-105 transition duration-300">
            <div class="text-6xl mb-4">
                <i class="fas fa-sign-in-alt"></i>
            </div>
            <h2 class="text-2xl font-bold mb-2">管理者ログイン</h2>
            <p class="text-green-100">管理画面へのログイン</p>
        </a>
        @endauth

        {{-- お問い合わせ --}}
        <a href="#" class="bg-gradient-to-br from-blue-500 to-cyan-600 hover:from-blue-600 hover:to-cyan-700 text-white rounded-lg shadow-lg p-8 transform hover:scale-105 transition duration-300">
            <div class="text-6xl mb-4">
                <i class="fas fa-envelope"></i>
            </div>
            <h2 class="text-2xl font-bold mb-2">お問い合わせ</h2>
            <p class="text-blue-100">ご不明点はこちら</p>
        </a>
    </div>

    <div class="mt-16 max-w-4xl mx-auto">
        <div class="bg-white shadow-lg rounded-lg p-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-4">システム概要</h3>
            <div class="text-left space-y-4 text-gray-700">
                <p><i class="fas fa-check-circle text-green-500 mr-2"></i>F-REGI決済システムとの連携</p>
                <p><i class="fas fa-check-circle text-green-500 mr-2"></i>契約プラン（学習ページ数 50〜300）</p>
                <p><i class="fas fa-check-circle text-green-500 mr-2"></i>安全な決済処理（クレジットカード）</p>
                <p><i class="fas fa-check-circle text-green-500 mr-2"></i>契約・決済履歴の一元管理</p>
                <p><i class="fas fa-check-circle text-green-500 mr-2"></i>暗号化された設定情報の保存</p>
            </div>
        </div>
    </div>
</div>
@endsection
