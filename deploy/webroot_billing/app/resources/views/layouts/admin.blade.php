<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Billing System') - F-REGI決済管理</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Filament Styles -->
    <link rel="stylesheet" href="{{ asset('css/filament/support/support.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filament/forms/forms.css') }}">
    
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-shadow {
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        /* Filament RichEditor 調整 */
        .fi-fo-rich-editor .tiptap {
            min-height: 300px;
        }
        /* 通常のselect要素がFilamentのCSSの影響を受けないようにリセット */
        select.native-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") !important;
            background-position: right 0.5rem center !important;
            background-repeat: no-repeat !important;
            background-size: 1.5em 1.5em !important;
            padding-right: 2.5rem !important;
            appearance: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
        }
        /* select要素の重複する背景画像や疑似要素をクリア */
        select.native-select::before,
        select.native-select::after {
            display: none !important;
            content: none !important;
        }
    </style>
    @livewireStyles
    @stack('styles')
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="gradient-bg text-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <i class="fas fa-credit-card text-3xl"></i>
                    <div>
                        <h1 class="text-2xl font-bold">Billing System</h1>
                        <p class="text-sm text-indigo-200">F-REGI決済管理システム</p>
                    </div>
                </div>
                <nav class="hidden md:flex space-x-6">
                    <a href="{{ url('/billing/') }}" class="hover:text-indigo-200 transition">
                        <i class="fas fa-home mr-2"></i>ホーム
                    </a>
                    <a href="{{ route('admin.dashboard') }}" class="hover:text-indigo-200 transition">
                        <i class="fas fa-tachometer-alt mr-2"></i>ダッシュボード
                    </a>
                    <a href="{{ route('admin.contracts.index') }}" class="hover:text-indigo-200 transition">
                        <i class="fas fa-list-alt mr-2"></i>契約管理
                    </a>
                    <a href="{{ route('admin.contract-plan-masters.index') }}" class="hover:text-indigo-200 transition">
                        <i class="fas fa-folder-open mr-2"></i>契約プランマスター管理
                    </a>
                    <a href="{{ route('admin.contract-plans.index') }}" class="hover:text-indigo-200 transition">
                        <i class="fas fa-layer-group mr-2"></i>契約プラン管理
                    </a>
                    <a href="{{ route('admin.contract-forms.index') }}" class="hover:text-indigo-200 transition">
                        <i class="fas fa-link mr-2"></i>新規申込フォーム管理
                    </a>
                    <a href="{{ route('admin.fregi-configs.index') }}" class="hover:text-indigo-200 transition">
                        <i class="fas fa-cog mr-2"></i>F-REGI設定
                    </a>
                    <a href="{{ route('admin.site-settings.index') }}" class="hover:text-indigo-200 transition">
                        <i class="fas fa-globe mr-2"></i>サイト管理
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="hover:text-indigo-200 transition">
                            <i class="fas fa-sign-out-alt mr-2"></i>ログアウト
                        </button>
                    </form>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-3"></i>
                    <p>{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle mr-3 mt-1"></i>
                    <div>
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-16">
        <div class="container mx-auto px-4 py-6">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-sm text-gray-400">© 2026 Billing System. All rights reserved.</p>
                <p class="text-sm text-gray-400">Powered by Laravel 10 & F-REGI</p>
            </div>
        </div>
    </footer>
    
    <!-- Filament Scripts -->
    <script src="{{ asset('js/filament/support/support.js') }}"></script>
    <script src="{{ asset('js/filament/forms/components/rich-editor.js') }}"></script>
    @livewireScripts
    
    <!-- Fix for native-select elements -->
    <script>
        function applyNativeSelectStyles() {
            // すべてのnative-selectクラスを持つselect要素にスタイルを適用
            const nativeSelects = document.querySelectorAll('select.native-select');
            nativeSelects.forEach(function(select) {
                // インラインスタイルで強制的に適用
                select.style.setProperty('background-image', "url(\"data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e\")", 'important');
                select.style.setProperty('background-position', 'right 0.5rem center', 'important');
                select.style.setProperty('background-repeat', 'no-repeat', 'important');
                select.style.setProperty('background-size', '1.5em 1.5em', 'important');
                select.style.setProperty('padding-right', '2.5rem', 'important');
                select.style.setProperty('appearance', 'none', 'important');
                select.style.setProperty('-webkit-appearance', 'none', 'important');
                select.style.setProperty('-moz-appearance', 'none', 'important');
            });
        }
        
        // DOMContentLoaded時に実行
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', applyNativeSelectStyles);
        } else {
            applyNativeSelectStyles();
        }
        
        // Livewireの更新後にも実行
        document.addEventListener('livewire:init', function() {
            Livewire.hook('morph.updated', function() {
                setTimeout(applyNativeSelectStyles, 100);
            });
        });
        
        // DOM変更を監視（MutationObserver）
        const observer = new MutationObserver(function(mutations) {
            applyNativeSelectStyles();
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    </script>
    
    @yield('scripts')
</body>
</html>

