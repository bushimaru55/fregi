@extends('layouts.admin')

@section('title', 'ステータスマスター')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">ステータスマスター</h1>
        <a href="{{ route('admin.contract-statuses.create') }}" class="btn-primary font-bold py-2 px-4 rounded-lg shadow-md transition duration-300">
            <i class="fas fa-plus mr-2"></i>新規追加
        </a>
    </div>

    @if(session('error'))
        <div class="theme-alert-error p-4 mb-6 rounded-r">
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        @if($statuses->isEmpty())
            <div class="p-6 text-center text-gray-600">
                ステータスがありません。新規追加で登録してください。
            </div>
        @else
            <table class="min-w-full leading-normal">
                <thead>
                    <tr class="theme-table-header uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left">ID</th>
                        <th class="py-3 px-6 text-left">コード</th>
                        <th class="py-3 px-6 text-left">契約状態（表示名）</th>
                        <th class="py-3 px-6 text-left">表示順</th>
                        <th class="py-3 px-6 text-left">有効</th>
                        <th class="py-3 px-6 text-center">アクション</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 text-sm font-light">
                    @foreach ($statuses as $status)
                        <tr class="border-b border-gray-200 hover:bg-gray-100">
                            <td class="py-3 px-6 text-left whitespace-nowrap">{{ $status->id }}</td>
                            <td class="py-3 px-6 text-left font-mono text-sm">{{ $status->code }}</td>
                            <td class="py-3 px-6 text-left font-semibold">{{ $status->name }}</td>
                            <td class="py-3 px-6 text-left">{{ $status->display_order }}</td>
                            <td class="py-3 px-6 text-left">
                                @if($status->is_active)
                                    <span class="bg-green-200 text-green-800 py-1 px-3 rounded-full text-xs">有効</span>
                                @else
                                    <span class="bg-gray-200 text-gray-600 py-1 px-3 rounded-full text-xs">無効</span>
                                @endif
                            </td>
                            <td class="py-3 px-6 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('admin.contract-statuses.edit', $status) }}" class="theme-link font-semibold text-sm whitespace-nowrap">
                                        <i class="fas fa-edit mr-1"></i>編集
                                    </a>
                                    @if(!$status->contracts()->exists())
                                        <span class="text-gray-300">|</span>
                                        <form action="{{ route('admin.contract-statuses.destroy', $status) }}" method="POST" class="inline inline-confirm-form" data-confirm="このステータスを削除してもよろしいですか？">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 font-semibold text-sm whitespace-nowrap cursor-pointer">
                                                <i class="fas fa-trash-alt mr-1"></i>削除
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-gray-400 text-xs">（使用中のため削除不可）</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <p class="mt-4 text-sm text-gray-500">
        ※ 契約状態（表示名）・表示順・有効の変更は、既存の申込レコードに影響しません。コードは作成時のみ設定し、編集では変更できません。
    </p>
@endsection
