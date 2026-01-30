@extends('layouts.admin')

@section('title', 'F-REGI設定一覧')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h2 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-list text-indigo-600 mr-3"></i>F-REGI設定一覧
            </h2>
            <p class="text-gray-600 mt-2">決済連携設定の管理</p>
        </div>
    </div>

    <!-- Cards Grid -->
    @if($configs->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($configs as $config)
                <div class="bg-white rounded-xl card-shadow hover:shadow-2xl transition duration-300 overflow-hidden">
                    <!-- Card Header -->
                    <div class="p-6 {{ $config->is_active ? 'bg-gradient-to-r from-green-50 to-emerald-50' : 'bg-gray-50' }}">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">
                                    {{ $config->environment === 'prod' ? '本番環境' : 'テスト環境' }}
                                </h3>
                                <p class="text-sm text-gray-600 mt-1">Company ID: {{ $config->company_id }}</p>
                            </div>
                            @if($config->is_active)
                                <span class="bg-green-500 text-white text-xs px-3 py-1 rounded-full font-semibold">
                                    <i class="fas fa-check-circle mr-1"></i>有効
                                </span>
                            @else
                                <span class="bg-gray-400 text-white text-xs px-3 py-1 rounded-full">
                                    無効
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-6">
                        <div class="space-y-3">
                            <div class="flex items-center text-sm">
                                <i class="fas fa-store text-indigo-500 w-5 mr-3"></i>
                                <span class="text-gray-600">SHOP ID:</span>
                                <span class="ml-2 font-semibold text-gray-800">{{ $config->shopid }}</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <i class="fas fa-key text-indigo-500 w-5 mr-3"></i>
                                <span class="text-gray-600">パスワード:</span>
                                <span class="ml-2 text-gray-500">********</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <i class="fas fa-calendar text-indigo-500 w-5 mr-3"></i>
                                <span class="text-gray-600">更新日:</span>
                                <span class="ml-2 text-gray-700">{{ $config->updated_at->format('Y/m/d H:i') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card Footer -->
                    <div class="px-6 py-4 bg-gray-50 border-t">
                        <div class="flex justify-between items-center">
                            <div class="flex space-x-3">
                                <a href="{{ route('admin.fregi-configs.show', ['environment' => $config->environment]) }}" 
                                   class="text-indigo-600 hover:text-indigo-800 font-semibold text-sm">
                                    <i class="fas fa-eye mr-1"></i>詳細
                                </a>
                                <a href="{{ route('admin.fregi-configs.edit', ['environment' => $config->environment]) }}" 
                                   class="text-blue-600 hover:text-blue-800 font-semibold text-sm">
                                    <i class="fas fa-edit mr-1"></i>編集
                                </a>
                            </div>
                            @if(!$config->is_active)
                                <form action="{{ route('admin.fregi-configs.switch', ['environment' => $config->environment]) }}" 
                                      method="POST" 
                                      class="inline"
                                      onsubmit="return confirm('{{ $config->environment === 'prod' ? '本番環境' : 'テスト環境' }}に切り替えますか？\n他の環境の設定は自動的に無効になります。');">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" 
                                            class="bg-green-500 hover:bg-green-600 text-white text-xs px-4 py-2 rounded-lg font-semibold transition">
                                        <i class="fas fa-toggle-on mr-1"></i>この環境を有効にする
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- Empty State -->
        <div class="bg-white rounded-xl card-shadow p-12 text-center">
            <i class="fas fa-inbox text-gray-300 text-6xl mb-4"></i>
            <h3 class="text-xl font-bold text-gray-700 mb-2">設定が登録されていません</h3>
            <p class="text-gray-500 mb-6">編集画面から設定を追加してください</p>
            <div class="flex justify-center space-x-4">
                <a href="{{ route('admin.fregi-configs.edit', ['environment' => 'test']) }}" 
                   class="inline-block gradient-bg text-white px-6 py-3 rounded-lg hover:opacity-90 transition">
                    <i class="fas fa-flask mr-2"></i>テスト環境を登録
                </a>
                <a href="{{ route('admin.fregi-configs.edit', ['environment' => 'prod']) }}" 
                   class="inline-block gradient-bg text-white px-6 py-3 rounded-lg hover:opacity-90 transition">
                    <i class="fas fa-server mr-2"></i>本番環境を登録
                </a>
            </div>
        </div>
    @endif
</div>
@endsection

