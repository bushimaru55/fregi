@extends('layouts.admin')

@section('title', '製品ページのURL編集')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-800">
            <i class="fas fa-edit theme-price mr-3"></i>製品ページのURL編集
        </h2>
        <p class="text-gray-600 mt-2">公開ページヘッダーの「製品ページへ戻る」ボタンのリンク先URLを設定します</p>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-xl card-shadow p-8">
        <form action="{{ route('admin.site-settings.product-page-url.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <!-- Product Page URL -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-globe theme-price mr-2"></i>製品ページのURL <span class="text-red-500">*</span>
                    </label>
                    <p class="text-xs text-gray-500 mb-2">
                        公開ページヘッダーの「製品ページへ戻る」ボタンから遷移するURLを指定してください。
                    </p>
                    <input type="url" 
                           name="product_page_url" 
                           value="{{ old('product_page_url', $productPageUrl) }}" 
                           placeholder="https://www.dschatbot.ai/"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg theme-input @error('product_page_url') border-red-500 @enderror"
                           required>
                    @error('product_page_url')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        例: <code class="bg-gray-100 px-1 py-0.5 rounded">https://www.dschatbot.ai/</code>
                    </p>
                </div>

                <!-- Success Message -->
                @if(session('success'))
                    <div class="theme-alert-success rounded-lg p-4">
                        <p class="text-green-800">
                            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                        </p>
                    </div>
                @endif

                <!-- Error Message -->
                @if($errors->has('error'))
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <p class="text-red-800">
                            <i class="fas fa-exclamation-circle mr-2"></i>{{ $errors->first('error') }}
                        </p>
                    </div>
                @endif
            </div>

            <div class="mt-8 flex justify-end space-x-4">
                <a href="{{ route('admin.site-settings.index') }}" 
                   class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                    <i class="fas fa-times mr-2"></i>キャンセル
                </a>
                <button type="submit" 
                        class="theme-btn-primary inline-block px-6 py-3 rounded-lg hover:opacity-90 transition shadow-lg no-underline border-0 cursor-pointer">
                    <i class="fas fa-save mr-2"></i>更新する
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
