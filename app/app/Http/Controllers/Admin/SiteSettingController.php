<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SiteSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $termsOfService = SiteSetting::getValue('terms_of_service', '');
        
        // プレーンテキストとして扱う（HTMLタグの有無に関わらず）
        // 表示はCSSのwhite-space: pre-wrapで処理する
        $termsOfService = e($termsOfService);
        
        return view('admin.site-settings.index', compact('termsOfService'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        $termsOfService = SiteSetting::getValue('terms_of_service', '');
        
        // HTMLタグを削除してプレーンテキストに変換
        if ($termsOfService) {
            // <br>タグと</p>タグを改行に変換
            $termsOfService = preg_replace('/<br\s*\/?>/i', "\n", $termsOfService);
            $termsOfService = preg_replace('/<\/p>/i', "\n", $termsOfService);
            // その他のHTMLタグを削除
            $termsOfService = strip_tags($termsOfService);
            // HTMLエンティティをデコード
            $termsOfService = html_entity_decode($termsOfService, ENT_QUOTES, 'UTF-8');
            // 連続する改行を整理（3つ以上の連続する改行を2つに）
            $termsOfService = preg_replace('/\n{3,}/', "\n\n", $termsOfService);
            // 先頭と末尾の改行を削除
            $termsOfService = trim($termsOfService);
        }
        
        return view('admin.site-settings.edit', compact('termsOfService'));
    }

    /**
     * Update the specified resource in storage.
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
            SiteSetting::setValue(
                'terms_of_service',
                $request->input('terms_of_service'),
                '利用規約の本文'
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
}
