@extends('layouts.admin')

@section('title', '契約管理')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">契約管理</h1>
    </div>

    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        @if($contracts->isEmpty())
            <div class="p-6 text-center text-gray-600">
                契約データがありません。
            </div>
        @else
            <table class="min-w-full leading-normal">
                <thead>
                    <tr class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left">ID</th>
                        <th class="py-3 px-6 text-left">会社名</th>
                        <th class="py-3 px-6 text-left">担当者</th>
                        <th class="py-3 px-6 text-left">プラン</th>
                        <th class="py-3 px-6 text-left">金額</th>
                        <th class="py-3 px-6 text-left">ステータス</th>
                        <th class="py-3 px-6 text-left">申込日</th>
                        <th class="py-3 px-6 text-center">アクション</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 text-sm font-light">
                    @foreach ($contracts as $contract)
                        <tr class="border-b border-gray-200 hover:bg-gray-100">
                            <td class="py-3 px-6 text-left whitespace-nowrap">{{ $contract->id }}</td>
                            <td class="py-3 px-6 text-left">{{ $contract->company_name }}</td>
                            <td class="py-3 px-6 text-left">{{ $contract->contact_name }}</td>
                            <td class="py-3 px-6 text-left">{{ $contract->contractPlan->name }}</td>
                            <td class="py-3 px-6 text-left font-semibold">{{ number_format($contract->contractPlan->price) }}円</td>
                            <td class="py-3 px-6 text-left">
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
                                <span class="{{ $colorClass }} py-1 px-3 rounded-full text-xs font-semibold">
                                    {{ $contract->status_label }}
                                </span>
                            </td>
                            <td class="py-3 px-6 text-left">{{ $contract->created_at->format('Y/m/d') }}</td>
                            <td class="py-3 px-6 text-center">
                                <a href="{{ route('admin.contracts.show', $contract->id) }}" class="text-indigo-600 hover:text-indigo-900 font-semibold">
                                    <i class="fas fa-eye mr-1"></i>詳細
                                </a>
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
@endsection

