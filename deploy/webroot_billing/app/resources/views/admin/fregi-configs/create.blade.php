@extends('layouts.admin')

@section('title', 'F-REGI設定新規登録')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-800">
            <i class="fas fa-plus-circle text-indigo-600 mr-3"></i>F-REGI設定新規登録
        </h2>
        <p class="text-gray-600 mt-2">決済連携に必要な情報を入力してください</p>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-xl card-shadow p-8">
        <form action="{{ route('admin.fregi-configs.store') }}" method="POST">
            @csrf

            <div class="space-y-6">
                <!-- Company ID -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-building text-indigo-500 mr-2"></i>会社ID
                    </label>
                    <input type="number" name="company_id" value="{{ old('company_id', 1) }}" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           required>
                </div>

                <!-- Environment -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-server text-indigo-500 mr-2"></i>環境
                    </label>
                    <select name="environment" 
                            class="native-select w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            required>
                        <option value="test" {{ old('environment') === 'test' ? 'selected' : '' }}>テスト環境</option>
                        <option value="prod" {{ old('environment') === 'prod' ? 'selected' : '' }}>本番環境</option>
                    </select>
                </div>

                <!-- Shop ID -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-store text-indigo-500 mr-2"></i>SHOP ID
                    </label>
                    <input type="text" name="shopid" value="{{ old('shopid') }}" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           required>
                </div>

                <!-- Connect Password -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-key text-indigo-500 mr-2"></i>接続パスワード
                    </label>
                    <input type="password" name="connect_password" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           required>
                    <p class="text-xs text-gray-500 mt-1">※ 暗号化して保存されます</p>
                </div>

                <!-- Notify URL -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-bell text-indigo-500 mr-2"></i>通知URL
                    </label>
                    <input type="url" name="notify_url" value="{{ old('notify_url', url('/api/fregi/notify')) }}" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           required>
                </div>

                <!-- Return URL Success -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>成功時戻りURL
                    </label>
                    <input type="url" name="return_url_success" value="{{ old('return_url_success', url('/return/success')) }}" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           required>
                </div>

                <!-- Return URL Cancel -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-times-circle text-yellow-500 mr-2"></i>キャンセル時戻りURL
                    </label>
                    <input type="url" name="return_url_cancel" value="{{ old('return_url_cancel', url('/return/cancel')) }}" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           required>
                </div>

                <!-- Is Active -->
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1" 
                           {{ old('is_active', true) ? 'checked' : '' }}
                           class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                    <label for="is_active" class="ml-3 text-sm font-semibold text-gray-700">
                        <i class="fas fa-toggle-on text-green-500 mr-2"></i>この設定を有効にする
                    </label>
                </div>
            </div>

            <!-- Buttons -->
            <div class="mt-8 flex justify-end space-x-4">
                <a href="{{ route('admin.fregi-configs.index') }}" 
                   class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                    <i class="fas fa-times mr-2"></i>キャンセル
                </a>
                <button type="submit" 
                        class="gradient-bg text-white px-6 py-3 rounded-lg hover:opacity-90 transition shadow-lg">
                    <i class="fas fa-save mr-2"></i>登録する
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

