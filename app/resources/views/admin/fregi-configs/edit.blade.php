@extends('layouts.admin')

@section('title', 'F-REGI設定編集')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-3xl font-bold text-gray-800">
                    <i class="fas fa-edit text-indigo-600 mr-3"></i>F-REGI設定編集
                </h2>
                <p class="text-gray-600 mt-2">設定内容を更新します</p>
            </div>
            <a href="{{ route('admin.fregi-configs.index') }}" 
               class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                <i class="fas fa-list mr-2"></i>一覧に戻る
            </a>
        </div>
    </div>

    <!-- 環境切り替えスイッチ -->
    <div class="mb-6 bg-white rounded-xl card-shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-1">
                    <i class="fas fa-toggle-on text-indigo-600 mr-2"></i>使用環境の切り替え
                </h3>
                <p class="text-sm text-gray-600">現在使用する環境を選択してください</p>
            </div>
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2">
                    <span class="text-sm font-medium text-gray-700 {{ (!isset($activeEnvironment) || $activeEnvironment === 'test') ? 'text-indigo-600 font-bold' : '' }}">
                        <i class="fas fa-flask mr-1"></i>テスト環境
                    </span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" 
                               id="environment-switch" 
                               class="sr-only peer"
                               {{ (isset($activeEnvironment) && $activeEnvironment === 'prod') ? 'checked' : '' }}
                               onchange="switchEnvironment(this)">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                    <span class="text-sm font-medium text-gray-700 {{ (isset($activeEnvironment) && $activeEnvironment === 'prod') ? 'text-indigo-600 font-bold' : '' }}">
                        <i class="fas fa-server mr-1"></i>本番環境
                    </span>
                </div>
            </div>
        </div>
        @if(isset($activeEnvironment))
            <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded">
                <p class="text-sm text-blue-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>現在有効な環境:</strong> 
                    <span class="font-bold">{{ $activeEnvironment === 'prod' ? '本番環境' : 'テスト環境' }}</span>
                </p>
            </div>
        @endif
    </div>

    <!-- 環境切り替えタブ -->
    <div class="mb-6">
        <div class="flex space-x-2 border-b border-gray-200">
            <a href="{{ route('admin.fregi-configs.edit', ['environment' => 'test']) }}" 
               class="px-6 py-3 font-semibold {{ $config->environment === 'test' ? 'border-b-2 border-indigo-500 text-indigo-600' : 'text-gray-600 hover:text-gray-800' }}">
                <i class="fas fa-flask mr-2"></i>テスト環境
            </a>
            <a href="{{ route('admin.fregi-configs.edit', ['environment' => 'prod']) }}" 
               class="px-6 py-3 font-semibold {{ $config->environment === 'prod' ? 'border-b-2 border-indigo-500 text-indigo-600' : 'text-gray-600 hover:text-gray-800' }}">
                <i class="fas fa-server mr-2"></i>本番環境
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-xl card-shadow p-8">
        <form action="{{ route('admin.fregi-configs.update') }}" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="company_id" value="1">

            <div class="space-y-6">

                <!-- Environment (読み取り専用) -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-server text-indigo-500 mr-2"></i>環境
                    </label>
                    <input type="hidden" name="environment" value="{{ $config->environment }}">
                    <div class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-700">
                        {{ $config->environment === 'prod' ? '本番環境' : 'テスト環境' }}
                    </div>
                    <p class="text-xs text-gray-500 mt-1">※ 環境を変更する場合は、上部のタブから切り替えてください</p>
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

                <!-- Is Active (非表示: スイッチで制御) -->
                <!-- 注意: スイッチで環境を切り替えるため、フォーム内のis_activeは使用しない -->

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
    // パスワード変更チェックボックスの処理
    const changePasswordCheckbox = document.getElementById('change_password');
    if (changePasswordCheckbox) {
        changePasswordCheckbox.addEventListener('change', function() {
            const passwordInput = document.getElementById('connect_password');
            passwordInput.disabled = !this.checked;
            if (!this.checked) {
                passwordInput.value = '';
            }
        });
    }

    // 環境切り替えスイッチの処理
    function switchEnvironment(checkbox) {
        const targetEnvironment = checkbox.checked ? 'prod' : 'test';
        const currentEnvironment = '{{ $config->environment }}';
        
        // 確認ダイアログ
        const envName = targetEnvironment === 'prod' ? '本番環境' : 'テスト環境';
        if (!confirm(`${envName}に切り替えますか？\n他の環境の設定は自動的に無効になります。`)) {
            // キャンセルされた場合はスイッチを元に戻す
            checkbox.checked = !checkbox.checked;
            return;
        }

        // 環境切り替えAPIを呼び出す
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("admin.fregi-configs.switch", ["environment" => ":env"]) }}'.replace(':env', targetEnvironment);
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);

        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'PUT';
        form.appendChild(methodInput);

        document.body.appendChild(form);
        form.submit();
    }

    // ページ読み込み時にスイッチの状態を設定
    document.addEventListener('DOMContentLoaded', function() {
        const activeEnvironment = '{{ isset($activeEnvironment) ? $activeEnvironment : "test" }}';
        const switchCheckbox = document.getElementById('environment-switch');
        if (switchCheckbox) {
            switchCheckbox.checked = activeEnvironment === 'prod';
        }
    });
</script>
@endsection

