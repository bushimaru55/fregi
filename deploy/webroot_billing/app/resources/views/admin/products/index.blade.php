@extends('layouts.admin')

@section('title', '商品管理')

@section('content')
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">商品管理</h1>
        <a href="{{ route('admin.products.create') }}" class="btn-cta font-bold py-2 px-4 rounded-lg shadow-md transition duration-300">
            <i class="fas fa-plus mr-2"></i>新規作成
        </a>
    </div>

    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        @if($products->isEmpty())
            <div class="p-6 text-center text-gray-600">
                商品データがありません。
            </div>
        @else
            <table class="min-w-full leading-normal">
                <thead>
                    <tr class="theme-table-header uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left">ID</th>
                        <th class="py-3 px-6 text-left">商品コード</th>
                        <th class="py-3 px-6 text-left">商品名</th>
                        <th class="py-3 px-6 text-left">種別</th>
                        <th class="py-3 px-6 text-left">決済タイプ</th>
                        <th class="py-3 px-6 text-left">単価</th>
                        <th class="py-3 px-6 text-left">状態</th>
                        <th class="py-3 px-6 text-center">アクション</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 text-sm font-light">
                    @foreach ($products as $product)
                        <tr class="border-b border-gray-200 hover:bg-gray-100">
                            <td class="py-3 px-6 text-left whitespace-nowrap">{{ $product->id }}</td>
                            <td class="py-3 px-6 text-left font-mono">{{ $product->code }}</td>
                            <td class="py-3 px-6 text-left">{{ $product->name }}</td>
                            <td class="py-3 px-6 text-left">
                                @php
                                    $typeColors = [
                                        'plan' => 'bg-blue-200 text-blue-800',
                                        'option' => 'bg-green-200 text-green-800',
                                        'addon' => 'bg-purple-200 text-purple-800',
                                    ];
                                    $colorClass = $typeColors[$product->type] ?? 'bg-gray-200 text-gray-600';
                                @endphp
                                <span class="{{ $colorClass }} py-1 px-3 rounded-full text-xs font-semibold">
                                    {{ $product->type_label }}
                                </span>
                            </td>
                            <td class="py-3 px-6 text-left">
                                @if($product->type === 'option')
                                    @if(($product->billing_type ?? 'one_time') === 'monthly')
                                        <span class="px-2 py-0.5 rounded text-xs theme-price" style="background-color: var(--color-primary-soft);">月額課金</span>
                                    @else
                                        <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded text-xs">一回限り</span>
                                    @endif
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-6 text-left font-semibold">{{ $product->formatted_price }}</td>
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
@endsection

