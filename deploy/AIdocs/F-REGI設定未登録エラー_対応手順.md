# F-REGI設定未登録エラー対応手順

作成日: 2026-01-19

---

## エラーメッセージ

```
F-REGI設定が未登録です（company_id: 1, environment: prod）
```

---

## 原因

本番環境で`FREGI_ENV`が設定されていない、または`prod`になっているため、DB上で`environment='prod'`の設定を探していますが、実際には`environment='test'`の設定しか存在しない可能性が高いです。

**確認事項:**
- DB上に`company_id=1, environment=test`の設定が存在する
- しかし、`FREGI_ENV`が未設定または`prod`になっている
- そのため、`environment='prod'`で検索して見つからない

---

## 対応方法

### 方法1: .envにFREGI_ENV=testを設定（推奨）

1. **`.env`ファイルを編集**
   - ファイルパス: `/httpdocs/webroot/billing/app/.env`
   - 以下を追記（既存の`.env`に追加）:
     ```env
     FREGI_ENV=test
     ```

2. **Config Cacheを再生成**
   - Pleskでは`php artisan`コマンドが実行できないため、以下の方法で対応：
   
   **方法A: Config Cacheファイルを削除（推奨）**
   - ファイルパス: `/httpdocs/webroot/billing/app/bootstrap/cache/config.php`
   - Pleskファイルマネージャーからこのファイルを削除
   - 次回アクセス時に自動的に再生成されます
   
   **方法B: ブラウザでアクセスして再生成**
   - 管理画面にアクセスすると、config cacheが自動的に再生成されます
   - ただし、既存のcacheファイルがある場合は反映されないため、**方法Aを推奨**

3. **動作確認**
   - ログファイル（`storage/logs/laravel.log`）で以下を確認：
     - `F-REGI設定取得`ログに`target_env: test`と`found: true`が記録されていること
   - エラーが解消されること

### 方法2: DBにenvironment='prod'の設定を追加

管理画面からF-REGI設定を追加し、`environment='prod'`の設定を作成します。

1. 管理画面にログイン
2. F-REGI設定画面にアクセス
3. 環境を「本番環境（prod）」に設定して保存

**注意**: この方法は、実際に本番環境のF-REGIに接続する場合に使用します。

---

## 確認SQL

現在のDB上の設定を確認：

```sql
SELECT id, company_id, environment, shopid, is_active, created_at
FROM fregi_configs
WHERE company_id = 1
ORDER BY environment, created_at DESC;
```

**期待される結果:**
- `environment='test'`の設定が存在すること
- `is_active=1`であること

---

## トラブルシューティング

### 問題: `.env`に`FREGI_ENV=test`を設定したが、まだ`environment: prod`で検索している

**原因**: Config Cacheが古いまま

**対処**:
1. `/httpdocs/webroot/billing/app/bootstrap/cache/config.php`を削除
2. 管理画面にアクセスしてconfig cacheを再生成
3. 再度F-REGI決済を実行してログを確認

### 問題: ログに`F-REGI設定取得`が表示されない

**原因**: 古いコードがデプロイされている可能性

**対処**:
1. `FregiConfigService.php`が最新版であることを確認
2. ファイルの更新日時を確認
3. 必要に応じて再デプロイ

### 問題: DB上に`environment='test'`の設定が存在しない

**対処**:
1. 管理画面からF-REGI設定を追加
2. 環境を「テスト環境（test）」に設定
3. 必要な情報（SHOP ID、接続パスワード等）を入力して保存

---

## ログ確認方法

### 正常時のログ例

```
[2026-01-19 12:34:56] local.INFO: F-REGI設定取得 {"company_id":1,"target_env":"test","found":true,"count":1}
```

### エラー時のログ例

```
[2026-01-19 12:34:56] local.INFO: F-REGI設定取得 {"company_id":1,"target_env":"prod","found":false,"count":0}
[2026-01-19 12:34:56] local.ERROR: F-REGI設定が見つかりません {"company_id":1,"target_env":"prod","payment_id":1,"fregi_env_config":"prod"}
```

**確認ポイント:**
- `target_env`: 検索に使用した環境値（`FREGI_ENV`の値）
- `found`: 設定が見つかったか（`true`/`false`）
- `fregi_env_config`: 現在の`config('fregi.environment')`の値

---

## 参考情報

- [deploy_rules.md](./deploy_rules.md) - セクション10「F-REGI接続先の制御：FREGI_ENV」
- [F-REGI設定_最終成果物.md](./F-REGI設定_最終成果物.md) - F-REGI設定の詳細
