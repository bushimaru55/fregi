<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
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
        // Livewireのルートを/billing/プレフィックス配下に設定
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/billing/livewire/update', $handle)
                ->middleware(['web']);
        });

        Livewire::setScriptRoute(function ($handle) {
            return Route::get('/billing/livewire/livewire.js', $handle);
        });

        // FREGI_SECRET_KEY未設定時の警告ログ（本番事故予防）
        if (empty(config('fregi.secret_key'))) {
            Log::warning('FREGI_SECRET_KEY is not set in environment. F-REGI configuration save will fail.', [
                'message' => 'Please set FREGI_SECRET_KEY in .env file. See README.md or deploy_rules.md for key generation instructions.',
            ]);
        }
    }
}
