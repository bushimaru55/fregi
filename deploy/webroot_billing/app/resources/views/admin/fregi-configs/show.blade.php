@extends('layouts.admin')

@section('title', 'F-REGI設定詳細')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h2 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-info-circle text-indigo-600 mr-3"></i>F-REGI設定詳細
            </h2>
            <p class="text-gray-600 mt-2">設定情報の確認</p>
        </div>
        <a href="{{ route('admin.fregi-configs.edit', $fregiConfig) }}" 
           class="gradient-bg text-white px-6 py-3 rounded-lg hover:opacity-90 transition shadow-lg">
            <i class="fas fa-edit mr-2"></i>編集する
        </a>
    </div>

    <!-- Details Card -->
    <div class="bg-white rounded-xl card-shadow overflow-hidden">
        <!-- Status Banner -->
        <div class="p-6 {{ $fregiConfig->is_active ? 'bg-gradient-to-r from-green-50 to-emerald-50' : 'bg-gray-50' }}">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-2xl font-bold text-gray-800">
                        {{ $fregiConfig->environment === 'prod' ? '本番環境' : 'テスト環境' }}
                    </h3>
                    <p class="text-sm text-gray-600 mt-1">ID: {{ $fregiConfig->id }}</p>
                </div>
                @if($fregiConfig->is_active)
                    <span class="bg-green-500 text-white px-4 py-2 rounded-full font-semibold">
                        <i class="fas fa-check-circle mr-1"></i>有効
                    </span>
                @else
                    <span class="bg-gray-400 text-white px-4 py-2 rounded-full">
                        無効
                    </span>
                @endif
            </div>
        </div>

        <!-- Details Grid -->
        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-6">
                    <div>
                        <label class="text-sm font-semibold text-gray-500 block mb-2">
                            <i class="fas fa-building text-indigo-500 mr-2"></i>会社ID
                        </label>
                        <p class="text-lg text-gray-800">{{ $fregiConfig->company_id }}</p>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-gray-500 block mb-2">
                            <i class="fas fa-store text-indigo-500 mr-2"></i>SHOP ID
                        </label>
                        <p class="text-lg text-gray-800">{{ $fregiConfig->shopid }}</p>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-gray-500 block mb-2">
                            <i class="fas fa-key text-indigo-500 mr-2"></i>接続パスワード
                        </label>
                        <p class="text-lg text-gray-500">********（暗号化済み）</p>
                    </div>
                </div>

                <div class="space-y-6">
                    <div>
                        <label class="text-sm font-semibold text-gray-500 block mb-2">
                            <i class="fas fa-calendar text-indigo-500 mr-2"></i>作成日時
                        </label>
                        <p class="text-lg text-gray-800">{{ $fregiConfig->created_at->format('Y年m月d日 H:i') }}</p>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-gray-500 block mb-2">
                            <i class="fas fa-clock text-indigo-500 mr-2"></i>更新日時
                        </label>
                        <p class="text-lg text-gray-800">{{ $fregiConfig->updated_at->format('Y年m月d日 H:i') }}</p>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-gray-500 block mb-2">
                            <i class="fas fa-user text-indigo-500 mr-2"></i>更新者
                        </label>
                        <p class="text-lg text-gray-800">{{ $fregiConfig->updated_by ?? '未設定' }}</p>
                    </div>
                </div>
            </div>

            <!-- URLs Section -->
            <div class="mt-8 pt-8 border-t">
                <h4 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-link text-indigo-600 mr-2"></i>URL設定
                </h4>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-semibold text-gray-500 block mb-1">
                            <i class="fas fa-bell text-indigo-500 mr-2"></i>通知URL
                        </label>
                        <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded">{{ $fregiConfig->notify_url }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-500 block mb-1">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>成功時戻りURL
                        </label>
                        <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded">{{ $fregiConfig->return_url_success }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-500 block mb-1">
                            <i class="fas fa-times-circle text-yellow-500 mr-2"></i>キャンセル時戻りURL
                        </label>
                        <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded">{{ $fregiConfig->return_url_cancel }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Back Button -->
    <div class="mt-6">
        <a href="{{ route('admin.fregi-configs.index') }}" 
           class="text-indigo-600 hover:text-indigo-800 font-semibold">
            <i class="fas fa-arrow-left mr-2"></i>一覧に戻る
        </a>
    </div>
</div>
@endsection

