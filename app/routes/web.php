<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// トップページは新規申込フォームを表示
Route::get('/', [\App\Http\Controllers\ContractController::class, 'create'])->name('home');

// 旧トップページ（必要に応じて）
Route::get('/about', function () {
    return view('welcome');
})->name('about');

// 申込フォーム（公開）
Route::prefix('contract')->name('contract.')->group(function () {
    Route::get('/create', [\App\Http\Controllers\ContractController::class, 'create'])->name('create');
    Route::get('/confirm', [\App\Http\Controllers\ContractController::class, 'confirmGet'])->name('confirm.get');
    Route::post('/confirm', [\App\Http\Controllers\ContractController::class, 'confirm'])->name('confirm');
    Route::post('/store', [\App\Http\Controllers\ContractController::class, 'store'])->name('store');
    Route::get('/complete', [\App\Http\Controllers\ContractController::class, 'complete'])->name('complete');
});

// 管理画面（認証必須）
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    // ダッシュボード
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    // 契約管理
    Route::prefix('contracts')->name('contracts.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ContractController::class, 'index'])->name('index');
        Route::get('/{contract}', [\App\Http\Controllers\Admin\ContractController::class, 'show'])->name('show');
    });

    // F-REGI設定
    Route::prefix('fregi-configs')->name('fregi-configs.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\FregiConfigController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\FregiConfigController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\FregiConfigController::class, 'store'])->name('store');
        Route::get('/{fregiConfig}', [\App\Http\Controllers\Admin\FregiConfigController::class, 'show'])->name('show');
        Route::get('/{fregiConfig}/edit', [\App\Http\Controllers\Admin\FregiConfigController::class, 'edit'])->name('edit');
        Route::put('/{fregiConfig}', [\App\Http\Controllers\Admin\FregiConfigController::class, 'update'])->name('update');
        Route::delete('/{fregiConfig}', [\App\Http\Controllers\Admin\FregiConfigController::class, 'destroy'])->name('destroy');
    });

    // 契約プラン管理
    Route::resource('contract-plans', \App\Http\Controllers\Admin\ContractPlanController::class);
    Route::post('contract-plans/update-order', [\App\Http\Controllers\Admin\ContractPlanController::class, 'updateOrder'])->name('contract-plans.update-order');

    // 契約プランマスター管理
    Route::resource('contract-plan-masters', \App\Http\Controllers\Admin\ContractPlanMasterController::class);

    // 新規申込フォーム管理
    Route::prefix('contract-forms')->name('contract-forms.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ContractFormController::class, 'index'])->name('index');
        Route::post('/generate', [\App\Http\Controllers\Admin\ContractFormController::class, 'generate'])->name('generate');
        Route::delete('/{contractFormUrl}', [\App\Http\Controllers\Admin\ContractFormController::class, 'destroy'])->name('destroy');
    });

    // サイト管理
    Route::prefix('site-settings')->name('site-settings.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SiteSettingController::class, 'index'])->name('index');
        Route::get('/edit', [\App\Http\Controllers\Admin\SiteSettingController::class, 'edit'])->name('edit');
        Route::put('/', [\App\Http\Controllers\Admin\SiteSettingController::class, 'update'])->name('update');
    });
});

// 決済フロー
Route::prefix('payment')->name('payment.')->group(function () {
    Route::get('/{payment}/initiate', [\App\Http\Controllers\PaymentController::class, 'initiate'])->name('initiate');
});

// 戻りURL（F-REGIから戻ってくるURL、STATUSパラメータで処理を分岐）
Route::get('/return', [\App\Http\Controllers\ReturnController::class, 'handle'])->name('return.handle');
// 後方互換性のため残す（必要に応じて削除可能）
Route::prefix('return')->name('return.')->group(function () {
    Route::get('/success', [\App\Http\Controllers\ReturnController::class, 'success'])->name('success');
    Route::get('/cancel', [\App\Http\Controllers\ReturnController::class, 'cancel'])->name('cancel');
    Route::get('/failure', [\App\Http\Controllers\ReturnController::class, 'failure'])->name('failure');
});

// 認証関連（Breeze）
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
