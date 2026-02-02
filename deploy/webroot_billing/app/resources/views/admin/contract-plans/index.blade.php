@extends('layouts.admin')

@section('title', '製品管理')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">製品管理</h1>
        <a href="{{ route('admin.contract-plans.create') }}" class="btn-cta font-bold py-2 px-4 rounded-lg shadow-md transition duration-300">
            <i class="fas fa-plus mr-2"></i>新規作成
        </a>
    </div>

    {{-- ベース製品セクション --}}
    <div class="mb-8">
        <h2 class="text-xl font-bold text-gray-700 mb-4">
            <i class="fas fa-layer-group mr-2"></i>ベース製品
        </h2>
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            @if($plans->isEmpty())
                <div class="p-6 text-center text-gray-600">
                    ベース製品がありません。
                </div>
            @else
            <div class="p-4 bg-blue-50 border-b border-blue-200">
                <p class="text-sm text-blue-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    <i class="fas fa-grip-vertical mr-1"></i>アイコンをドラッグして表示順を変更できます
                </p>
            </div>
            <div id="sort-message" class="hidden fixed top-20 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-opacity duration-300">
                <i class="fas fa-check-circle mr-2"></i>
                <span>表示順を更新しました</span>
            </div>
            <table class="min-w-full leading-normal">
                <thead>
                    <tr class="theme-table-header uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left w-12"></th>
                        <th class="py-3 px-6 text-left">製品コード</th>
                        <th class="py-3 px-6 text-left">製品名</th>
                        <th class="py-3 px-6 text-left">料金</th>
                        <th class="py-3 px-6 text-left">決済タイプ</th>
                        <th class="py-3 px-6 text-left">状態</th>
                        <th class="py-3 px-6 text-center">アクション</th>
                    </tr>
                </thead>
                <tbody id="sortable-table" class="text-gray-600 text-sm font-light">
                    @foreach ($plans as $plan)
                        <tr data-id="{{ $plan->id }}" class="border-b border-gray-200 hover:bg-gray-100">
                            <td class="py-3 px-6 text-center handle-column cursor-move">
                                <i class="fas fa-grip-vertical text-gray-400 hover:text-gray-600"></i>
                            </td>
                            <td class="py-3 px-6 text-left whitespace-nowrap font-mono font-semibold">{{ $plan->item }}</td>
                            <td class="py-3 px-6 text-left">{{ $plan->name }}</td>
                            <td class="py-3 px-6 text-left font-semibold theme-price">{{ $plan->formatted_price }}</td>
                            <td class="py-3 px-6 text-left">
                                @if($plan->billing_type === 'monthly')
                                    <span class="bg-blue-200 text-blue-600 py-1 px-3 rounded-full text-xs font-semibold">月額課金</span>
                                @else
                                    <span class="bg-gray-200 text-gray-600 py-1 px-3 rounded-full text-xs font-semibold">一回限り</span>
                                @endif
                            </td>
                            <td class="py-3 px-6 text-left">
                                @if($plan->is_active)
                                    <span class="bg-green-200 text-green-600 py-1 px-3 rounded-full text-xs">有効</span>
                                @else
                                    <span class="bg-red-200 text-red-600 py-1 px-3 rounded-full text-xs">無効</span>
                                @endif
                            </td>
                            <td class="py-3 px-6 text-center">
                                <div class="flex item-center justify-center space-x-2">
                                    <a href="{{ route('admin.contract-plans.edit', $plan->id) }}" class="theme-link font-semibold">
                                        <i class="fas fa-edit mr-1"></i>編集
                                    </a>
                                    <form action="{{ route('admin.contract-plans.destroy', $plan->id) }}" method="POST" class="inline-block inline-confirm-form" data-confirm="本当に削除しますか？">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 font-semibold">
                                            <i class="fas fa-trash mr-1"></i>削除
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>

    {{-- オプション製品セクション --}}
    <div class="mb-8">
        <h2 class="text-xl font-bold text-gray-700 mb-4">
            <i class="fas fa-box mr-2"></i>オプション製品
        </h2>
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            @if($optionProducts->isEmpty())
                <div class="p-6 text-center text-gray-600">
                    オプション製品がありません。
                </div>
            @else
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr class="theme-table-header uppercase text-sm leading-normal">
                            <th class="py-3 px-6 text-left">製品コード</th>
                            <th class="py-3 px-6 text-left">製品名</th>
                            <th class="py-3 px-6 text-left">料金</th>
                            <th class="py-3 px-6 text-left">状態</th>
                            <th class="py-3 px-6 text-center">アクション</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 text-sm font-light">
                        @foreach ($optionProducts as $product)
                            <tr class="border-b border-gray-200 hover:bg-gray-100">
                                <td class="py-3 px-6 text-left whitespace-nowrap font-mono font-semibold">{{ $product->code }}</td>
                                <td class="py-3 px-6 text-left">{{ $product->name }}</td>
                                <td class="py-3 px-6 text-left font-semibold theme-price">{{ $product->formatted_price }}</td>
                                <td class="py-3 px-6 text-left">
                                    @if($product->is_active)
                                        <span class="bg-green-200 text-green-600 py-1 px-3 rounded-full text-xs">有効</span>
                                    @else
                                        <span class="bg-red-200 text-red-600 py-1 px-3 rounded-full text-xs">無効</span>
                                    @endif
                                </td>
                                <td class="py-3 px-6 text-center">
                                    <div class="flex item-center justify-center space-x-2">
                                        <a href="{{ route('admin.products.edit', $product->id) }}" class="theme-link font-semibold">
                                            <i class="fas fa-edit mr-1"></i>編集
                                        </a>
                                        <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" class="inline-block inline-confirm-form" data-confirm="本当に削除しますか？">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 font-semibold">
                                                <i class="fas fa-trash mr-1"></i>削除
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
<!-- Sortable.js -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sortableTable = document.getElementById('sortable-table');
    if (!sortableTable) return;

    let isUpdating = false;
    const messageEl = document.getElementById('sort-message');

    // メッセージを表示する関数
    function showMessage() {
        if (messageEl) {
            messageEl.classList.remove('hidden');
            setTimeout(function() {
                messageEl.classList.add('hidden');
            }, 3000);
        }
    }

    // エラーメッセージを表示する関数
    function showError() {
        if (messageEl) {
            messageEl.className = 'fixed top-20 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-opacity duration-300';
            messageEl.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i><span>表示順の更新に失敗しました</span>';
            messageEl.classList.remove('hidden');
            setTimeout(function() {
                messageEl.classList.add('hidden');
                messageEl.className = 'hidden fixed top-20 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-opacity duration-300';
                messageEl.innerHTML = '<i class="fas fa-check-circle mr-2"></i><span>表示順を更新しました</span>';
            }, 3000);
        }
    }

    const sortable = Sortable.create(sortableTable, {
        handle: '.handle-column',
        animation: 150,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        onEnd: function(evt) {
            if (isUpdating) return;
            if (evt.oldIndex === evt.newIndex) return; // 位置が変わっていない場合は何もしない

            const rows = sortableTable.querySelectorAll('tr[data-id]');
            const order = Array.from(rows).map(row => parseInt(row.getAttribute('data-id')));

            isUpdating = true;

            // AJAXで順序を更新
            fetch('{{ route("admin.contract-plans.update-order") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ order: order })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                isUpdating = false;
                if (data.success) {
                    showMessage();
                } else {
                    showError();
                }
            })
            .catch(error => {
                console.error('エラー:', error);
                isUpdating = false;
                showError();
            });
        }
    });
});
</script>
<style>
.sortable-ghost {
    opacity: 0.4;
    background-color: var(--color-primary-soft) !important;
}
.sortable-chosen {
    background-color: var(--color-primary-soft) !important;
}
.sortable-drag {
    opacity: 0.8;
}
#sortable-table tr {
    transition: background-color 0.2s;
}
#sortable-table tr:hover {
    background-color: var(--color-bg);
}
.handle-column {
    cursor: move;
    user-select: none;
}
.handle-column:hover {
    background-color: var(--color-bg);
}
.handle-column .fa-grip-vertical {
    transition: color 0.2s;
}
.handle-column:hover .fa-grip-vertical {
    color: var(--color-primary) !important;
}
</style>
@endsection
