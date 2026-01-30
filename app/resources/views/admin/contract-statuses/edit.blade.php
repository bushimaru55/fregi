@extends('layouts.admin')

@section('title', 'ステータスマスター編集')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.contract-statuses.index') }}" class="theme-link font-semibold">
            <i class="fas fa-arrow-left mr-2"></i>一覧に戻る
        </a>
    </div>

    <h1 class="text-3xl font-bold text-gray-800 mb-6">ステータスマスター編集（{{ $contractStatus->code }}）</h1>

    <div class="bg-white shadow-lg rounded-lg p-6">
        <form action="{{ route('admin.contract-statuses.update', $contractStatus) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">コード</label>
                    <p class="px-4 py-2 bg-gray-100 rounded-lg font-mono text-gray-600">{{ $contractStatus->code }}</p>
                    <p class="text-xs text-gray-500 mt-1">コードは変更できません。申込レコードはこのコードで参照しています。</p>
                </div>
                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">表示名 <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name', $contractStatus->name) }}" required maxlength="100"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="display_order" class="block text-sm font-semibold text-gray-700 mb-2">表示順 <span class="text-red-500">*</span></label>
                    <input type="number" name="display_order" id="display_order" value="{{ old('display_order', $contractStatus->display_order) }}" min="0" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('display_order') border-red-500 @enderror">
                    @error('display_order')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex items-center">
                    <label class="flex items-center cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $contractStatus->is_active) ? 'checked' : '' }}
                            class="theme-checkbox-accent border-gray-300 rounded mr-2">
                        <span class="text-sm font-semibold text-gray-700">有効</span>
                    </label>
                </div>
            </div>
            <div class="flex flex-wrap gap-3 mt-6">
                <button type="submit" class="btn-primary px-6 py-2 font-bold rounded-lg">更新</button>
                <a href="{{ route('admin.contract-statuses.index') }}" class="btn-outline px-6 py-2 font-bold rounded-lg inline-block">キャンセル</a>
            </div>
        </form>
    </div>
@endsection
