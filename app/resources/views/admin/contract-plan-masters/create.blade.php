@extends('layouts.admin')

@section('title', '契約プランマスター新規作成')

@section('content')
    <h1 class="text-3xl font-bold text-gray-800 mb-6">契約プランマスター新規作成</h1>

    <div class="bg-white shadow-lg rounded-lg p-6">
        <form action="{{ route('admin.contract-plan-masters.store') }}" method="POST">
            @csrf
            @include('admin.contract-plan-masters._form')
            <div class="flex justify-end mt-6">
                <a href="{{ route('admin.contract-plan-masters.index') }}" class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white font-bold rounded-lg shadow-md transition duration-300 mr-2">
                    キャンセル
                </a>
                <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg shadow-md transition duration-300">
                    作成
                </button>
            </div>
        </form>
    </div>
@endsection

