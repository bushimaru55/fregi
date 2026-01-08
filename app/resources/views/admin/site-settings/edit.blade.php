@extends('layouts.admin')

@section('title', '利用規約編集')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-800">
            <i class="fas fa-edit text-indigo-600 mr-3"></i>利用規約編集
        </h2>
        <p class="text-gray-600 mt-2">利用規約の内容を編集します（リッチテキストエディタ）</p>
    </div>

    <!-- Form Card with Livewire Component -->
    <div class="bg-white rounded-xl card-shadow p-8">
        <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-file-contract text-indigo-500 mr-2"></i>利用規約 <span class="text-red-500">*</span>
            </label>
            <p class="text-xs text-gray-500 mb-4">※ この内容は新規申込フォームに表示されます。HTMLで装飾できます。</p>
        </div>
        
        <!-- Livewire RichEditor Component -->
        @livewire('admin.terms-of-service-editor')
    </div>
</div>
@endsection
