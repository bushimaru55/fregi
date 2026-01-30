@extends('layouts.admin')

@section('title', '契約プランマスター管理')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">契約プランマスター管理</h1>
        <a href="{{ route('admin.contract-plan-masters.create') }}" class="btn-primary font-bold py-2 px-4 rounded-lg shadow-md transition duration-300">
            <i class="fas fa-plus mr-2"></i>新規作成
        </a>
    </div>

    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        @if($masters->isEmpty())
            <div class="p-6 text-center text-gray-600">
                契約プランマスターがありません。
            </div>
        @else
            <table class="min-w-full leading-normal">
                <thead>
                    <tr class="theme-table-header uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left">ID</th>
                        <th class="py-3 px-6 text-left">マスター名</th>
                        <th class="py-3 px-6 text-left">説明</th>
                        <th class="py-3 px-6 text-left">契約プラン数</th>
                        <th class="py-3 px-6 text-left">状態</th>
                        <th class="py-3 px-6 text-center">アクション</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 text-sm font-light">
                    @foreach ($masters as $master)
                        <tr class="border-b border-gray-200 hover:bg-gray-100">
                            <td class="py-3 px-6 text-left whitespace-nowrap">{{ $master->id }}</td>
                            <td class="py-3 px-6 text-left font-semibold">{{ $master->name }}</td>
                            <td class="py-3 px-6 text-left">{{ Str::limit($master->description ?? '', 50) }}</td>
                            <td class="py-3 px-6 text-left">
                                <span class="bg-blue-100 text-blue-800 py-1 px-3 rounded-full text-xs font-semibold">
                                    {{ $master->contractPlans->count() }}件
                                </span>
                            </td>
                            <td class="py-3 px-6 text-left">
                                @if($master->is_active)
                                    <span class="bg-green-200 text-green-600 py-1 px-3 rounded-full text-xs">有効</span>
                                @else
                                    <span class="bg-red-200 text-red-600 py-1 px-3 rounded-full text-xs">無効</span>
                                @endif
                            </td>
                            <td class="py-3 px-6 text-center">
                                <div class="flex item-center justify-center space-x-2">
                                    <a href="{{ route('admin.contract-plan-masters.show', $master->id) }}" class="text-blue-600 hover:text-blue-900 font-semibold">
                                        <i class="fas fa-eye mr-1"></i>詳細
                                    </a>
                                    <a href="{{ route('admin.contract-plan-masters.edit', $master->id) }}" class="text-indigo-600 hover:text-indigo-900 font-semibold">
                                        <i class="fas fa-edit mr-1"></i>編集
                                    </a>
                                    <form action="{{ route('admin.contract-plan-masters.destroy', $master->id) }}" method="POST" class="inline-block inline-confirm-form" data-confirm="本当に削除しますか？">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 font-semibold">
                                            <i class="fas fa-trash mr-1"></i>削除
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection

