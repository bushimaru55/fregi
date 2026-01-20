@extends('layouts.admin')

@section('title', 'F-REGI設定編集')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-800">
            <i class="fas fa-edit text-indigo-600 mr-3"></i>F-REGI設定編集
        </h2>
        <p class="text-gray-600 mt-2">設定内容を更新します</p>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-xl card-shadow p-8">
        <form action="{{ route('admin.fregi-configs.update') }}" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="company_id" value="1">

            <div class="space-y-6">

                <!-- Environment -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-server text-indigo-500 mr-2"></i>環境
                    </label>
                    <select name="environment" 
                            class="native-select w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            required>
                        <option value="test" {{ old('environment', $config->environment) === 'test' ? 'selected' : '' }}>テスト環境</option>
                        <option value="prod" {{ old('environment', $config->environment) === 'prod' ? 'selected' : '' }}>本番環境</option>
                    </select>
                </div>

                <!-- Shop ID -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-store text-indigo-500 mr-2"></i>SHOP ID
                    </label>
                    <input type="text" name="shopid" value="{{ old('shopid', $config->shopid) }}" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           required>
                </div>

                <!-- Connect Password -->
                <div>
                    @php
                        $isFirstTime = empty($config->connect_password_enc);
                    @endphp
                    @if($isFirstTime)
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-key text-indigo-500 mr-2"></i>接続パスワード <span class="text-red-500">*</span>
                        </label>
                        <input type="password" name="connect_password" id="connect_password"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               required>
                        <p class="text-xs text-gray-500 mt-1">※ 初回設定のため、接続パスワードは必須です</p>
                    @else
                        <div class="flex items-center mb-2">
                            <input type="checkbox" name="change_password" id="change_password" value="1"
                                   class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                            <label for="change_password" class="ml-3 text-sm font-semibold text-gray-700">
                                <i class="fas fa-key text-indigo-500 mr-2"></i>接続パスワードを変更する
                            </label>
                        </div>
                        <input type="password" name="connect_password" id="connect_password"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               disabled>
                        <p class="text-xs text-gray-500 mt-1">※ 変更する場合はチェックボックスをONにしてください</p>
                    @endif
                </div>

                <!-- Notify URL -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-bell text-indigo-500 mr-2"></i>通知URL
                    </label>
                    <input type="url" name="notify_url" value="{{ old('notify_url', $config->notify_url) }}" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           required>
                </div>

                <!-- Return URL Success -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>成功時戻りURL
                    </label>
                    <input type="url" name="return_url_success" value="{{ old('return_url_success', $config->return_url_success) }}" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           required>
                </div>

                <!-- Return URL Cancel -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-times-circle text-yellow-500 mr-2"></i>キャンセル時戻りURL
                    </label>
                    <input type="url" name="return_url_cancel" value="{{ old('return_url_cancel', $config->return_url_cancel) }}" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           required>
                </div>

                <!-- Is Active -->
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1" 
                           {{ old('is_active', $config->is_active) ? 'checked' : '' }}
                           class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                    <label for="is_active" class="ml-3 text-sm font-semibold text-gray-700">
                        <i class="fas fa-toggle-on text-green-500 mr-2"></i>この設定を有効にする
                    </label>
                </div>

                <!-- Change Reason -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-comment text-indigo-500 mr-2"></i>変更理由（任意）
                    </label>
                    <textarea name="change_reason" rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">{{ old('change_reason') }}</textarea>
                </div>
            </div>

            <!-- Buttons -->
            <div class="mt-8 flex justify-end space-x-4">
                <a href="{{ route('admin.fregi-configs.show') }}" 
                   class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                    <i class="fas fa-times mr-2"></i>キャンセル
                </a>
                <button type="submit" 
                        class="gradient-bg text-white px-6 py-3 rounded-lg hover:opacity-90 transition shadow-lg">
                    <i class="fas fa-save mr-2"></i>更新する
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('change_password').addEventListener('change', function() {
        const passwordInput = document.getElementById('connect_password');
        passwordInput.disabled = !this.checked;
        if (!this.checked) {
            passwordInput.value = '';
        }
    });
</script>
@endsection

