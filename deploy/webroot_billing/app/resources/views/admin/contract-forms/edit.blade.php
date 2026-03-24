@extends('layouts.admin')

@section('title', 'フォーム編集')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.contract-forms.index') }}" class="theme-link font-semibold">
            <i class="fas fa-arrow-left mr-2"></i>フォーム管理に戻る
        </a>
    </div>

    <div class="bg-white shadow-lg rounded-lg p-6 max-w-2xl">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">フォーム編集</h1>

        <div class="mb-4 p-4 bg-gray-50 rounded-lg">
            <p class="text-sm text-gray-600 mb-1">URL</p>
            <p class="font-mono text-sm break-all">{{ $contractFormUrl->url }}</p>
        </div>

        <form action="{{ route('admin.contract-forms.update', $contractFormUrl) }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="mb-4">
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">フォーム名（任意）</label>
                <input type="text" 
                    id="name" 
                    name="name" 
                    value="{{ old('name', $contractFormUrl->name) }}" 
                    maxlength="255"
                    placeholder="例: 法人向け申込フォーム"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg theme-input @error('name') border-red-500 @enderror">
                @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p class="text-xs text-gray-500 mt-1">一覧で識別しやすい名前を付けてください。</p>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">登録する製品（紐付け）</label>
                <p class="text-xs text-gray-500 mb-3">このフォームのURLで選択可能な製品を選んでください。1つ以上必須です。オプションは商品・プラン管理でベースプランに紐付けたものが申込時に表示されます。</p>
                <div class="space-y-3 p-4 bg-gray-50 rounded-lg border border-gray-200 max-h-72 overflow-y-auto">
                    @php
                        $currentPlanIds = old('plan_ids', $contractFormUrl->plan_ids ?? []);
                    @endphp
                    @foreach($plans as $plan)
                        @php $opts = $plan->optionProducts; @endphp
                        <div class="border-b border-gray-200 last:border-0 pb-3 last:pb-0">
                            <label class="flex items-start cursor-pointer">
                                <input type="checkbox"
                                    name="plan_ids[]"
                                    value="{{ $plan->id }}"
                                    {{ in_array($plan->id, $currentPlanIds) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mt-0.5">
                                <span class="ml-2 flex-1">
                                    <span class="text-sm text-gray-800 font-medium">{{ $plan->name }}</span>
                                    <span class="text-xs text-gray-500">（{{ $plan->formatted_price }}）</span>
                                    @if($opts->isEmpty())
                                        <span class="block mt-1 text-[11px] text-gray-400"><i class="fas fa-minus-circle mr-0.5"></i>オプション設定なし</span>
                                    @else
                                        <span class="block mt-1 text-[11px] text-emerald-800">
                                            <i class="fas fa-puzzle-piece mr-0.5"></i>オプションあり: {{ $opts->pluck('name')->implode('、') }}
                                        </span>
                                    @endif
                                </span>
                            </label>
                        </div>
                    @endforeach
                </div>
                @error('plan_ids')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                @error('plan_ids.*')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- 決済処理方法 --}}
            <div class="mb-6">
                <p class="block text-sm font-semibold text-gray-700 mb-2">決済処理方法 <span class="text-red-500">*</span></p>
                @php $currentJobType = old('job_type', $contractFormUrl->job_type ?? 'AUTH'); @endphp
                <div class="flex flex-col sm:flex-row gap-4">
                    <label class="flex items-start gap-3 cursor-pointer p-4 border-2 rounded-lg transition-colors
                        {{ $currentJobType === 'AUTH' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300' }}"
                        id="edit-label-auth">
                        <input type="radio" name="job_type" value="AUTH"
                            class="mt-1 w-4 h-4 text-indigo-600"
                            {{ $currentJobType === 'AUTH' ? 'checked' : '' }}
                            onchange="updateEditJobTypeStyle()">
                        <span>
                            <span class="font-bold text-gray-800 block">仮売上のみ（AUTH）</span>
                            <span class="text-xs text-gray-500">カードを仮押さえし、ROBOT PAYMENT管理画面で手動売上確定。</span>
                        </span>
                    </label>
                    <label class="flex items-start gap-3 cursor-pointer p-4 border-2 rounded-lg transition-colors
                        {{ $currentJobType === 'CAPTURE' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300' }}"
                        id="edit-label-capture">
                        <input type="radio" name="job_type" value="CAPTURE"
                            class="mt-1 w-4 h-4 text-indigo-600"
                            {{ $currentJobType === 'CAPTURE' ? 'checked' : '' }}
                            onchange="updateEditJobTypeStyle()">
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

            <div class="mb-6">
                <label class="flex items-center">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" 
                        name="is_active" 
                        value="1" 
                        {{ old('is_active', $contractFormUrl->is_active) ? 'checked' : '' }}
                        class="rounded border-gray-300 theme-checkbox-accent shadow-sm">
                    <span class="ml-2 text-sm font-medium text-gray-700">このフォームを有効にする</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-6">無効にすると、このURLからの申し込みは受け付けません。</p>
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="btn-cta px-6 py-2 font-bold rounded-lg shadow-md transition duration-300">
                    <i class="fas fa-save mr-2"></i>更新
                </button>
                <a href="{{ route('admin.contract-forms.index') }}" class="px-6 py-2 border border-gray-300 rounded-lg font-semibold text-gray-700 hover:bg-gray-50 transition duration-300">
                    キャンセル
                </a>
            </div>
        </form>
    </div>
@section('scripts')
<script>
function updateEditJobTypeStyle() {
    const authRadio    = document.querySelector('input[name="job_type"][value="AUTH"]');
    const captureRadio = document.querySelector('input[name="job_type"][value="CAPTURE"]');
    const labelAuth    = document.getElementById('edit-label-auth');
    const labelCapture = document.getElementById('edit-label-capture');
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
</script>
@endsection
