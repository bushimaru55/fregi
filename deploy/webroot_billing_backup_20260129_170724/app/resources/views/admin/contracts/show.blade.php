@extends('layouts.admin')

@section('title', '契約詳細')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.contracts.index') }}" class="text-indigo-600 hover:text-indigo-900 font-semibold">
            <i class="fas fa-arrow-left mr-2"></i>契約一覧に戻る
        </a>
    </div>

    <h1 class="text-3xl font-bold text-gray-800 mb-6">契約詳細 #{{ $contract->id }}</h1>

    {{-- 契約情報 --}}
    <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-3 border-b-2 border-indigo-500">
            <i class="fas fa-file-contract mr-2"></i>契約情報
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm text-gray-600 mb-1">契約ID</p>
                <p class="text-lg font-semibold text-gray-800">{{ $contract->id }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">ステータス</p>
                @php
                    $statusColors = [
                        'draft' => 'bg-gray-200 text-gray-600',
                        'pending_payment' => 'bg-yellow-200 text-yellow-800',
                        'active' => 'bg-green-200 text-green-800',
                        'canceled' => 'bg-red-200 text-red-800',
                        'expired' => 'bg-gray-300 text-gray-700',
                    ];
                    $colorClass = $statusColors[$contract->status] ?? 'bg-gray-200 text-gray-600';
                @endphp
                <span class="{{ $colorClass }} py-2 px-4 rounded-full text-sm font-semibold inline-block">
                    {{ $contract->status_label }}
                </span>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">契約プラン</p>
                <p class="text-lg font-semibold text-gray-800">{{ $contract->contractPlan->name }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">料金</p>
                <p class="text-lg font-bold text-indigo-600">{{ number_format($contract->contractPlan->price) }}円（税込）</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">利用開始希望日</p>
                <p class="text-lg font-semibold text-gray-800">{{ $contract->desired_start_date->format('Y年m月d日') }}</p>
            </div>
            @if($contract->actual_start_date)
            <div>
                <p class="text-sm text-gray-600 mb-1">実際の利用開始日</p>
                <p class="text-lg font-semibold text-gray-800">{{ $contract->actual_start_date->format('Y年m月d日') }}</p>
            </div>
            @endif
            <div>
                <p class="text-sm text-gray-600 mb-1">申込日時</p>
                <p class="text-lg font-semibold text-gray-800">{{ $contract->created_at->format('Y年m月d日 H:i') }}</p>
            </div>
        </div>
    </div>

    {{-- 申込企業情報 --}}
    <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-3 border-b-2 border-indigo-500">
            <i class="fas fa-building mr-2"></i>申込企業情報
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm text-gray-600 mb-1">会社名</p>
                <p class="text-lg font-semibold text-gray-800">{{ $contract->company_name }}</p>
            </div>
            @if($contract->company_name_kana)
            <div>
                <p class="text-sm text-gray-600 mb-1">会社名（フリガナ）</p>
                <p class="text-lg font-semibold text-gray-800">{{ $contract->company_name_kana }}</p>
            </div>
            @endif
            @if($contract->department)
            <div>
                <p class="text-sm text-gray-600 mb-1">部署名</p>
                <p class="text-lg font-semibold text-gray-800">{{ $contract->department }}</p>
            </div>
            @endif
            @if($contract->position)
            <div>
                <p class="text-sm text-gray-600 mb-1">役職</p>
                <p class="text-lg font-semibold text-gray-800">{{ $contract->position }}</p>
            </div>
            @endif
            <div>
                <p class="text-sm text-gray-600 mb-1">担当者名</p>
                <p class="text-lg font-semibold text-gray-800">{{ $contract->contact_name }}</p>
            </div>
            @if($contract->contact_name_kana)
            <div>
                <p class="text-sm text-gray-600 mb-1">担当者名（フリガナ）</p>
                <p class="text-lg font-semibold text-gray-800">{{ $contract->contact_name_kana }}</p>
            </div>
            @endif
            <div>
                <p class="text-sm text-gray-600 mb-1">メールアドレス</p>
                <p class="text-lg font-semibold text-gray-800">{{ $contract->email }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">電話番号</p>
                <p class="text-lg font-semibold text-gray-800">{{ $contract->phone }}</p>
            </div>
            @if($contract->full_address)
            <div class="md:col-span-2">
                <p class="text-sm text-gray-600 mb-1">住所</p>
                <p class="text-lg font-semibold text-gray-800">{{ $contract->full_address }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- ご利用情報 --}}
    <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-3 border-b-2 border-indigo-500">
            <i class="fas fa-globe mr-2"></i>ご利用情報
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm text-gray-600 mb-1">ご利用URL・ドメイン</p>
                <p class="text-lg font-semibold text-gray-800">{{ $contract->usage_url_domain ?? '—' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">体験版からのインポートを希望する</p>
                <p class="text-lg font-semibold text-gray-800">{{ $contract->import_from_trial ? 'はい' : 'いいえ' }}</p>
            </div>
        </div>
    </div>

    {{-- 契約内容（オプション商品） --}}
    @php $optionItems = $contract->contractItems->whereNotNull('product_id'); @endphp
    @if($optionItems->isNotEmpty())
    <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-3 border-b-2 border-indigo-500">
            <i class="fas fa-puzzle-piece mr-2"></i>オプション商品
        </h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-gray-200 text-gray-600 uppercase text-xs">
                        <th class="py-2 px-4 text-left">商品名</th>
                        <th class="py-2 px-4 text-left">コード</th>
                        <th class="py-2 px-4 text-right">単価（税込）</th>
                        <th class="py-2 px-4 text-right">数量</th>
                        <th class="py-2 px-4 text-right">小計</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($optionItems as $item)
                    <tr class="border-b border-gray-200">
                        <td class="py-2 px-4">{{ $item->product_name }}</td>
                        <td class="py-2 px-4">{{ $item->product_code ?? '—' }}</td>
                        <td class="py-2 px-4 text-right">{{ number_format($item->unit_price) }}円</td>
                        <td class="py-2 px-4 text-right">{{ $item->quantity }}</td>
                        <td class="py-2 px-4 text-right">{{ number_format($item->subtotal) }}円</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- 決済情報 --}}
    @if($contract->payment)
    <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-3 border-b-2 border-indigo-500">
            <i class="fas fa-credit-card mr-2"></i>決済情報
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm text-gray-600 mb-1">注文番号</p>
                <p class="text-lg font-mono font-semibold text-gray-800">{{ $contract->payment->orderid }}</p>
            </div>
            @if($contract->payment->receiptno)
            <div>
                <p class="text-sm text-gray-600 mb-1">F-REGI受付番号</p>
                <p class="text-lg font-mono font-semibold text-gray-800">{{ $contract->payment->receiptno }}</p>
            </div>
            @endif
            @if($contract->payment->slipno)
            <div>
                <p class="text-sm text-gray-600 mb-1">F-REGI伝票番号</p>
                <p class="text-lg font-mono font-semibold text-gray-800">{{ $contract->payment->slipno }}</p>
            </div>
            @endif
            <div>
                <p class="text-sm text-gray-600 mb-1">決済金額</p>
                <p class="text-lg font-bold text-gray-800">{{ number_format($contract->payment->amount) }}円</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">決済ステータス</p>
                <p class="text-lg font-semibold text-gray-800">{{ $contract->payment->status }}</p>
            </div>
            @if($contract->payment->requested_at)
            <div>
                <p class="text-sm text-gray-600 mb-1">決済要求日時</p>
                <p class="text-lg font-semibold text-gray-800">{{ $contract->payment->requested_at->format('Y年m月d日 H:i') }}</p>
            </div>
            @endif
            @if($contract->payment->completed_at)
            <div>
                <p class="text-sm text-gray-600 mb-1">決済完了日時</p>
                <p class="text-lg font-semibold text-gray-800">{{ $contract->payment->completed_at->format('Y年m月d日 H:i') }}</p>
            </div>
            @endif
        </div>

        {{-- 決済イベント履歴 --}}
        @if($contract->payment->events->isNotEmpty())
        <div class="mt-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">決済イベント履歴</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-200 text-gray-600 uppercase text-xs">
                            <th class="py-2 px-4 text-left">日時</th>
                            <th class="py-2 px-4 text-left">イベントタイプ</th>
                            <th class="py-2 px-4 text-left">詳細</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($contract->payment->events->sortByDesc('created_at') as $event)
                        <tr class="border-b border-gray-200">
                            <td class="py-2 px-4">{{ $event->created_at->format('Y/m/d H:i:s') }}</td>
                            <td class="py-2 px-4">{{ $event->event_type }}</td>
                            <td class="py-2 px-4">
                                <pre class="text-xs bg-gray-100 p-2 rounded overflow-auto">{{ json_encode($event->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- 備考 --}}
    @if($contract->notes)
    <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-3 border-b-2 border-indigo-500">
            <i class="fas fa-sticky-note mr-2"></i>備考
        </h2>
        <p class="text-gray-700 whitespace-pre-wrap">{{ $contract->notes }}</p>
    </div>
    @endif
@endsection

