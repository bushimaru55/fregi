@extends('layouts.admin')

@section('title', 'フォーム編集')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.contract-forms.index') }}" class="theme-link font-semibold">
            <i class="fas fa-arrow-left mr-2"></i>フォーム管理に戻る
        </a>
    </div>

    <div class="bg-white shadow-lg rounded-lg p-6 max-w-2xl">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">フォーム編集</h1>

        <div class="mb-4 p-4 bg-gray-50 rounded-lg">
            <p class="text-sm text-gray-600 mb-1">URL</p>
            <p class="font-mono text-sm break-all">{{ $contractFormUrl->url }}</p>
        </div>

        <form action="{{ route('admin.contract-forms.update', $contractFormUrl) }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="mb-4">
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">フォーム名（任意）</label>
                <input type="text" 
                    id="name" 
                    name="name" 
                    value="{{ old('name', $contractFormUrl->name) }}" 
                    maxlength="255"
                    placeholder="例: 法人向け申込フォーム"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('name') border-red-500 @enderror">
                @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p class="text-xs text-gray-500 mt-1">一覧で識別しやすい名前を付けてください。</p>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">登録する製品（紐付け）</label>
                <p class="text-xs text-gray-500 mb-3">このフォームのURLで選択可能な製品を選んでください。1つ以上必須です。</p>
                <div class="space-y-2 p-4 bg-gray-50 rounded-lg border border-gray-200 max-h-60 overflow-y-auto">
                    @php
                        $currentPlanIds = old('plan_ids', $contractFormUrl->plan_ids ?? []);
                    @endphp
                    @foreach($plans as $plan)
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox"
                                name="plan_ids[]"
                                value="{{ $plan->id }}"
                                {{ in_array($plan->id, $currentPlanIds) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700">{{ $plan->name }}</span>
                            <span class="ml-2 text-xs text-gray-500">（{{ $plan->formatted_price }}）</span>
                        </label>
                    @endforeach
                </div>
                @error('plan_ids')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                @error('plan_ids.*')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label class="flex items-center">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" 
                        name="is_active" 
                        value="1" 
                        {{ old('is_active', $contractFormUrl->is_active) ? 'checked' : '' }}
                        class="rounded border-gray-300 theme-checkbox-accent shadow-sm">
                    <span class="ml-2 text-sm font-medium text-gray-700">このフォームを有効にする</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-6">無効にすると、このURLからの申し込みは受け付けません。</p>
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="btn-cta px-6 py-2 font-bold rounded-lg shadow-md transition duration-300">
                    <i class="fas fa-save mr-2"></i>更新
                </button>
                <a href="{{ route('admin.contract-forms.index') }}" class="px-6 py-2 border border-gray-300 rounded-lg font-semibold text-gray-700 hover:bg-gray-50 transition duration-300">
                    キャンセル
                </a>
            </div>
        </form>
    </div>
@endsection
