@extends('layouts.admin')

@section('title', 'ステータスマスター追加')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.contract-statuses.index') }}" class="theme-link font-semibold">
            <i class="fas fa-arrow-left mr-2"></i>一覧に戻る
        </a>
    </div>

    <h1 class="text-3xl font-bold text-gray-800 mb-6">ステータスマスター追加</h1>

    <div class="bg-white shadow-lg rounded-lg p-6">
        <form action="{{ route('admin.contract-statuses.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="code" class="block text-sm font-semibold text-gray-700 mb-2">コード <span class="text-red-500">*</span></label>
                    <input type="text" name="code" id="code" value="{{ old('code') }}" required
                        pattern="[a-z0-9_]+" placeholder="例: new_status"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input font-mono @error('code') border-red-500 @enderror">
                    <p class="text-xs text-gray-500 mt-1">半角英小文字・数字・アンダースコアのみ。作成後は変更できません。</p>
                    @error('code')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">表示名 <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required maxlength="100"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="display_order" class="block text-sm font-semibold text-gray-700 mb-2">表示順 <span class="text-red-500">*</span></label>
                    <input type="number" name="display_order" id="display_order" value="{{ old('display_order', 0) }}" min="0" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('display_order') border-red-500 @enderror">
                    @error('display_order')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex items-center">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                            class="theme-checkbox-accent border-gray-300 rounded mr-2">
                        <span class="text-sm font-semibold text-gray-700">有効</span>
                    </label>
                </div>
            </div>
            <div class="flex flex-wrap gap-3 mt-6">
                <button type="submit" class="btn-primary px-6 py-2 font-bold rounded-lg">追加</button>
                <a href="{{ route('admin.contract-statuses.index') }}" class="btn-outline px-6 py-2 font-bold rounded-lg inline-block">キャンセル</a>
            </div>
        </form>
    </div>
@endsection
