<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'DSchatbot') - 申込管理</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Filament Styles -->
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filament/support/support.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filament/forms/forms.css') }}">
    
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif; }
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
        /* Filamentのselectスタイルをリセット（管理画面の通常のselect要素用） */
        select:not([class*="fi-"]):not([class*="filament-"]):not([data-filament-select]) {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") !important;
            background-position: right 0.5rem center !important;
            background-repeat: no-repeat !important;
            background-size: 1.5em 1.5em !important;
            padding-right: 2.5rem !important;
            appearance: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
        }
        /* Filamentの疑似要素を無効化 */
        select:not([class*="fi-"]):not([class*="filament-"]):not([data-filament-select])::before,
        select:not([class*="fi-"]):not([class*="filament-"]):not([data-filament-select])::after,
        select.native-select::before,
        select.native-select::after {
            display: none !important;
            content: none !important;
            background: none !important;
        }
    </style>
    @livewireStyles
    @stack('styles')
</head>
<body class="theme-page">
    <!-- Header -->
    <header class="admin-header shadow-sm">
        <div class="container mx-auto admin-content-inner py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center shrink-0 admin-logo-wrap">
                    <a href="{{ route('admin.dashboard') }}" class="block admin-logo-link">
                        <img src="{{ asset('images/DSchatbot_logo.png') }}" alt="DSchatbot" class="h-12 w-auto cursor-pointer block admin-logo-img">
                    </a>
                </div>
                <nav class="hidden md:flex items-center flex-wrap gap-x-5 gap-y-0">
                    <a href="{{ route('admin.contracts.index') }}" class="hover:opacity-90 transition whitespace-nowrap">
                        <i class="fas fa-list-alt mr-2"></i>申し込み一覧
                    </a>
                    <a href="{{ route('admin.contract-plan-masters.index') }}" class="hover:opacity-90 transition whitespace-nowrap">
                        <i class="fas fa-folder-open mr-2"></i>契約プランマスター
                    </a>
                    <a href="{{ route('admin.contract-plans.index') }}" class="hover:opacity-90 transition whitespace-nowrap">
                        <i class="fas fa-layer-group mr-2"></i>製品管理
                    </a>
                    <a href="{{ route('admin.contract-forms.index') }}" class="hover:opacity-90 transition whitespace-nowrap">
                        <i class="fas fa-link mr-2"></i>リンク発行
                    </a>
                    <a href="{{ route('admin.contract-statuses.index') }}" class="hover:opacity-90 transition whitespace-nowrap">
                        <i class="fas fa-tags mr-2"></i>ステータスマスター
                    </a>
                    <a href="{{ route('admin.site-settings.index') }}" class="hover:opacity-90 transition whitespace-nowrap">
                        <i class="fas fa-globe mr-2"></i>サイト管理
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="hover:opacity-90 transition whitespace-nowrap">
                        <i class="fas fa-user-shield mr-2"></i>管理者管理
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="inline shrink-0">
                        @csrf
                        <button type="submit" class="hover:opacity-90 transition whitespace-nowrap">
                            <i class="fas fa-sign-out-alt mr-2"></i>ログアウト
                        </button>
                    </form>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto admin-content-inner py-8">
        @if(session('success'))
            <div class="theme-alert-success p-4 mb-6 rounded-r">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-3"></i>
                    <p>{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="theme-alert-error p-4 mb-6 rounded-r">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-3"></i>
                    <p>{{ session('error') }}</p>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="theme-alert-error p-4 mb-6 rounded-r">
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
    <footer class="theme-footer mt-16">
        <div class="container mx-auto px-4 py-6">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-sm opacity-90">© 2026 DSchatbot. All rights reserved.</p>
                <p class="text-sm opacity-80">Powered by Laravel 10</p>
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
            // すべてのselect要素（Filamentのものを除く）にスタイルを適用
            const allSelects = document.querySelectorAll('select:not([class*="fi-"]):not([class*="filament-"]):not([data-filament-select])');
            allSelects.forEach(function(select) {
                // Filamentのコンポーネント内でないことを確認
                if (!select.closest('.fi-fo-field-wrapper') && !select.closest('[wire\\:id]')) {
                    // インラインスタイルで強制的に適用（既存の背景画像を上書き）
                    select.style.setProperty('background-image', "url(\"data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e\")", 'important');
                    select.style.setProperty('background-position', 'right 0.5rem center', 'important');
                    select.style.setProperty('background-repeat', 'no-repeat', 'important');
                    select.style.setProperty('background-size', '1.5em 1.5em', 'important');
                    select.style.setProperty('padding-right', '2.5rem', 'important');
                    select.style.setProperty('appearance', 'none', 'important');
                    select.style.setProperty('-webkit-appearance', 'none', 'important');
                    select.style.setProperty('-moz-appearance', 'none', 'important');
                }
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
    {{-- 削除・確認系フォーム: 画面中央モーダルで確認 --}}
    <div id="confirm-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;" onclick="window._confirmModalBgClick && window._confirmModalBgClick(event)">
        <div style="background:white;border-radius:0.5rem;padding:1.5rem;max-width:400px;width:90%;box-shadow:0 10px 25px rgba(0,0,0,0.2);pointer-events:auto;" onclick="event.stopPropagation()">
            <p id="confirm-modal-message" style="font-size:1rem;color:#333;margin-bottom:1.5rem;text-align:center;"></p>
            <div style="display:flex;justify-content:center;gap:1rem;">
                <button type="button" id="confirm-modal-ok" onclick="window._confirmModalOk && window._confirmModalOk()" style="padding:0.5rem 1.5rem;background:#dc2626;color:white;border:none;border-radius:0.375rem;font-weight:600;cursor:pointer;pointer-events:auto;">OK</button>
                <button type="button" id="confirm-modal-cancel" onclick="window._confirmModalCancel && window._confirmModalCancel()" style="padding:0.5rem 1.5rem;background:#6b7280;color:white;border:none;border-radius:0.375rem;font-weight:600;cursor:pointer;pointer-events:auto;">キャンセル</button>
            </div>
        </div>
    </div>
    <script>
    (function() {
        var modal, modalMsg, currentForm = null;
        window._confirmModalOk = function() {
            if (currentForm) currentForm.submit();
            hideModal();
        };
        window._confirmModalCancel = function() { hideModal(); };
        window._confirmModalBgClick = function(e) {
            if (e.target.id === 'confirm-modal') hideModal();
        };
        function showModal(msg, form) {
            currentForm = form;
            modal = document.getElementById('confirm-modal');
            modalMsg = document.getElementById('confirm-modal-message');
            if (modalMsg) modalMsg.textContent = msg;
            if (modal) modal.style.display = 'flex';
        }
        function hideModal() {
            modal = document.getElementById('confirm-modal');
            if (modal) modal.style.display = 'none';
            currentForm = null;
        }
        function run() {
            document.addEventListener('click', function(e) {
                var form = e.target.closest('form.inline-confirm-form');
                var btn = form && e.target.closest('button[type="submit"]');
                if (!form || !btn) return;
                e.preventDefault();
                e.stopPropagation();
                showModal(form.getAttribute('data-confirm') || 'よろしいですか？', form);
            }, true);
        }
        if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', run); } else { run(); }
    })();
    </script>
    @stack('scripts')
    @yield('scripts')
</body>
</html>

