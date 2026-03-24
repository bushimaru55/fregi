@extends('layouts.admin')

@section('title', 'フォーム管理')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">フォーム管理</h1>
    </div>

    <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
        <p class="text-gray-600 mb-4">
            <i class="fas fa-info-circle mr-2"></i>
            表示したい製品を選択してURLを生成してください。生成されたURLにアクセスすると、選択した製品のみが表示される新規申込フォームが表示されます。
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
                <i class="fas fa-info-circle mr-1"></i>閲覧画面用URLにアクセスすると、選択した製品のみが表示される申込フォームが開きます
            </p>
        </div>
    @endif

    {{-- 保存されているURL一覧 --}}
    @if(isset($savedUrls) && $savedUrls->count() > 0)
        <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-6">
            <div class="p-4 bg-blue-50 border-b border-blue-200">
                <h2 class="text-lg font-bold text-gray-800">
                    <i class="fas fa-list mr-2"></i>発行済みフォーム一覧
                </h2>
                <p class="text-sm text-blue-900/80 mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    「現在の決済設定」列では、<strong>仮売上のみ</strong> / <strong>仮実同時売上</strong>のどちらが有効か、および<strong>フォームで固定</strong>か<strong>サイト全体設定</strong>かを表示します。
                </p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr class="theme-table-header uppercase text-sm leading-normal">
                            <th class="py-3 px-6 text-left">フォーム名</th>
                            <th class="py-3 px-6 text-left">生成日時</th>
                            <th class="py-3 px-6 text-left">URL</th>
                            <th class="py-3 px-6 text-left">選択製品</th>
                            <th class="py-3 px-6 text-left">製品数</th>
                            <th class="py-3 px-6 text-left min-w-[11rem]">
                                <span class="block">現在の決済設定</span>
                                <span class="normal-case text-[10px] font-normal text-gray-500 font-sans tracking-normal">仮売上 / 仮実同時</span>
                            </th>
                            <th class="py-3 px-6 text-left">有効期限</th>
                            <th class="py-3 px-6 text-left">ステータス</th>
                            <th class="py-3 px-6 text-center">操作</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 text-sm font-light">
                        @foreach($savedUrls as $savedUrl)
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="py-3 px-6 text-left">
                                    {{ $savedUrl->name ?: '—' }}
                                </td>
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
                                <td class="py-3 px-6 text-left align-top">
                                    @php
                                        $primary = $savedUrl->jobTypeListPrimaryLabel();
                                        $secondary = $savedUrl->jobTypeListSecondaryLine();
                                    @endphp
                                    @if($savedUrl->job_type === 'AUTH')
                                        <div class="flex flex-col gap-1 max-w-xs">
                                            <span class="inline-flex items-center gap-1.5 w-fit bg-amber-50 border border-amber-200 text-amber-900 py-1.5 px-2.5 rounded-lg text-xs font-bold shadow-sm">
                                                <i class="fas fa-hand-paper text-amber-600" title="仮売上"></i>{{ $primary }}
                                            </span>
                                            <span class="text-[11px] text-gray-600 leading-snug pl-0.5">{{ $secondary }}</span>
                                        </div>
                                    @elseif($savedUrl->job_type === 'CAPTURE')
                                        <div class="flex flex-col gap-1 max-w-xs">
                                            <span class="inline-flex items-center gap-1.5 w-fit bg-blue-50 border border-blue-200 text-blue-900 py-1.5 px-2.5 rounded-lg text-xs font-bold shadow-sm">
                                                <i class="fas fa-bolt text-blue-600" title="仮実同時"></i>{{ $primary }}
                                            </span>
                                            <span class="text-[11px] text-gray-600 leading-snug pl-0.5">{{ $secondary }}</span>
                                        </div>
                                    @else
                                        <div class="flex flex-col gap-1 max-w-xs">
                                            <span class="inline-flex items-center gap-1.5 w-fit bg-slate-100 border border-slate-300 text-slate-800 py-1.5 px-2.5 rounded-lg text-xs font-bold shadow-sm">
                                                <i class="fas fa-cog text-slate-600" title="サイト設定"></i>{{ $primary }}
                                            </span>
                                            <span class="text-[11px] text-gray-600 leading-snug pl-0.5">{{ $secondary }}</span>
                                        </div>
                                    @endif
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
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('admin.contract-forms.edit', $savedUrl) }}" 
                                            class="px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded transition duration-300">
                                            <i class="fas fa-edit mr-1"></i>編集
                                        </a>
                                        <form action="{{ route('admin.contract-forms.destroy', $savedUrl) }}" method="POST" class="inline-confirm-form inline" data-confirm="このフォームを削除してもよろしいですか？">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded transition duration-300">
                                                <i class="fas fa-trash mr-1"></i>削除
                                            </button>
                                        </form>
                                    </div>
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

    {{-- フォーム発行フォーム --}}
    <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-plus-circle mr-2"></i>新規フォームURL発行
        </h2>

    <form action="{{ route('admin.contract-forms.generate') }}" method="POST" id="generate-form">
        @csrf

        <div class="mb-4">
            <label for="form_name" class="block text-sm font-semibold text-gray-700 mb-2">フォーム名（任意）</label>
            <input type="text" 
                id="form_name" 
                name="name" 
                value="{{ old('name') }}" 
                maxlength="255"
                placeholder="例: 法人向け申込フォーム"
                class="w-full max-w-md px-4 py-2 border border-gray-300 rounded-lg theme-input @error('name') border-red-500 @enderror">
            @error('name')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- 決済処理方法 --}}
        <div class="mb-6">
            <p class="block text-sm font-semibold text-gray-700 mb-2">決済処理方法 <span class="text-red-500">*</span></p>
            <div class="flex flex-col sm:flex-row gap-4">
                <label class="flex items-start gap-3 cursor-pointer p-4 border-2 rounded-lg transition-colors
                    {{ old('job_type', 'AUTH') === 'AUTH' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300' }}"
                    id="label-auth">
                    <input type="radio" name="job_type" value="AUTH"
                        class="mt-1 w-4 h-4 text-indigo-600"
                        {{ old('job_type', 'AUTH') === 'AUTH' ? 'checked' : '' }}
                        onchange="updateJobTypeStyle()">
                    <span>
                        <span class="font-bold text-gray-800 block">仮売上のみ（AUTH）</span>
                        <span class="text-xs text-gray-500">カードを仮押さえし、ROBOT PAYMENT管理画面で手動売上確定。</span>
                    </span>
                </label>
                <label class="flex items-start gap-3 cursor-pointer p-4 border-2 rounded-lg transition-colors
                    {{ old('job_type', 'AUTH') === 'CAPTURE' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300' }}"
                    id="label-capture">
                    <input type="radio" name="job_type" value="CAPTURE"
                        class="mt-1 w-4 h-4 text-indigo-600"
                        {{ old('job_type', 'AUTH') === 'CAPTURE' ? 'checked' : '' }}
                        onchange="updateJobTypeStyle()">
                    <span>
                        <span class="font-bold text-gray-800 block">仮実同時売上（CAPTURE）</span>
                        <span class="text-xs text-gray-500">申込時点で即時売上確定。請求管理ロボAPI5も同時実行。</span>
                    </span>
                </label>
            </div>
            @error('job_type')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
        
        <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-6">
            <div class="p-4 bg-blue-50 border-b border-blue-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-blue-800">
                            <i class="fas fa-list mr-2"></i>
                            製品一覧（表示したい製品にチェックを入れてください）
                        </p>
                        <p class="text-xs text-blue-700/90 mt-1 pl-6">
                            「オプション」列は各ベースプランに<strong>申込時に選択可能なオプション</strong>が登録されているかを表示します（商品・プラン管理で紐付け）。
                        </p>
                    </div>
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
                    製品がありません。
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
                            <th class="py-3 px-6 text-left">製品コード</th>
                            <th class="py-3 px-6 text-left">製品名</th>
                            <th class="py-3 px-6 text-left">料金</th>
                            <th class="py-3 px-6 text-left">決済タイプ</th>
                            <th class="py-3 px-6 text-left min-w-[12rem]">オプション</th>
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
                                <td class="py-3 px-6 text-left align-top">
                                    @php $opts = $plan->optionProducts; @endphp
                                    @if($opts->isEmpty())
                                        <span class="inline-flex items-center gap-1 text-xs text-gray-500">
                                            <i class="fas fa-minus-circle"></i>設定なし
                                        </span>
                                    @else
                                        <div class="flex flex-col gap-1.5 max-w-md">
                                            <span class="inline-flex w-fit items-center gap-1 bg-emerald-50 text-emerald-800 border border-emerald-200 px-2 py-0.5 rounded text-[11px] font-semibold">
                                                <i class="fas fa-puzzle-piece"></i>あり（{{ $opts->count() }}件）
                                            </span>
                                            <ul class="text-xs text-gray-700 space-y-0.5 pl-1 border-l-2 border-emerald-200 ml-0.5">
                                                @foreach($opts as $op)
                                                    <li class="leading-snug">
                                                        <span class="font-medium text-gray-800">{{ $op->name }}</span>
                                                        @if($op->code)
                                                            <span class="text-gray-400 font-mono text-[10px] ml-1">({{ $op->code }})</span>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
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
                <i class="fas fa-link mr-2"></i>フォーム発行
            </button>
        </div>
    </form>
@endsection

@section('scripts')
<script>
function updateJobTypeStyle() {
    const authRadio    = document.querySelector('input[name="job_type"][value="AUTH"]');
    const captureRadio = document.querySelector('input[name="job_type"][value="CAPTURE"]');
    const labelAuth    = document.getElementById('label-auth');
    const labelCapture = document.getElementById('label-capture');
    if (!authRadio || !captureRadio) return;

    if (authRadio.checked) {
        labelAuth.classList.add('border-indigo-500', 'bg-indigo-50');
        labelAuth.classList.remove('border-gray-200');
        labelCapture.classList.remove('border-indigo-500', 'bg-indigo-50');
        labelCapture.classList.add('border-gray-200');
    } else {
        labelCapture.classList.add('border-indigo-500', 'bg-indigo-50');
        labelCapture.classList.remove('border-gray-200');
        labelAuth.classList.remove('border-indigo-500', 'bg-indigo-50');
        labelAuth.classList.add('border-gray-200');
    }
}

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
