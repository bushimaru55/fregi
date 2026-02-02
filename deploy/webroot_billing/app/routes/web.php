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
    Route::get('/complete/{contract}', [\App\Http\Controllers\ContractController::class, 'complete'])->name('complete')->middleware('signed');
    
    // オプション商品取得API（選択されたベース商品に紐づくオプション商品を取得）
    Route::get('/api/option-products/{contractPlanId}', [\App\Http\Controllers\ContractController::class, 'getOptionProducts'])->name('api.option-products');
});

// 管理画面（認証必須）
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    // ダッシュボード
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    // 契約ステータスマスター
    Route::resource('contract-statuses', \App\Http\Controllers\Admin\ContractStatusController::class)->except('show')->names('contract-statuses');

    // 契約管理（申し込み一覧）
    Route::prefix('contracts')->name('contracts.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ContractController::class, 'index'])->name('index');
        Route::get('/export', [\App\Http\Controllers\Admin\ContractController::class, 'exportCsv'])->name('export');
        Route::get('/{contract}/edit', [\App\Http\Controllers\Admin\ContractController::class, 'edit'])->name('edit');
        Route::put('/{contract}', [\App\Http\Controllers\Admin\ContractController::class, 'update'])->name('update');
        Route::delete('/{contract}', [\App\Http\Controllers\Admin\ContractController::class, 'destroy'])->name('destroy');
        Route::get('/{contract}', [\App\Http\Controllers\Admin\ContractController::class, 'show'])->name('show');
    });

    // 契約プラン管理
    Route::resource('contract-plans', \App\Http\Controllers\Admin\ContractPlanController::class);
    Route::post('contract-plans/update-order', [\App\Http\Controllers\Admin\ContractPlanController::class, 'updateOrder'])->name('contract-plans.update-order');

    // 契約プランマスター管理
    Route::resource('contract-plan-masters', \App\Http\Controllers\Admin\ContractPlanMasterController::class);

    // 商品管理
    Route::resource('products', \App\Http\Controllers\Admin\ProductController::class);

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
        // トップページのURL設定
        Route::get('/top-page-url/edit', [\App\Http\Controllers\Admin\SiteSettingController::class, 'editTopPageUrl'])->name('top-page-url.edit');
        Route::put('/top-page-url', [\App\Http\Controllers\Admin\SiteSettingController::class, 'updateTopPageUrl'])->name('top-page-url.update');
        // 製品ページのURL設定
        Route::get('/product-page-url/edit', [\App\Http\Controllers\Admin\SiteSettingController::class, 'editProductPageUrl'])->name('product-page-url.edit');
        Route::put('/product-page-url', [\App\Http\Controllers\Admin\SiteSettingController::class, 'updateProductPageUrl'])->name('product-page-url.update');
        // 返信メール設定
        Route::get('/reply-mail/edit', [\App\Http\Controllers\Admin\SiteSettingController::class, 'editReplyMail'])->name('reply-mail.edit');
        Route::put('/reply-mail', [\App\Http\Controllers\Admin\SiteSettingController::class, 'updateReplyMail'])->name('reply-mail.update');
    });

    // 管理者管理（通知メール設定は {user} と衝突するため resource より前に定義）
    Route::get('users/notification-email/edit', [\App\Http\Controllers\Admin\UserController::class, 'editNotificationEmail'])->name('users.edit-notification-email');
    Route::put('users/notification-email', [\App\Http\Controllers\Admin\UserController::class, 'updateNotificationEmail'])->name('users.update-notification-email');
    Route::post('users/notification-email/send-test', [\App\Http\Controllers\Admin\UserController::class, 'sendTestNotificationEmail'])->name('users.send-test-notification-email');
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
});

// 認証関連（Breeze）
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
