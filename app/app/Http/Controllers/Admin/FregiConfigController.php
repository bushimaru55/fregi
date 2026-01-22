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
     * テスト環境と本番環境の両方の設定を一覧表示
     */
    public function index()
    {
        $configs = $this->configService->getAllConfigs(1);
        
        // テスト環境と本番環境の設定を取得（存在しない場合は空のオブジェクト）
        $testConfig = $configs->where('environment', 'test')->first();
        $prodConfig = $configs->where('environment', 'prod')->first();
        
        return view('admin.fregi-configs.index', compact('configs', 'testConfig', 'prodConfig'));
    }

    /**
     * Show the form for editing the specified resource.
     * 環境パラメータを受け取り、該当環境の設定を編集
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function edit(Request $request)
    {
        // 環境パラメータを取得（デフォルトはtest）
        $environment = $request->input('environment', 'test');
        
        // 該当環境の設定を取得（is_activeに関係なく取得）
        $config = $this->configService->getConfigByEnvironment(1, $environment);
        
        // レコードが存在しない場合は、空の設定オブジェクトを作成（DBには保存しない）
        if (!$config) {
            $config = new FregiConfig([
                'company_id' => 1,
                'environment' => $environment,
                'shopid' => '',
                'notify_url' => url('/api/fregi/notify'),
                'return_url_success' => url('/return/success'),
                'return_url_cancel' => url('/return/cancel'),
                'is_active' => false,
            ]);
        } else {
            // 既存レコードのURLを正規化（/billing/billing/ を /billing/ に修正）
            if ($config->notify_url && strpos($config->notify_url, '/billing/billing/') !== false) {
                $config->notify_url = str_replace('/billing/billing/', '/billing/', $config->notify_url);
            }
            if ($config->return_url_success && strpos($config->return_url_success, '/billing/billing/') !== false) {
                $config->return_url_success = str_replace('/billing/billing/', '/billing/', $config->return_url_success);
            }
            if ($config->return_url_cancel && strpos($config->return_url_cancel, '/billing/billing/') !== false) {
                $config->return_url_cancel = str_replace('/billing/billing/', '/billing/', $config->return_url_cancel);
            }
        }
        
        // すべての設定を取得（環境切り替え用）
        $allConfigs = $this->configService->getAllConfigs(1);
        $testConfig = $allConfigs->where('environment', 'test')->first();
        $prodConfig = $allConfigs->where('environment', 'prod')->first();
        
        // 現在有効な環境を取得
        $activeConfig = $this->configService->getSingleConfig();
        $activeEnvironment = $activeConfig ? $activeConfig->environment : null;
        
        return view('admin.fregi-configs.edit', compact('config', 'testConfig', 'prodConfig', 'activeEnvironment'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        // 環境パラメータを取得（デフォルトはtest）
        $environment = $request->input('environment', 'test');
        
        // 該当環境の設定を取得
        $config = $this->configService->getConfigByEnvironment(1, $environment);
        
        if (!$config) {
            return redirect()->route('admin.fregi-configs.edit', ['environment' => $environment])
                ->with('info', '設定が未登録です。編集画面で設定を登録してください。');
        }
        
        return view('admin.fregi-configs.show', compact('config'));
    }

    /**
     * Update the specified resource in storage.
     * 環境別に設定を保存（初回保存時はcreateConfig()を呼び出す）
     */
    public function update(FregiConfigRequest $request)
    {
        try {
            $data = $request->validated();
            
            // company_idは固定値1を設定
            $data['company_id'] = 1;
            $data['updated_by'] = auth()->id() ?? 'system'; // TODO: 認証実装後に対応

            // 該当環境の設定を取得
            $environment = $data['environment'];
            $config = $this->configService->getConfigByEnvironment(1, $environment);

            if (!$config) {
                // 初回保存時：createConfig()を使用（connect_passwordが必須）
                if (empty($data['connect_password'])) {
                    return back()
                        ->withInput()
                        ->withErrors(['connect_password' => '初回保存時は接続パスワードが必須です。']);
                }
                
                $config = $this->configService->createConfig($data);
                
                return redirect()
                    ->route('admin.fregi-configs.edit', ['environment' => $environment])
                    ->with('success', 'F-REGI設定を登録しました。');
            } else {
                // 更新時：パスワード変更チェック
                if (!($request->has('change_password') && $request->input('change_password'))) {
                    unset($data['connect_password']);
                }

                // URLの正規化（/billing/billing/ を /billing/ に修正）
                if (isset($data['notify_url']) && strpos($data['notify_url'], '/billing/billing/') !== false) {
                    $data['notify_url'] = str_replace('/billing/billing/', '/billing/', $data['notify_url']);
                }
                if (isset($data['return_url_success']) && strpos($data['return_url_success'], '/billing/billing/') !== false) {
                    $data['return_url_success'] = str_replace('/billing/billing/', '/billing/', $data['return_url_success']);
                }
                if (isset($data['return_url_cancel']) && strpos($data['return_url_cancel'], '/billing/billing/') !== false) {
                    $data['return_url_cancel'] = str_replace('/billing/billing/', '/billing/', $data['return_url_cancel']);
                }

                // 環境切り替え時の警告メッセージを準備
                $activeConfig = $this->configService->getSingleConfig();
                $isSwitchingEnvironment = isset($data['is_active']) && $data['is_active'] === true 
                    && $activeConfig 
                    && $activeConfig->environment !== $environment;

                $config = $this->configService->updateConfig($config, $data);

                $message = 'F-REGI設定を更新しました。';
                if ($isSwitchingEnvironment) {
                    $message .= ' 使用環境を' . ($environment === 'prod' ? '本番環境' : 'テスト環境') . 'に切り替えました。';
                }

                return redirect()
                    ->route('admin.fregi-configs.edit', ['environment' => $environment])
                    ->with('success', $message);
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
     * 環境を切り替える（一覧画面から直接切り替え）
     *
     * @param string $environment
     * @return \Illuminate\Http\RedirectResponse
     */
    public function switch(string $environment)
    {
        try {
            // 該当環境の設定を取得（is_activeに関係なく取得）
            $config = $this->configService->getConfigByEnvironment(1, $environment);
            
            if (!$config) {
                return redirect()
                    ->route('admin.fregi-configs.edit', ['environment' => $environment])
                    ->withErrors(['error' => '設定が未登録です。先に設定を登録してください。']);
            }
            
            // 環境を切り替え（is_active=trueに設定）
            $data = ['is_active' => true];
            $this->configService->updateConfig($config, $data);
            
            $envName = $environment === 'prod' ? '本番環境' : 'テスト環境';
            
            // 現在のタブに戻る
            return redirect()
                ->route('admin.fregi-configs.edit', ['environment' => $environment])
                ->with('success', "使用環境を{$envName}に切り替えました。");
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.fregi-configs.edit', ['environment' => $environment])
                ->withErrors(['error' => '環境の切り替えに失敗しました: ' . $e->getMessage()]);
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
