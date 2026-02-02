<?php

namespace App\Providers;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // /billing/ 等のサブディレクトリで動作している場合、フォーム送信先など
        // 生成URLにベースパスを含める（削除ボタン等が正しいURLに送信されるようにする）
        if (! $this->app->runningInConsole()) {
            $basePath = Request::getBasePath();
            if ($basePath !== '') {
                URL::forceRootUrl(Request::getSchemeAndHttpHost() . $basePath);
            } else {
                $appUrl = config('app.url');
                $path = is_string($appUrl) ? parse_url($appUrl, PHP_URL_PATH) : null;
                if (is_string($appUrl) && $appUrl !== '' && $path !== null && $path !== '' && $path !== '/') {
                    URL::forceRootUrl($appUrl);
                }
            }
        }

        // Livewireのルートを/billing/プレフィックス配下に設定
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/billing/livewire/update', $handle)
                ->middleware(['web']);
        });

        Livewire::setScriptRoute(function ($handle) {
            return Route::get('/billing/livewire/livewire.js', $handle);
        });
    }
}
