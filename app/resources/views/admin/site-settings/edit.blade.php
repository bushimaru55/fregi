@extends('layouts.admin')

@section('title', '利用規約編集')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-800">
            <i class="fas fa-edit text-indigo-600 mr-3"></i>利用規約編集
        </h2>
        <p class="text-gray-600 mt-2">利用規約の内容を編集します</p>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-xl card-shadow p-8">
        <form action="{{ route('admin.site-settings.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <!-- Terms of Service -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-file-contract text-indigo-500 mr-2"></i>利用規約 <span class="text-red-500">*</span>
                    </label>
                    <textarea name="terms_of_service" id="terms_of_service" rows="15" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('terms_of_service') border-red-500 @enderror" required>{{ old('terms_of_service', $termsOfService) }}</textarea>
                    @error('terms_of_service')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1">※ この内容は新規申込フォームに表示されます</p>
                </div>
            </div>

            <!-- Buttons -->
            <div class="mt-8 flex justify-end space-x-4">
                <a href="{{ route('admin.site-settings.index') }}" 
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
@endsection
