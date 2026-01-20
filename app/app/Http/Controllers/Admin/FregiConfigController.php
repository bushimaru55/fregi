<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FregiConfigRequest;
use App\Models\FregiConfig;
use App\Services\FregiConfigService;
use Illuminate\Http\Request;

class FregiConfigController extends Controller
{
    private FregiConfigService $configService;

    public function __construct(FregiConfigService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * Display a listing of the resource.
     * 編集のみ運用のため、編集画面へリダイレクト
     */
    public function index()
    {
        return redirect()->route('admin.fregi-configs.edit');
    }

    /**
     * Show the form for editing the specified resource.
     * レコードが存在しない場合は空の設定オブジェクトを表示（DBには作成しない）
     */
    public function edit()
    {
        $config = $this->configService->getSingleConfig();
        
        // レコードが存在しない場合は、空の設定オブジェクトを作成（DBには保存しない）
        if (!$config) {
            $config = new FregiConfig([
                'company_id' => 1,
                'environment' => 'test',
                'shopid' => '',
                'notify_url' => url('/billing/api/fregi/notify'),
                'return_url_success' => url('/billing/return/success'),
                'return_url_cancel' => url('/billing/return/cancel'),
                'is_active' => true,
            ]);
        }
        
        return view('admin.fregi-configs.edit', compact('config'));
    }

    /**
     * Display the specified resource.
     */
    public function show()
    {
        $config = $this->configService->getSingleConfig();
        
        if (!$config) {
            return redirect()->route('admin.fregi-configs.edit')
                ->with('info', '設定が未登録です。編集画面で設定を登録してください。');
        }
        
        return view('admin.fregi-configs.show', compact('config'));
    }

    /**
     * Update the specified resource in storage.
     * 初回保存時（レコードが存在しない場合）はcreateConfig()を呼び出す
     */
    public function update(FregiConfigRequest $request)
    {
        try {
            $config = $this->configService->getSingleConfig();
            $data = $request->validated();
            
            // company_idは固定値1を設定
            $data['company_id'] = 1;
            $data['updated_by'] = auth()->id() ?? 'system'; // TODO: 認証実装後に対応

            if (!$config) {
                // 初回保存時：createConfig()を使用（connect_passwordが必須）
                if (empty($data['connect_password'])) {
                    return back()
                        ->withInput()
                        ->withErrors(['connect_password' => '初回保存時は接続パスワードが必須です。']);
                }
                
                $config = $this->configService->createConfig($data);
                
                return redirect()
                    ->route('admin.fregi-configs.edit')
                    ->with('success', 'F-REGI設定を登録しました。');
            } else {
                // 更新時：パスワード変更チェック
                if (!($request->has('change_password') && $request->input('change_password'))) {
                    unset($data['connect_password']);
                }

                $config = $this->configService->updateConfig($config, $data);

                return redirect()
                    ->route('admin.fregi-configs.edit')
                    ->with('success', 'F-REGI設定を更新しました。');
            }
        } catch (\Exception $e) {
            // FREGI_SECRET_KEY未設定エラーの特別処理
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'FREGI_SECRET_KEY') !== false) {
                return back()
                    ->withInput()
                    ->withErrors(['error' => 'F-REGI暗号化キー（FREGI_SECRET_KEY）が未設定です。.env に設定してから再度保存してください。']);
            }
            
            return back()
                ->withInput()
                ->withErrors(['error' => '保存に失敗しました: ' . $errorMessage]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FregiConfig $fregiConfig)
    {
        // TODO: 削除機能の実装（必要に応じて）
        return back()->withErrors(['error' => '削除機能は未実装です。']);
    }
}
