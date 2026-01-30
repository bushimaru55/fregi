<?php

namespace App\Services;

use App\Models\FregiConfig;
use App\Models\FregiConfigVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FregiConfigService
{
    /**
     * 単一設定を取得（編集のみ運用）
     * レコードが存在しない場合はnullを返す（DBには作成しない）
     * 優先順位: is_active=true の設定 > 最初の1件
     *
     * @return FregiConfig|null
     */
    public function getSingleConfig(): ?FregiConfig
    {
        // まず、is_active=true の設定を取得（優先）
        $activeConfig = FregiConfig::where('company_id', 1)
            ->where('is_active', true)
            ->orderBy('environment') // test を優先
            ->first();
        
        if ($activeConfig) {
            return $activeConfig;
        }
        
        // is_active=true の設定がない場合は、最初の1件を取得
        return FregiConfig::where('company_id', 1)->first();
    }

    /**
     * すべての設定を取得（テスト環境と本番環境の両方）
     *
     * @param int $companyId 会社ID
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllConfigs(int $companyId = 1): \Illuminate\Database\Eloquent\Collection
    {
        return FregiConfig::where('company_id', $companyId)
            ->orderBy('environment')
            ->orderBy('is_active', 'desc')
            ->get();
    }

    /**
     * 環境別の設定を取得（編集用：is_activeに関係なく取得）
     *
     * @param int $companyId 会社ID
     * @param string $environment 環境（test/prod）
     * @return FregiConfig|null
     */
    public function getConfigByEnvironment(int $companyId, string $environment): ?FregiConfig
    {
        return FregiConfig::where('company_id', $companyId)
            ->where('environment', $environment)
            ->first();
    }

    /**
     * 環境別のアクティブな設定を取得（決済処理用）
     *
     * @param int $companyId 会社ID
     * @param string $environment 環境（test/prod）
     * @return FregiConfig|null
     */
    public function getActiveConfigByEnvironment(int $companyId, string $environment): ?FregiConfig
    {
        return FregiConfig::where('company_id', $companyId)
            ->where('environment', $environment)
            ->where('is_active', true)
            ->first();
    }

    /**
     * アクティブな設定を取得
     *
     * @param int $companyId 会社ID
     * @param string $environment 環境（test/prod）
     * @return FregiConfig
     * @throws \Exception
     */
    public function getActiveConfig(int $companyId, string $environment): FregiConfig
    {
        $configs = FregiConfig::where('company_id', $companyId)
            ->where('environment', $environment)
            ->where('is_active', true)
            ->get();

        $found = !$configs->isEmpty();
        
        Log::info('F-REGI設定取得', [
            'company_id' => $companyId,
            'target_env' => $environment,
            'found' => $found,
            'count' => $configs->count(),
        ]);

        if ($configs->isEmpty()) {
            // より詳細なエラーメッセージを生成
            $fregiEnv = config('fregi.environment', 'test');
            $errorMessage = "F-REGI設定が未登録です（company_id: {$companyId}, environment: {$environment}）";
            $errorMessage .= "\n現在のFREGI_ENV設定: {$fregiEnv}";
            $errorMessage .= "\n\n対処方法:";
            $errorMessage .= "\n1. DBに environment='{$environment}' の設定が存在することを確認";
            $errorMessage .= "\n2. または、.env に FREGI_ENV={$environment} を設定してConfig Cacheを再生成";
            $errorMessage .= "\n   （Pleskの場合: bootstrap/cache/config.php を削除）";
            
            throw new \Exception($errorMessage);
        }

        if ($configs->count() > 1) {
            // 重複検出：監視ログに記録
            Log::warning('Multiple active F-REGI configs detected', [
                'company_id' => $companyId,
                'environment' => $environment,
                'count' => $configs->count(),
            ]);
            throw new \Exception("アクティブなF-REGI設定が複数存在します（company_id: {$companyId}, environment: {$environment}）");
        }

        return $configs->first();
    }

    /**
     * 設定を作成
     * 初回保存時のみ使用。connect_passwordは必須で、暗号化してconnect_password_encに保存
     *
     * @param array $data
     * @return FregiConfig
     * @throws \Exception connect_passwordが空の場合
     */
    public function createConfig(array $data): FregiConfig
    {
        return DB::transaction(function () use ($data) {
            // connect_passwordが必須であることを確認
            if (empty($data['connect_password'])) {
                throw new \Exception('初回保存時は接続パスワードが必須です。');
            }

            // connect_passwordを暗号化してconnect_password_encに設定
            $password = $data['connect_password'];
            unset($data['connect_password']);
            
            // is_activeがtrueの場合、すべての環境の他の設定をfalseにする（環境切り替え機能）
            if (isset($data['is_active']) && $data['is_active'] === true) {
                FregiConfig::where('company_id', $data['company_id'] ?? 1)
                    ->update(['is_active' => false]);
            }
            
            // 新しい設定を作成
            $config = new FregiConfig($data);
            $config->connect_password = $password; // アクセサで自動暗号化
            $config->save();

            // 変更履歴を保存
            $this->saveVersion($config, 1, '初期登録');

            return $config;
        });
    }

    /**
     * 設定を更新
     *
     * @param FregiConfig $config
     * @param array $data
     * @return FregiConfig
     */
    public function updateConfig(FregiConfig $config, array $data): FregiConfig
    {
        return DB::transaction(function () use ($config, $data) {
            // is_activeの変更がある場合、一意性を保証
            if (isset($data['is_active']) && $data['is_active'] === true) {
                // 同じcompany_id, environmentの他の設定をfalseにする
                FregiConfig::where('company_id', $config->company_id)
                    ->where('environment', $config->environment)
                    ->where('id', '!=', $config->id)
                    ->update(['is_active' => false]);
                
                // 環境切り替え: すべての環境の他の設定をfalseにする（使用環境を切り替えるため）
                // これにより、is_active=true の設定が1件のみになる（環境切り替え機能）
                FregiConfig::where('company_id', $config->company_id)
                    ->where('id', '!=', $config->id)
                    ->update(['is_active' => false]);
            }

            // パスワード処理：connect_passwordが空の場合は既存値を保持
            $passwordChanged = false;
            if (isset($data['connect_password']) && !empty($data['connect_password'])) {
                // 入力がある場合は暗号化してconnect_password_encに設定
                // connect_passwordアクセサを使用して暗号化
                $config->connect_password = $data['connect_password'];
                $passwordChanged = true;
            }
            // connect_passwordは$dataから除外（アクセサで処理済み）
            unset($data['connect_password']);

            // 既存のバージョン番号を取得
            $currentVersion = $config->versions()->max('version_no') ?? 0;
            $newVersion = $currentVersion + 1;

            // 更新前の状態をスナップショットとして保存（変更後）
            $oldData = $config->toArray();
            
            // 設定を更新
            $config->update($data);
            
            // パスワードが変更された場合は、アクセサで設定されたconnect_password_encを保存
            if ($passwordChanged) {
                $config->save(); // connect_password_encを保存
            }
            
            $config->refresh();

            // 変更履歴を保存
            $this->saveVersion($config, $newVersion, $data['change_reason'] ?? '更新');

            return $config;
        });
    }

    /**
     * 変更履歴を保存
     *
     * @param FregiConfig $config
     * @param int $versionNo
     * @param string|null $changeReason
     * @return void
     */
    private function saveVersion(FregiConfig $config, int $versionNo, ?string $changeReason = null): void
    {
        $snapshot = $config->toArray();
        // 秘密情報はマスク（ログ安全のため）
        if (isset($snapshot['connect_password_enc'])) {
            $snapshot['connect_password_enc'] = '***MASKED***';
        }

        FregiConfigVersion::create([
            'config_id' => $config->id,
            'version_no' => $versionNo,
            'snapshot_json' => $snapshot,
            'changed_at' => now(),
            'changed_by' => $config->updated_by ?? null,
            'change_reason' => $changeReason,
        ]);
    }
}

