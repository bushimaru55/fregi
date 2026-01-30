<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    {{-- マスター名 --}}
    <div class="md:col-span-2">
        <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
            マスター名 <span class="text-red-500">*</span>
        </label>
        <input type="text" name="name" id="name" 
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('name') border-red-500 @enderror" 
            value="{{ old('name', $contractPlanMaster->name ?? '') }}" required placeholder="例: 基本プラングループ">
        @error('name')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- マスター説明 --}}
    <div class="md:col-span-2">
        <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">
            マスター説明
        </label>
        <textarea name="description" id="description" rows="4"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('description') border-red-500 @enderror">{{ old('description', $contractPlanMaster->description ?? '') }}</textarea>
        @error('description')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- 表示順 --}}
    <div>
        <label for="display_order" class="block text-sm font-semibold text-gray-700 mb-2">
            表示順 <span class="text-red-500">*</span>
        </label>
        <input type="number" name="display_order" id="display_order" min="0" step="1"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('display_order') border-red-500 @enderror" 
            value="{{ old('display_order', $contractPlanMaster->display_order ?? 0) }}" required>
        @error('display_order')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- 有効フラグ --}}
    <div class="md:col-span-2">
        <label class="flex items-center">
            <input type="checkbox" name="is_active" value="1" 
                class="mr-2 w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                {{ old('is_active', $contractPlanMaster->is_active ?? true) ? 'checked' : '' }}>
            <span class="text-sm font-semibold text-gray-700">このマスターを有効にする</span>
        </label>
    </div>
</div>

