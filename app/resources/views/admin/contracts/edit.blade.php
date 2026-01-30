@extends('layouts.admin')

@section('title', '契約編集 #' . $contract->id)

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.contracts.show', $contract) }}" class="theme-link font-semibold">
            <i class="fas fa-arrow-left mr-2"></i>詳細に戻る
        </a>
    </div>

    <h1 class="text-3xl font-bold text-gray-800 mb-6">契約編集 #{{ $contract->id }}</h1>

    <form action="{{ route('admin.contracts.update', $contract) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- 契約情報（読取専用） --}}
        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 pb-2 theme-section-border">契約情報（変更不可）</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-600">
                <div>
                    <p class="text-sm text-gray-500 mb-1">契約プラン</p>
                    <p class="font-semibold">{{ $contract->contractPlan->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 mb-1">料金（税込）</p>
                    <p class="font-semibold theme-price">{{ number_format($contract->contractPlan->price) }}円</p>
                </div>
            </div>
        </div>

        {{-- オプション商品の有無（チェックボックスで編集） --}}
        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 pb-2 theme-section-border">オプション商品</h2>
            <p class="text-sm text-gray-600 mb-4">この契約に含めるオプション商品にチェックを入れてください。</p>
            @if(isset($optionProducts) && $optionProducts->isNotEmpty())
                <div class="space-y-3">
                    @foreach($optionProducts as $product)
                        <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" name="option_product_ids[]" value="{{ $product->id }}"
                                {{ in_array($product->id, old('option_product_ids', $selectedOptionProductIds ?? [])) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="font-medium text-gray-800">{{ $product->name }}</span>
                            <span class="text-sm text-gray-500">{{ $product->formatted_price ?? number_format($product->unit_price) }}円</span>
                        </label>
                    @endforeach
                </div>
                @error('option_product_ids')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                @error('option_product_ids.*')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            @else
                <p class="text-sm text-gray-500">このプランに紐づくオプション製品はありません。</p>
            @endif
        </div>

        {{-- ステータス --}}
        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 pb-2 theme-section-border">ステータス</h2>
            <div>
                <label for="status" class="block text-sm font-semibold text-gray-700 mb-2">ステータス <span class="text-red-500">*</span></label>
                <select name="status" id="status" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('status') border-red-500 @enderror">
                    @foreach($statuses ?? [] as $s)
                        <option value="{{ $s->code }}" {{ old('status', $contract->status) === $s->code ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                    @if(($statuses ?? collect())->isEmpty())
                        <option value="{{ $contract->status }}" selected>{{ $contract->status_label }}</option>
                    @endif
                </select>
                @error('status')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- 申込企業情報 --}}
        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 pb-2 theme-section-border">申込企業情報</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label for="company_name" class="block text-sm font-semibold text-gray-700 mb-2">会社名 <span class="text-red-500">*</span></label>
                    <input type="text" name="company_name" id="company_name" value="{{ old('company_name', $contract->company_name) }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('company_name') border-red-500 @enderror">
                    @error('company_name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="company_name_kana" class="block text-sm font-semibold text-gray-700 mb-2">会社名（フリガナ）</label>
                    <input type="text" name="company_name_kana" id="company_name_kana" value="{{ old('company_name_kana', $contract->company_name_kana) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('company_name_kana') border-red-500 @enderror">
                    @error('company_name_kana')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="department" class="block text-sm font-semibold text-gray-700 mb-2">部署名</label>
                    <input type="text" name="department" id="department" value="{{ old('department', $contract->department) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('department') border-red-500 @enderror">
                    @error('department')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="position" class="block text-sm font-semibold text-gray-700 mb-2">役職</label>
                    <input type="text" name="position" id="position" value="{{ old('position', $contract->position) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('position') border-red-500 @enderror">
                    @error('position')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="contact_name" class="block text-sm font-semibold text-gray-700 mb-2">担当者名 <span class="text-red-500">*</span></label>
                    <input type="text" name="contact_name" id="contact_name" value="{{ old('contact_name', $contract->contact_name) }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('contact_name') border-red-500 @enderror">
                    @error('contact_name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="contact_name_kana" class="block text-sm font-semibold text-gray-700 mb-2">担当者名（フリガナ）</label>
                    <input type="text" name="contact_name_kana" id="contact_name_kana" value="{{ old('contact_name_kana', $contract->contact_name_kana) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('contact_name_kana') border-red-500 @enderror">
                    @error('contact_name_kana')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">メールアドレス <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="email" value="{{ old('email', $contract->email) }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">電話番号 <span class="text-red-500">*</span></label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone', $contract->phone) }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('phone') border-red-500 @enderror">
                    @error('phone')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- 住所 --}}
        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 pb-2 theme-section-border">住所</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="postal_code" class="block text-sm font-semibold text-gray-700 mb-2">郵便番号</label>
                    <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $contract->postal_code) }}" placeholder="123-4567"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('postal_code') border-red-500 @enderror">
                    @error('postal_code')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="prefecture" class="block text-sm font-semibold text-gray-700 mb-2">都道府県</label>
                    <input type="text" name="prefecture" id="prefecture" value="{{ old('prefecture', $contract->prefecture) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('prefecture') border-red-500 @enderror">
                    @error('prefecture')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="city" class="block text-sm font-semibold text-gray-700 mb-2">市区町村</label>
                    <input type="text" name="city" id="city" value="{{ old('city', $contract->city) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('city') border-red-500 @enderror">
                    @error('city')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="address_line1" class="block text-sm font-semibold text-gray-700 mb-2">番地</label>
                    <input type="text" name="address_line1" id="address_line1" value="{{ old('address_line1', $contract->address_line1) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('address_line1') border-red-500 @enderror">
                    @error('address_line1')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="md:col-span-2">
                    <label for="address_line2" class="block text-sm font-semibold text-gray-700 mb-2">建物名</label>
                    <input type="text" name="address_line2" id="address_line2" value="{{ old('address_line2', $contract->address_line2) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('address_line2') border-red-500 @enderror">
                    @error('address_line2')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- ご利用情報・日付・備考 --}}
        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 pb-2 theme-section-border">その他</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label for="usage_url_domain" class="block text-sm font-semibold text-gray-700 mb-2">ご利用URL・ドメイン</label>
                    <input type="text" name="usage_url_domain" id="usage_url_domain" value="{{ old('usage_url_domain', $contract->usage_url_domain) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('usage_url_domain') border-red-500 @enderror">
                    @error('usage_url_domain')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="desired_start_date" class="block text-sm font-semibold text-gray-700 mb-2">希望開始日</label>
                    <input type="date" name="desired_start_date" id="desired_start_date" value="{{ old('desired_start_date', $contract->desired_start_date?->format('Y-m-d')) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('desired_start_date') border-red-500 @enderror">
                    @error('desired_start_date')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="actual_start_date" class="block text-sm font-semibold text-gray-700 mb-2">実際の利用開始日</label>
                    <input type="date" name="actual_start_date" id="actual_start_date" value="{{ old('actual_start_date', $contract->actual_start_date?->format('Y-m-d')) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('actual_start_date') border-red-500 @enderror">
                    @error('actual_start_date')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-semibold text-gray-700 mb-2">終了日</label>
                    <input type="date" name="end_date" id="end_date" value="{{ old('end_date', $contract->end_date?->format('Y-m-d')) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('end_date') border-red-500 @enderror">
                    @error('end_date')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="md:col-span-2">
                    <label for="notes" class="block text-sm font-semibold text-gray-700 mb-2">備考</label>
                    <textarea name="notes" id="notes" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('notes') border-red-500 @enderror">{{ old('notes', $contract->notes) }}</textarea>
                    @error('notes')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn-primary px-6 py-2 font-bold rounded-lg shadow-md transition duration-300">
                <i class="fas fa-save mr-2"></i>更新
            </button>
            <a href="{{ route('admin.contracts.show', $contract) }}" class="btn-outline px-6 py-2 font-bold rounded-lg inline-block">
                キャンセル
            </a>
        </div>
    </form>
@endsection
