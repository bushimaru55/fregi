<div>
    <form wire:submit="save">
        {{-- モード切り替えボタン --}}
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="text-sm font-semibold text-gray-700">
                    <i class="fas fa-edit mr-1"></i>編集モード:
                </span>
                <span class="px-3 py-1 rounded-full text-sm font-medium {{ $isSourceMode ? 'bg-orange-100 text-orange-700' : 'bg-green-100 text-green-700' }}">
                    {{ $isSourceMode ? 'HTMLソース' : 'ビジュアル' }}
                </span>
            </div>
            <button type="button" 
                    wire:click="toggleSourceMode"
                    class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition flex items-center">
                @if($isSourceMode)
                    <i class="fas fa-eye mr-2"></i>ビジュアルモードに切替
                @else
                    <i class="fas fa-code mr-2"></i>HTMLソースを編集
                @endif
            </button>
        </div>

        {{-- ビジュアルエディタ（ソースモードでない場合に表示） --}}
        <div class="{{ $isSourceMode ? 'hidden' : '' }}">
            {{ $this->form }}
        </div>

        {{-- HTMLソースエディタ（ソースモードの場合に表示） --}}
        @if($isSourceMode)
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-code text-orange-500 mr-2"></i>HTMLソース編集
            </label>
            <div class="bg-orange-50 border border-orange-200 rounded-lg p-3 mb-3">
                <p class="text-xs text-orange-700">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    HTMLタグを直接編集できます。保存時にセキュリティのためサニタイズ（安全でないタグの除去）が行われます。
                </p>
            </div>
            <textarea 
                wire:model="sourceHtml"
                rows="20"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 font-mono text-sm bg-gray-50"
                placeholder="HTMLソースを入力してください..."
            ></textarea>
            <p class="text-xs text-gray-500 mt-2">
                <i class="fas fa-info-circle mr-1"></i>
                許可されるタグ: p, br, strong, b, em, i, u, s, h1-h6, ul, ol, li, a, span, div, blockquote, pre, code, table, tr, th, td など
            </p>
        </div>
        @endif

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
