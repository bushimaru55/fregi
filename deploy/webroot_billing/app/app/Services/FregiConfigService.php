<?php

namespace App\Services;

use App\Models\FregiConfig;
use App\Models\FregiConfigVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FregiConfigService
{
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

        if ($configs->isEmpty()) {
            throw new \Exception("F-REGI設定が未登録です（company_id: {$companyId}, environment: {$environment}）");
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
     *
     * @param array $data
     * @return FregiConfig
     */
    public function createConfig(array $data): FregiConfig
    {
        return DB::transaction(function () use ($data) {
            // 新しい設定を作成
            $config = FregiConfig::create($data);

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
            }

            // パスワードが変更されない場合は、connect_password_encを保持
            if (isset($data['connect_password']) && empty($data['connect_password'])) {
                unset($data['connect_password']);
            }

            // 既存のバージョン番号を取得
            $currentVersion = $config->versions()->max('version_no') ?? 0;
            $newVersion = $currentVersion + 1;

            // 更新前の状態をスナップショットとして保存（変更後）
            $oldData = $config->toArray();
            
            // 設定を更新
            $config->update($data);
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

