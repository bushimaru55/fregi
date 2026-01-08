@extends('layouts.admin')

@section('title', 'ダッシュボード')

@section('content')
<div class="mb-8">
    <h1 class="text-4xl font-bold text-gray-800 mb-2">ダッシュボード</h1>
    <p class="text-gray-600">ようこそ、{{ Auth::user()->name }}さん</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    {{-- 統計カード --}}
    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">契約数</p>
                <p class="text-3xl font-bold text-gray-800">{{ \App\Models\Contract::count() }}</p>
            </div>
            <div class="bg-blue-100 rounded-full p-4">
                <i class="fas fa-file-contract text-blue-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">有効契約</p>
                <p class="text-3xl font-bold text-gray-800">{{ \App\Models\Contract::where('status', 'active')->count() }}</p>
            </div>
            <div class="bg-green-100 rounded-full p-4">
                <i class="fas fa-check-circle text-green-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">契約プラン数</p>
                <p class="text-3xl font-bold text-gray-800">{{ \App\Models\ContractPlan::count() }}</p>
            </div>
            <div class="bg-purple-100 rounded-full p-4">
                <i class="fas fa-layer-group text-purple-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">決済完了</p>
                <p class="text-3xl font-bold text-gray-800">{{ \App\Models\Payment::where('status', 'paid')->count() }}</p>
            </div>
            <div class="bg-yellow-100 rounded-full p-4">
                <i class="fas fa-credit-card text-yellow-600 text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- 最近の契約 --}}
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">
            <i class="fas fa-history mr-2"></i>最近の契約
        </h2>
        <div class="space-y-3">
            @forelse(\App\Models\Contract::latest()->take(5)->get() as $contract)
                <div class="flex items-center justify-between border-b border-gray-200 pb-3">
                    <div>
                        <p class="font-semibold text-gray-800">{{ $contract->company_name }}</p>
                        <p class="text-sm text-gray-600">{{ $contract->contractPlan->name ?? 'N/A' }}</p>
                    </div>
                    <div class="text-right">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold 
                            {{ $contract->status === 'active' ? 'bg-green-200 text-green-800' : 'bg-yellow-200 text-yellow-800' }}">
                            {{ $contract->status_label }}
                        </span>
                        <p class="text-xs text-gray-500 mt-1">{{ $contract->created_at->format('Y/m/d') }}</p>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 text-center py-4">契約データがありません</p>
            @endforelse
        </div>
        <div class="mt-4">
            <a href="{{ route('admin.contracts.index') }}" class="text-indigo-600 hover:text-indigo-800 font-semibold">
                すべて表示 <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
    </div>

    {{-- クイックアクション --}}
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">
            <i class="fas fa-bolt mr-2"></i>クイックアクション
        </h2>
        <div class="grid grid-cols-2 gap-4">
            <a href="{{ route('admin.contracts.index') }}" class="bg-gradient-to-br from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-lg p-4 text-center transform hover:scale-105 transition">
                <i class="fas fa-list-alt text-3xl mb-2"></i>
                <p class="font-semibold">契約一覧</p>
            </a>
            <a href="{{ route('admin.contract-plans.index') }}" class="bg-gradient-to-br from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white rounded-lg p-4 text-center transform hover:scale-105 transition">
                <i class="fas fa-layer-group text-3xl mb-2"></i>
                <p class="font-semibold">契約プラン管理</p>
            </a>
            <a href="{{ route('admin.contract-plans.create') }}" class="bg-gradient-to-br from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-lg p-4 text-center transform hover:scale-105 transition">
                <i class="fas fa-plus text-3xl mb-2"></i>
                <p class="font-semibold">プラン新規作成</p>
            </a>
            <a href="{{ route('admin.fregi-configs.index') }}" class="bg-gradient-to-br from-cyan-500 to-cyan-600 hover:from-cyan-600 hover:to-cyan-700 text-white rounded-lg p-4 text-center transform hover:scale-105 transition">
                <i class="fas fa-cog text-3xl mb-2"></i>
                <p class="font-semibold">F-REGI設定</p>
            </a>
            <a href="{{ route('admin.site-settings.index') }}" class="bg-gradient-to-br from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white rounded-lg p-4 text-center transform hover:scale-105 transition">
                <i class="fas fa-globe text-3xl mb-2"></i>
                <p class="font-semibold">サイト管理</p>
            </a>
        </div>
    </div>
</div>
@endsection

