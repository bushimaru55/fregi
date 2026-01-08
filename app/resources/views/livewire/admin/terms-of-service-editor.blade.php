<div>
    <form wire:submit="save">
        {{ $this->form }}

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
