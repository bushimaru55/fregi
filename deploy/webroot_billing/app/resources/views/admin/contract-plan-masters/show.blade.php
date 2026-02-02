@extends('layouts.admin')

@section('title', '契約プランマスター詳細')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">契約プランマスター詳細</h1>
        <div class="space-x-2">
            <a href="{{ route('admin.contract-plan-masters.edit', $contractPlanMaster->id) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition duration-300">
                <i class="fas fa-edit mr-2"></i>編集
            </a>
            <a href="{{ route('admin.contract-plan-masters.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg shadow-md transition duration-300">
                <i class="fas fa-arrow-left mr-2"></i>一覧へ戻る
            </a>
        </div>
    </div>

    <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">基本情報</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm text-gray-600 mb-1">マスターID</p>
                <p class="text-lg font-semibold text-gray-800">{{ $contractPlanMaster->id }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">マスター名</p>
                <p class="text-lg font-semibold text-gray-800">{{ $contractPlanMaster->name }}</p>
            </div>
            <div class="md:col-span-2">
                <p class="text-sm text-gray-600 mb-1">マスター説明</p>
                <p class="text-lg text-gray-800 whitespace-pre-wrap">{{ $contractPlanMaster->description ?? '（説明なし）' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">表示順</p>
                <p class="text-lg font-semibold text-gray-800">{{ $contractPlanMaster->display_order }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">状態</p>
                @if($contractPlanMaster->is_active)
                    <span class="bg-green-200 text-green-600 py-1 px-3 rounded-full text-sm font-semibold">有効</span>
                @else
                    <span class="bg-red-200 text-red-600 py-1 px-3 rounded-full text-sm font-semibold">無効</span>
                @endif
            </div>
        </div>
    </div>

    <div class="bg-white shadow-lg rounded-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-800">関連する契約プラン ({{ $contractPlanMaster->contractPlans->count() }}件)</h2>
            <a href="{{ route('admin.contract-plans.create') }}?master_id={{ $contractPlanMaster->id }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition duration-300 text-sm">
                <i class="fas fa-plus mr-2"></i>プランを追加
            </a>
        </div>
        @if($contractPlanMaster->contractPlans->isEmpty())
            <div class="p-6 text-center text-gray-600">
                関連する契約プランがありません。
            </div>
        @else
            <table class="min-w-full leading-normal">
                <thead>
                    <tr class="theme-table-header uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left">プランコード</th>
                        <th class="py-3 px-6 text-left">プラン名</th>
                        <th class="py-3 px-6 text-left">料金</th>
                        <th class="py-3 px-6 text-left">状態</th>
                        <th class="py-3 px-6 text-center">アクション</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 text-sm font-light">
                    @foreach ($contractPlanMaster->contractPlans as $plan)
                        <tr class="border-b border-gray-200 hover:bg-gray-100">
                            <td class="py-3 px-6 text-left whitespace-nowrap font-mono font-semibold">{{ $plan->item }}</td>
                            <td class="py-3 px-6 text-left">{{ $plan->name }}</td>
                            <td class="py-3 px-6 text-left font-semibold text-indigo-600">{{ $plan->formatted_price }}</td>
                            <td class="py-3 px-6 text-left">
                                @if($plan->is_active)
                                    <span class="bg-green-200 text-green-600 py-1 px-3 rounded-full text-xs">有効</span>
                                @else
                                    <span class="bg-red-200 text-red-600 py-1 px-3 rounded-full text-xs">無効</span>
                                @endif
                            </td>
                            <td class="py-3 px-6 text-center">
                                <a href="{{ route('admin.contract-plans.edit', $plan->id) }}" class="text-indigo-600 hover:text-indigo-900 font-semibold">
                                    <i class="fas fa-edit mr-1"></i>編集
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection

