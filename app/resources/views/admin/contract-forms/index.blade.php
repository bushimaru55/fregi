@extends('layouts.admin')

@section('title', '新規申込フォーム管理')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">新規申込フォーム管理</h1>
    </div>

    <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
        <p class="text-gray-600 mb-4">
            <i class="fas fa-info-circle mr-2"></i>
            表示したい契約プランを選択してURLを生成してください。生成されたURLにアクセスすると、選択したプランのみが表示される新規申込フォームが表示されます。
        </p>
    </div>

    @if(session('success'))
        <div class="theme-alert-success rounded-lg p-4 mb-6">
            <p class="text-green-800">
                <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            </p>
        </div>
    @endif

    @if(session('generated_view_url'))
        <div class="theme-alert-success rounded-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-check-circle text-green-500 mr-2"></i>閲覧画面用URLが生成されました
            </h2>
            <div class="flex items-center space-x-2 mb-2">
                <input type="text" 
                    id="generated-view-url" 
                    value="{{ session('generated_view_url') }}" 
                    readonly
                    class="flex-1 px-4 py-2 border-2 border-green-300 rounded-lg bg-white font-mono text-sm">
                <button 
                    onclick="copyUrl('generated-view-url', 'copy-success-view')" 
                    class="btn-primary px-6 py-2 font-bold rounded-lg shadow-md transition duration-300">
                    <i class="fas fa-copy mr-2"></i>コピー
                </button>
            </div>
            <div id="copy-success-view" class="hidden text-green-600 text-sm mt-2">
                <i class="fas fa-check mr-1"></i>URLをクリップボードにコピーしました
            </div>
            <div class="mt-4">
                <a href="{{ session('generated_view_url') }}" 
                    target="_blank"
                    class="btn-cta inline-block px-4 py-2 font-semibold rounded-lg transition duration-300">
                    <i class="fas fa-external-link-alt mr-2"></i>新しいタブで開く
                </a>
            </div>
            <p class="text-xs text-gray-600 mt-2">
                <i class="fas fa-info-circle mr-1"></i>閲覧画面用URLにアクセスすると、選択したプランのみが表示される申込フォームが開きます
            </p>
        </div>
    @endif

    {{-- 保存されているURL一覧 --}}
    @if(isset($savedUrls) && $savedUrls->count() > 0)
        <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-6">
            <div class="p-4 bg-blue-50 border-b border-blue-200">
                <h2 class="text-lg font-bold text-gray-800">
                    <i class="fas fa-list mr-2"></i>保存されているURL一覧
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr class="theme-table-header uppercase text-sm leading-normal">
                            <th class="py-3 px-6 text-left">生成日時</th>
                            <th class="py-3 px-6 text-left">URL</th>
                            <th class="py-3 px-6 text-left">選択されているプラン名（複数対応）</th>
                            <th class="py-3 px-6 text-left">プラン数</th>
                            <th class="py-3 px-6 text-left">有効期限</th>
                            <th class="py-3 px-6 text-left">ステータス</th>
                            <th class="py-3 px-6 text-center">操作</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 text-sm font-light">
                        @foreach($savedUrls as $savedUrl)
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="py-3 px-6 text-left whitespace-nowrap">
                                    {{ $savedUrl->created_at->format('Y/m/d H:i') }}
                                </td>
                                <td class="py-3 px-6 text-left">
                                    <div class="flex items-center space-x-2">
                                        <input type="text" 
                                            value="{{ $savedUrl->url }}" 
                                            readonly
                                            class="flex-1 px-2 py-1 border border-gray-300 rounded bg-white font-mono text-xs"
                                            id="url-{{ $savedUrl->id }}">
                                        <button 
                                            onclick="copyUrl('url-{{ $savedUrl->id }}', 'copy-success-{{ $savedUrl->id }}')" 
                                            class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded transition duration-300">
                                            <i class="fas fa-copy mr-1"></i>コピー
                                        </button>
                                    </div>
                                    <div id="copy-success-{{ $savedUrl->id }}" class="hidden text-green-600 text-xs mt-1">
                                        <i class="fas fa-check mr-1"></i>コピーしました
                                    </div>
                                </td>
                                <td class="py-3 px-6 text-left">
                                    @php
                                        $names = isset($planNames) ? array_map(fn($id) => $planNames[$id] ?? '（削除済み）', $savedUrl->plan_ids ?? []) : [];
                                    @endphp
                                    {{ implode('、', $names) ?: '—' }}
                                </td>
                                <td class="py-3 px-6 text-left">
                                    {{ count($savedUrl->plan_ids) }}件
                                </td>
                                <td class="py-3 px-6 text-left">
                                    {{ $savedUrl->expires_at->format('Y/m/d H:i') }}
                                    @if($savedUrl->isExpired())
                                        <span class="text-red-600 text-xs">（期限切れ）</span>
                                    @endif
                                </td>
                                <td class="py-3 px-6 text-left">
                                    @if($savedUrl->isValid())
                                        <span class="bg-green-200 text-green-600 py-1 px-3 rounded-full text-xs font-semibold">有効</span>
                                    @else
                                        <span class="bg-gray-200 text-gray-600 py-1 px-3 rounded-full text-xs font-semibold">無効</span>
                                    @endif
                                </td>
                                <td class="py-3 px-6 text-center">
                                    <form action="{{ route('admin.contract-forms.destroy', $savedUrl) }}" method="POST" class="inline-confirm-form" data-confirm="このURLを削除してもよろしいですか？">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded transition duration-300">
                                            <i class="fas fa-trash mr-1"></i>削除
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-gray-200">
                {{ $savedUrls->links() }}
            </div>
        </div>
    @endif

    {{-- URL生成フォーム --}}
    <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-link mr-2"></i>新規URL生成
        </h2>

    <form action="{{ route('admin.contract-forms.generate') }}" method="POST" id="generate-form">
        @csrf
        
        <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-6">
            <div class="p-4 bg-blue-50 border-b border-blue-200">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-list mr-2"></i>
                        契約プラン一覧（表示したいプランにチェックを入れてください）
                    </p>
                    <div class="flex items-center space-x-4">
                        <button type="button" 
                            onclick="selectAll()" 
                            class="text-sm text-blue-600 hover:text-blue-800 font-semibold">
                            <i class="fas fa-check-square mr-1"></i>全て選択
                        </button>
                        <button type="button" 
                            onclick="deselectAll()" 
                            class="text-sm text-blue-600 hover:text-blue-800 font-semibold">
                            <i class="fas fa-square mr-1"></i>全て解除
                        </button>
                    </div>
                </div>
            </div>
            
            @if($plans->isEmpty())
                <div class="p-6 text-center text-gray-600">
                    契約プランがありません。
                </div>
            @else
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr class="theme-table-header uppercase text-sm leading-normal">
                            <th class="py-3 px-6 text-left w-12">
                                <input type="checkbox" 
                                    id="select-all-checkbox" 
                                    onclick="toggleAll()"
                                    class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                            </th>
                            <th class="py-3 px-6 text-left">プランコード</th>
                            <th class="py-3 px-6 text-left">プラン名</th>
                            <th class="py-3 px-6 text-left">料金</th>
                            <th class="py-3 px-6 text-left">決済タイプ</th>
                            <th class="py-3 px-6 text-left">カテゴリ</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 text-sm font-light">
                        @foreach ($plans as $plan)
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="py-3 px-6 text-center">
                                    <input type="checkbox" 
                                        name="plan_ids[]" 
                                        value="{{ $plan->id }}"
                                        class="plan-checkbox w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                        {{ in_array($plan->id, old('plan_ids', session('selected_plan_ids', []))) ? 'checked' : '' }}>
                                </td>
                                <td class="py-3 px-6 text-left whitespace-nowrap font-mono font-semibold">{{ $plan->item }}</td>
                                <td class="py-3 px-6 text-left">{{ $plan->name }}</td>
                                <td class="py-3 px-6 text-left font-semibold text-indigo-600">{{ $plan->formatted_price }}</td>
                                <td class="py-3 px-6 text-left">
                                    @if($plan->billing_type === 'monthly')
                                        <span class="bg-blue-200 text-blue-600 py-1 px-3 rounded-full text-xs font-semibold">月額課金</span>
                                    @else
                                        <span class="bg-gray-200 text-gray-600 py-1 px-3 rounded-full text-xs font-semibold">一回限り</span>
                                    @endif
                                </td>
                                <td class="py-3 px-6 text-left">
                                    {{ $plan->contractPlanMaster->name ?? '（カテゴリなし）' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        @error('plan_ids')
            <div class="bg-red-50 border-2 border-red-500 rounded-lg p-4 mb-6">
                <p class="text-red-600">
                    <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                </p>
            </div>
        @enderror

        <div class="flex justify-end">
            <button type="submit" 
                class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-md transition duration-300">
                <i class="fas fa-link mr-2"></i>URL生成
            </button>
        </div>
    </form>
@endsection

@section('scripts')
<script>
function selectAll() {
    document.querySelectorAll('.plan-checkbox').forEach(checkbox => {
        checkbox.checked = true;
    });
    document.getElementById('select-all-checkbox').checked = true;
    updateSelectAllCheckbox();
}

function deselectAll() {
    document.querySelectorAll('.plan-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('select-all-checkbox').checked = false;
    updateSelectAllCheckbox();
}

function toggleAll() {
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    document.querySelectorAll('.plan-checkbox').forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
}

function updateSelectAllCheckbox() {
    const checkboxes = document.querySelectorAll('.plan-checkbox');
    const checked = document.querySelectorAll('.plan-checkbox:checked');
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    
    if (checkboxes.length === 0) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
    } else if (checked.length === checkboxes.length) {
        selectAllCheckbox.checked = true;
        selectAllCheckbox.indeterminate = false;
    } else if (checked.length > 0) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = true;
    } else {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
    }
}

// 個別チェックボックスの変更時に「全て選択」の状態を更新
document.querySelectorAll('.plan-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateSelectAllCheckbox);
});

// ページ読み込み時に「全て選択」の状態を更新
document.addEventListener('DOMContentLoaded', function() {
    updateSelectAllCheckbox();
});

function copyUrl(inputId, successId) {
    const urlInput = document.getElementById(inputId);
    const urlText = urlInput.value;
    
    // モダンブラウザ用のクリップボードAPI（優先）
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(urlText).then(function() {
            showCopySuccess(successId);
        }).catch(function(err) {
            // クリップボードAPIが失敗した場合はフォールバック
            fallbackCopy(urlInput, successId);
        });
    } else {
        // フォールバック: document.execCommandを使用
        fallbackCopy(urlInput, successId);
    }
}

function fallbackCopy(urlInput, successId) {
    urlInput.select();
    urlInput.setSelectionRange(0, 99999); // モバイルデバイス用
    
    try {
        const successful = document.execCommand('copy');
        if (successful) {
            showCopySuccess(successId);
        } else {
            alert('コピーに失敗しました。手動でコピーしてください。');
        }
    } catch (err) {
        alert('コピーに失敗しました。手動でコピーしてください。');
    }
}

function showCopySuccess(successId) {
    const successMessage = document.getElementById(successId);
    if (successMessage) {
        successMessage.classList.remove('hidden');
        setTimeout(function() {
            successMessage.classList.add('hidden');
        }, 3000);
    }
}
</script>
@endsection
