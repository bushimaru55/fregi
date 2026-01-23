<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'DSchatbot') - F-REGI決済管理</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #a8e6cf 0%, #88d8c0 50%, #b8e6d3 100%);
        }
        .card-shadow {
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-50">
    {{-- フロントエンドエラーログ用のスクリプト（本番環境でも有効） --}}
    <script>
        // グローバルエラーハンドラ：未処理のJavaScriptエラーをキャッチ
        window.addEventListener('error', function(event) {
            console.error('JavaScriptエラー:', {
                message: event.message,
                filename: event.filename,
                lineno: event.lineno,
                colno: event.colno,
                error: event.error ? event.error.stack : null,
                url: window.location.href,
                userAgent: navigator.userAgent,
                timestamp: new Date().toISOString(),
            });
            
            // エラー詳細をサーバーに送信（オプション：必要に応じて有効化）
            // fetch('/api/error-log', {
            //     method: 'POST',
            //     headers: {
            //         'Content-Type': 'application/json',
            //         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            //     },
            //     body: JSON.stringify({
            //         message: event.message,
            //         filename: event.filename,
            //         lineno: event.lineno,
            //         colno: event.colno,
            //         stack: event.error ? event.error.stack : null,
            //         url: window.location.href,
            //         userAgent: navigator.userAgent,
            //     })
            // }).catch(err => console.error('エラーログ送信失敗:', err));
        });
        
        // Promise rejection のハンドリング
        window.addEventListener('unhandledrejection', function(event) {
            console.error('未処理のPromise rejection:', {
                reason: event.reason,
                promise: event.promise,
                url: window.location.href,
                userAgent: navigator.userAgent,
                timestamp: new Date().toISOString(),
            });
        });
        
        // ページロード完了時にデバッグ情報をログに記録（本番環境では最小限）
        window.addEventListener('load', function() {
            if (window.console && window.console.log) {
                console.log('ページロード完了:', {
                    url: window.location.href,
                    timestamp: new Date().toISOString(),
                    userAgent: navigator.userAgent,
                });
            }
        });
    </script>
    <!-- Header -->
    <header class="gradient-bg text-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <img src="{{ asset('images/dschatbot_logo.svg') }}" alt="DSchatbot" class="h-12 w-auto">
                </div>
                <nav class="hidden md:flex space-x-6">
                    @php
                        // 管理画面で設定された製品ページのURLを取得（設定がない場合はデフォルト）
                        $productPageUrl = \App\Models\SiteSetting::getTextValue('product_page_url', 'https://www.dschatbot.ai/');
                    @endphp
                    <a href="{{ $productPageUrl }}" target="_blank" class="hover:text-indigo-200 transition">
                        <i class="fas fa-arrow-left mr-2"></i>製品ページへ戻る
                    </a>
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

        @if(session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-3"></i>
                    <p>{{ session('error') }}</p>
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
                <p class="text-sm text-gray-400">© 2026 DSchatbot. All rights reserved.</p>
                <p class="text-sm text-gray-400">Powered by Laravel 10 & F-REGI</p>
            </div>
        </div>
    </footer>
</body>
</html>

