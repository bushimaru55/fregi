<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;

class SiteSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     * サニタイズ済みHTMLを表示する
     */
    public function index()
    {
        // サニタイズ済みHTMLを取得（RichEditorで保存されたHTML）
        $termsOfService = SiteSetting::getValue('terms_of_service', '');
        // トップページのURLを取得
        $topPageUrl = SiteSetting::getTextValue('top_page_url', '');
        // 製品ページのURLを取得
        $productPageUrl = SiteSetting::getTextValue('product_page_url', '');
        
        return view('admin.site-settings.index', compact('termsOfService', 'topPageUrl', 'productPageUrl'));
    }

    /**
     * Show the form for editing the specified resource.
     * Livewireコンポーネントがデータ取得を担当するためシンプル化
     */
    public function edit()
    {
        // Livewireコンポーネントが直接DBからデータを取得するため
        // ここでは特にデータを渡さない
        return view('admin.site-settings.edit');
    }

    /**
     * Update the specified resource in storage.
     * 従来のフォーム経由での更新用（Livewire以外からの更新に対応）
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'terms_of_service' => 'required|string',
        ], [
            'terms_of_service.required' => '利用規約の内容を入力してください。',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $html = $request->input('terms_of_service');
            
            // HTMLをサニタイズ（rich_htmlプロファイル使用）
            $cleanHtml = Purifier::clean($html, 'rich_html');
            
            // プレーンテキスト版を生成
            $plainText = Str::squish(strip_tags($cleanHtml));

            // DBに保存
            SiteSetting::updateOrCreate(
                ['key' => 'terms_of_service'],
                [
                    'value' => $cleanHtml,
                    'value_text' => $plainText,
                    'description' => '利用規約の本文',
                ]
            );

            return redirect()
                ->route('admin.site-settings.index')
                ->with('success', '利用規約を更新しました。');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => '更新に失敗しました: ' . $e->getMessage()]);
        }
    }

    /**
     * トップページのURL編集画面を表示
     */
    public function editTopPageUrl()
    {
        $topPageUrl = SiteSetting::getTextValue('top_page_url', '');
        
        return view('admin.site-settings.edit-top-page-url', compact('topPageUrl'));
    }

    /**
     * トップページのURLを更新
     */
    public function updateTopPageUrl(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'top_page_url' => ['required', 'url', 'max:255'],
        ], [
            'top_page_url.required' => 'トップページのURLを入力してください。',
            'top_page_url.url' => '有効なURL形式で入力してください。',
            'top_page_url.max' => 'URLは255文字以内で入力してください。',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $url = $request->input('top_page_url');
            
            // DBに保存（テキスト形式）
            SiteSetting::setTextValue(
                'top_page_url',
                $url,
                '決済完了画面の「トップへ戻る」ボタンのリンク先URL'
            );

            return redirect()
                ->route('admin.site-settings.index')
                ->with('success', 'トップページのURLを更新しました。');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => '更新に失敗しました: ' . $e->getMessage()]);
        }
    }

    /**
     * 製品ページのURL編集画面を表示
     */
    public function editProductPageUrl()
    {
        $productPageUrl = SiteSetting::getTextValue('product_page_url', '');
        
        return view('admin.site-settings.edit-product-page-url', compact('productPageUrl'));
    }

    /**
     * 製品ページのURLを更新
     */
    public function updateProductPageUrl(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_page_url' => ['required', 'url', 'max:255'],
        ], [
            'product_page_url.required' => '製品ページのURLを入力してください。',
            'product_page_url.url' => '有効なURL形式で入力してください。',
            'product_page_url.max' => 'URLは255文字以内で入力してください。',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $url = $request->input('product_page_url');
            
            // DBに保存（テキスト形式）
            SiteSetting::setTextValue(
                'product_page_url',
                $url,
                '公開ページヘッダーの「製品ページへ戻る」ボタンのリンク先URL'
            );

            return redirect()
                ->route('admin.site-settings.index')
                ->with('success', '製品ページのURLを更新しました。');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => '更新に失敗しました: ' . $e->getMessage()]);
        }
    }
}
