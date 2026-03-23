@extends('layouts.admin')

@section('title', '請求サイクル設定')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-8">
        <a href="{{ route('admin.site-settings.index') }}" class="theme-link font-semibold">
            <i class="fas fa-arrow-left mr-2"></i>サイト管理に戻る
        </a>
    </div>

    <h2 class="text-3xl font-bold text-gray-800 mb-2">
        <i class="fas fa-calendar-alt theme-price mr-3"></i>請求サイクル設定
    </h2>
    <p class="text-gray-600 mb-6">月末5営業日ルールに基づく請求書発行日・送付日・決済期限を設定します。請求管理ロボ API（API1・API3）に渡す相対月/日です。</p>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <h4 class="font-semibold text-blue-800 mb-2"><i class="fas fa-info-circle mr-2"></i>月・日の意味</h4>
        <ul class="text-blue-700 text-sm space-y-1 list-disc list-inside">
            <li><strong>月</strong>: 0=当月 / 1=翌月 / 2=翌々月（申込月からのオフセット）</li>
            <li><strong>日</strong>: 1〜31 または 99=末日</li>
        </ul>
    </div>

    <div class="bg-white rounded-xl card-shadow p-8">
        <form action="{{ route('admin.site-settings.billing-cycle.update') }}" method="POST">
            @csrf
            @method('PUT')

            @php
                $monthOptions = [0 => '当月', 1 => '翌月', 2 => '翌々月'];
                $dayOptions = array_combine(range(1, 31), array_map(fn($d) => $d . '日', range(1, 31)));
                $dayOptions[99] = '末日';
                ksort($dayOptions);
            @endphp

            {{-- 月末5営業日以内の申込 --}}
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-800 border-b border-gray-200 pb-2 mb-4">
                    <i class="fas fa-check-circle theme-price mr-2"></i>月末5営業日以内の申込
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">請求書発行日</label>
                        <div class="flex gap-2 items-center">
                            <select name="within_issue_month" class="flex-1 rounded-lg border-gray-300 theme-input text-sm" required>
                                @foreach($monthOptions as $v => $l) <option value="{{ $v }}" {{ old('within_issue_month', $schedule['within']['issue_month'] ?? 0) == $v ? 'selected' : '' }}>{{ $l }}</option> @endforeach
                            </select>
                            <select name="within_issue_day" class="flex-1 rounded-lg border-gray-300 theme-input text-sm" required>
                                @foreach($dayOptions as $v => $l) <option value="{{ $v }}" {{ old('within_issue_day', $schedule['within']['issue_day'] ?? 99) == $v ? 'selected' : '' }}>{{ $l }}</option> @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">請求書送付日</label>
                        <div class="flex gap-2 items-center">
                            <select name="within_sending_month" class="flex-1 rounded-lg border-gray-300 theme-input text-sm" required>
                                @foreach($monthOptions as $v => $l) <option value="{{ $v }}" {{ old('within_sending_month', $schedule['within']['sending_month'] ?? 0) == $v ? 'selected' : '' }}>{{ $l }}</option> @endforeach
                            </select>
                            <select name="within_sending_day" class="flex-1 rounded-lg border-gray-300 theme-input text-sm" required>
                                @foreach($dayOptions as $v => $l) <option value="{{ $v }}" {{ old('within_sending_day', $schedule['within']['sending_day'] ?? 99) == $v ? 'selected' : '' }}>{{ $l }}</option> @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">決済期限</label>
                        <div class="flex gap-2 items-center">
                            <select name="within_deadline_month" class="flex-1 rounded-lg border-gray-300 theme-input text-sm" required>
                                @foreach($monthOptions as $v => $l) <option value="{{ $v }}" {{ old('within_deadline_month', $schedule['within']['deadline_month'] ?? 1) == $v ? 'selected' : '' }}>{{ $l }}</option> @endforeach
                            </select>
                            <select name="within_deadline_day" class="flex-1 rounded-lg border-gray-300 theme-input text-sm" required>
                                @foreach($dayOptions as $v => $l) <option value="{{ $v }}" {{ old('within_deadline_day', $schedule['within']['deadline_day'] ?? 1) == $v ? 'selected' : '' }}>{{ $l }}</option> @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 月末5営業日以降の申込 --}}
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-800 border-b border-gray-200 pb-2 mb-4">
                    <i class="fas fa-clock theme-price mr-2"></i>月末5営業日以降の申込
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">請求書発行日</label>
                        <div class="flex gap-2 items-center">
                            <select name="after_issue_month" class="flex-1 rounded-lg border-gray-300 theme-input text-sm" required>
                                @foreach($monthOptions as $v => $l) <option value="{{ $v }}" {{ old('after_issue_month', $schedule['after']['issue_month'] ?? 1) == $v ? 'selected' : '' }}>{{ $l }}</option> @endforeach
                            </select>
                            <select name="after_issue_day" class="flex-1 rounded-lg border-gray-300 theme-input text-sm" required>
                                @foreach($dayOptions as $v => $l) <option value="{{ $v }}" {{ old('after_issue_day', $schedule['after']['issue_day'] ?? 99) == $v ? 'selected' : '' }}>{{ $l }}</option> @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">請求書送付日</label>
                        <div class="flex gap-2 items-center">
                            <select name="after_sending_month" class="flex-1 rounded-lg border-gray-300 theme-input text-sm" required>
                                @foreach($monthOptions as $v => $l) <option value="{{ $v }}" {{ old('after_sending_month', $schedule['after']['sending_month'] ?? 1) == $v ? 'selected' : '' }}>{{ $l }}</option> @endforeach
                            </select>
                            <select name="after_sending_day" class="flex-1 rounded-lg border-gray-300 theme-input text-sm" required>
                                @foreach($dayOptions as $v => $l) <option value="{{ $v }}" {{ old('after_sending_day', $schedule['after']['sending_day'] ?? 99) == $v ? 'selected' : '' }}>{{ $l }}</option> @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">決済期限</label>
                        <div class="flex gap-2 items-center">
                            <select name="after_deadline_month" class="flex-1 rounded-lg border-gray-300 theme-input text-sm" required>
                                @foreach($monthOptions as $v => $l) <option value="{{ $v }}" {{ old('after_deadline_month', $schedule['after']['deadline_month'] ?? 2) == $v ? 'selected' : '' }}>{{ $l }}</option> @endforeach
                            </select>
                            <select name="after_deadline_day" class="flex-1 rounded-lg border-gray-300 theme-input text-sm" required>
                                @foreach($dayOptions as $v => $l) <option value="{{ $v }}" {{ old('after_deadline_day', $schedule['after']['deadline_day'] ?? 1) == $v ? 'selected' : '' }}>{{ $l }}</option> @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            @if($errors->any())
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <ul class="text-red-700 text-sm list-disc list-inside">
                        @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex gap-4">
                <button type="submit" class="btn-cta px-6 py-2 font-bold rounded-lg shadow-md transition duration-300">
                    <i class="fas fa-save mr-2"></i>更新
                </button>
                <a href="{{ route('admin.site-settings.index') }}" class="px-6 py-2 border border-gray-300 rounded-lg font-semibold text-gray-700 hover:bg-gray-50 transition duration-300 inline-block">
                    キャンセル
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
