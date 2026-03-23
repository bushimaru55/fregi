@extends('layouts.admin')

@section('title', '申し込み一覧')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">申し込み一覧</h1>
        <a href="{{ route('admin.contracts.export', request()->query()) }}" class="btn-primary inline-flex items-center px-4 py-2 rounded-lg font-semibold text-sm">
            <i class="fas fa-file-csv mr-2"></i>CSV出力（検索条件に一致する件のみ）
        </a>
    </div>

    {{-- 条件検索フォーム --}}
    <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">条件検索</h2>
            <form method="GET" action="{{ route('admin.contracts.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                    <div>
                        <label for="keyword" class="block text-sm font-medium text-gray-700 mb-1">キーワード</label>
                        <input type="text" name="keyword" id="keyword" value="{{ old('keyword', request('keyword')) }}"
                               placeholder="会社名・担当者・メール"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">ステータス</label>
                        <select name="status" id="status" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">すべて</option>
                            @foreach($statuses as $s)
                                <option value="{{ $s->code }}" {{ request('status') === $s->code ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="contract_plan_id" class="block text-sm font-medium text-gray-700 mb-1">製品（含む）</label>
                        <select name="contract_plan_id" id="contract_plan_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">すべて</option>
                            @foreach($plans as $p)
                                <option value="{{ $p->id }}" {{ request('contract_plan_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="product_id" class="block text-sm font-medium text-gray-700 mb-1">オプション商品（含む）</label>
                        <select name="product_id" id="product_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">すべて</option>
                            @foreach($optionProducts as $op)
                                <option value="{{ $op->id }}" {{ request('product_id') == $op->id ? 'selected' : '' }}>{{ $op->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="created_from" class="block text-sm font-medium text-gray-700 mb-1">申込日（から）</label>
                        <input type="date" name="created_from" id="created_from" value="{{ request('created_from') }}"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <div>
                        <label for="created_to" class="block text-sm font-medium text-gray-700 mb-1">申込日（まで）</label>
                        <input type="date" name="created_to" id="created_to" value="{{ request('created_to') }}"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary inline-flex items-center px-4 py-2 rounded-lg font-semibold text-sm">
                        <i class="fas fa-search mr-2"></i>検索
                    </button>
                    <a href="{{ route('admin.contracts.index') }}" class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50">
                        <i class="fas fa-times mr-2"></i>クリア
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        @if($contracts->isEmpty())
            <div class="p-6 text-center text-gray-600">
                契約データがありません。
            </div>
        @else
            <div id="bulk-destroy-toolbar" class="px-6 py-3 border-b border-gray-200 bg-gray-50 flex items-center gap-4"
                 data-action="{{ route('admin.contracts.bulk-destroy') }}"
                 data-csrf="{{ csrf_token() }}"
                 data-confirm="選択した申し込みを削除してもよろしいですか？">
                <button type="button" id="bulk-destroy-btn" class="inline-flex items-center px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                    <i class="fas fa-trash-alt mr-2"></i>選択した申し込みを削除
                </button>
                <span id="bulk-destroy-count" class="text-sm text-gray-600">0件選択中</span>
            </div>
            <table class="min-w-full leading-normal">
                <thead>
                    <tr class="theme-table-header uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left w-12">
                            <label class="flex items-center justify-center cursor-pointer">
                                <input type="checkbox" id="select-all" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" aria-label="すべて選択">
                            </label>
                        </th>
                        <th class="py-3 px-6 text-left">ID</th>
                        <th class="py-3 px-6 text-left">会社名</th>
                        <th class="py-3 px-6 text-left">担当者</th>
                        <th class="py-3 px-6 text-left">選択商品</th>
                        <th class="py-3 px-6 text-left">金額</th>
                        <th class="py-3 px-6 text-left">ステータス</th>
                        <th class="py-3 px-6 text-left">申込日</th>
                        <th class="py-3 px-6 text-center">アクション</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 text-sm font-light">
                    @foreach ($contracts as $contract)
                        <tr class="border-b border-gray-200 hover:bg-gray-100">
                            <td class="py-3 px-6 text-center">
                                <label class="cursor-pointer">
                                    <input type="checkbox" name="ids[]" value="{{ $contract->id }}" class="row-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" aria-label="ID {{ $contract->id }} を選択">
                                </label>
                            </td>
                            <td class="py-3 px-6 text-left whitespace-nowrap">{{ $contract->id }}</td>
                            <td class="py-3 px-6 text-left">{{ $contract->company_name }}</td>
                            <td class="py-3 px-6 text-left">{{ $contract->contact_name }}</td>
                            <td class="py-3 px-6 text-left">
                                @if($contract->contractItems->isNotEmpty())
                                    {{ $contract->contractItems->map(fn($i) => $i->product_name ?? $i->contractPlan->name ?? $i->product->name ?? '')->join(', ') }}
                                    @if($contract->contractItems->count() > 2)
                                        <span class="text-gray-500">（{{ $contract->contractItems->count() }}件）</span>
                                    @endif
                                @else
                                    {{ optional($contract->representative_plan)->name ?? '—' }}
                                @endif
                            </td>
                            <td class="py-3 px-6 text-left font-semibold">{{ number_format($contract->contractItems->sum('subtotal')) }}円</td>
                            <td class="py-3 px-6 text-left">
                                @php
                                    $statusColors = [
                                        'applied' => 'bg-blue-200 text-blue-800',
                                        'trial' => 'bg-yellow-200 text-yellow-800',
                                        'product' => 'bg-green-200 text-green-800',
                                        'suspended' => 'bg-red-200 text-red-800',
                                    ];
                                    $colorClass = $statusColors[$contract->status] ?? 'bg-gray-200 text-gray-600';
                                @endphp
                                <span class="{{ $colorClass }} py-1 px-3 rounded-full text-xs font-semibold">
                                    {{ $contract->status_label }}
                                </span>
                            </td>
                            <td class="py-3 px-6 text-left">{{ $contract->created_at->format('Y/m/d') }}</td>
                            <td class="py-3 px-6 text-center">
                                <div class="flex flex-wrap items-center justify-center gap-2">
                                    <a href="{{ route('admin.contracts.show', $contract) }}" class="theme-link font-semibold text-sm whitespace-nowrap">
                                        <i class="fas fa-eye mr-1"></i>詳細
                                    </a>
                                    <span class="text-gray-300">|</span>
                                    <a href="{{ route('admin.contracts.edit', $contract) }}" class="theme-link font-semibold text-sm whitespace-nowrap">
                                        <i class="fas fa-edit mr-1"></i>編集
                                    </a>
                                    <span class="text-gray-300">|</span>
                                    <form action="{{ route('admin.contracts.destroy', $contract) }}" method="POST" class="inline inline-confirm-form" data-confirm="この契約を削除してもよろしいですか？">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 font-semibold text-sm whitespace-nowrap cursor-pointer">
                                            <i class="fas fa-trash-alt mr-1"></i>削除
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- ページネーション --}}
            <div class="p-4">
                {{ $contracts->links() }}
            </div>
        @endif
    </div>

    @if(!$contracts->isEmpty())
    <script>
        (function() {
            var toolbar = document.getElementById('bulk-destroy-toolbar');
            var selectAll = document.getElementById('select-all');
            var checkboxes = document.querySelectorAll('.row-checkbox');
            var btn = document.getElementById('bulk-destroy-btn');
            var countEl = document.getElementById('bulk-destroy-count');

            function updateState() {
                var n = document.querySelectorAll('.row-checkbox:checked').length;
                btn.disabled = n === 0;
                countEl.textContent = n + '件選択中';
                selectAll.checked = n > 0 && n === checkboxes.length;
                selectAll.indeterminate = n > 0 && n < checkboxes.length;
            }

            selectAll.addEventListener('change', function() {
                checkboxes.forEach(function(cb) { cb.checked = selectAll.checked; });
                updateState();
            });
            checkboxes.forEach(function(cb) {
                cb.addEventListener('change', updateState);
            });
            updateState();

            btn.addEventListener('click', function() {
                var checked = document.querySelectorAll('.row-checkbox:checked');
                if (checked.length === 0) return;
                if (!toolbar.dataset.confirm || !confirm(toolbar.dataset.confirm)) return;
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = toolbar.dataset.action;
                var csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = toolbar.dataset.csrf;
                form.appendChild(csrf);
                checked.forEach(function(cb) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = cb.value;
                    form.appendChild(input);
                });
                document.body.appendChild(form);
                form.submit();
            });
        })();
    </script>
    @endif
@endsection

