# F-REGI環境切り替え機能 本番環境デプロイ手順

作成日: 2026-01-22

## 概要

F-REGI設定画面からテスト環境/本番環境の設定を登録・編集し、使用環境を切り替えられる機能を本番環境に適用します。

---

## デプロイ前の確認事項

### 1. 修正ファイルの確認

以下のファイルが `deploy/webroot_billing/` ディレクトリに正しく反映されていることを確認してください：

#### コントローラー
- [x] `app/app/Http/Controllers/Admin/FregiConfigController.php`
  - `index()` メソッドが実装されている
  - `edit()` メソッドが環境パラメータを受け取る
  - `update()` メソッドが環境別に保存する
  - `switch()` メソッドが実装されている

#### サービス
- [x] `app/app/Services/FregiConfigService.php`
  - `getAllConfigs()` メソッドが実装されている
  - `getConfigByEnvironment()` メソッドが `is_active` に関係なく取得する
  - `getActiveConfigByEnvironment()` メソッドが実装されている
  - `createConfig()` と `updateConfig()` で環境切り替え処理が実装されている

#### リクエスト
- [x] `app/app/Http/Requests/FregiConfigRequest.php`
  - バリデーションが環境別に判定される

#### ビュー
- [x] `app/resources/views/admin/fregi-configs/edit.blade.php`
  - 環境切り替えスイッチが実装されている
  - `switchEnvironment()` JavaScript関数が実装されている
  - URL正規化処理が実装されている

- [x] `app/resources/views/admin/fregi-configs/index.blade.php`
  - 環境切り替えボタンが実装されている

#### ルート
- [x] `app/routes/web.php`
  - `fregi-configs.switch` ルートが追加されている

#### その他
- [x] `app/app/Http/Controllers/ContractController.php`
  - 構文エラーが修正されている（配列内の変数代入を修正）

---

## デプロイ手順

### ステップ1: ファイルのアップロード

#### 1.1 FTP接続

FTPクライアント（FileZilla、Cyberduck等）を使用して本番サーバーに接続します。

#### 1.2 アップロード先の確認

**アップロード先**: `/var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/`

**重要**: `deploy/webroot_billing/` の中身を**そのまま**アップロードします。

#### 1.3 アップロードするファイル

以下のファイルをアップロードします：

```
deploy/webroot_billing/app/app/Http/Controllers/Admin/FregiConfigController.php
deploy/webroot_billing/app/app/Services/FregiConfigService.php
deploy/webroot_billing/app/app/Http/Requests/FregiConfigRequest.php
deploy/webroot_billing/app/app/Http/Controllers/ContractController.php
deploy/webroot_billing/app/resources/views/admin/fregi-configs/edit.blade.php
deploy/webroot_billing/app/resources/views/admin/fregi-configs/index.blade.php
deploy/webroot_billing/app/routes/web.php
```

**注意**: 
- 既存のファイルを上書きします
- `.env` ファイルはアップロードしないでください（既存の設定を保持）

---

### ステップ2: キャッシュのクリア

#### 2.1 ビューキャッシュのクリア

PleskのファイルマネージャーまたはFTPで以下のファイルを削除：

```
/var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/app/bootstrap/cache/config.php
/var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/app/storage/framework/views/*
```

**注意**: `storage/framework/views/` ディレクトリ内のファイルをすべて削除してください（ディレクトリ自体は削除しない）

---

### ステップ3: 既存データの修正（オプション）

既存のF-REGI設定レコードに `/billing/billing/` という重複したURLが保存されている場合、以下のSQLを実行して修正してください：

```sql
-- F-REGI設定のURL修正（/billing/billing/ を /billing/ に修正）
UPDATE `fregi_configs`
SET 
    `notify_url` = REPLACE(`notify_url`, '/billing/billing/', '/billing/'),
    `return_url_success` = REPLACE(`return_url_success`, '/billing/billing/', '/billing/'),
    `return_url_cancel` = REPLACE(`return_url_cancel`, '/billing/billing/', '/billing/'),
    `updated_at` = NOW()
WHERE 
    `company_id` = 1
    AND (
        `notify_url` LIKE '%/billing/billing/%'
        OR `return_url_success` LIKE '%/billing/billing/%'
        OR `return_url_cancel` LIKE '%/billing/billing/%'
    );
```

**確認SQL**:
```sql
SELECT 
    `id`,
    `environment`,
    `notify_url`,
    `return_url_success`,
    `return_url_cancel`,
    `updated_at`
FROM `fregi_configs`
WHERE `company_id` = 1
ORDER BY `environment`, `id`;
```

---

### ステップ4: 動作確認

#### 4.1 管理画面へのアクセス

1. 以下のURLにアクセス：
   ```
   https://dschatbot.ai/webroot/billing/admin/fregi-configs
   ```

2. ログイン情報：
   - メールアドレス: `kanri@dschatbot.ai`
   - パスワード: `cs20051101`

#### 4.2 機能確認

1. **一覧画面の確認**
   - テスト環境と本番環境の設定が表示される
   - 現在有効な環境に「有効」バッジが表示される
   - 無効な環境に「この環境を有効にする」ボタンが表示される

2. **環境切り替えスイッチの確認**
   - 編集画面の上部に環境切り替えスイッチが表示される
   - スイッチを切り替えると確認ダイアログが表示される
   - 切り替え後、正しい環境が有効になる

3. **設定の登録・編集**
   - テスト環境の設定を登録・編集できる
   - 本番環境の設定を登録・編集できる
   - 初回保存時は接続パスワードが必須
   - URLのデフォルト値が正しい（`/billing/billing/` ではない）

4. **環境切り替え**
   - 一覧画面から「この環境を有効にする」ボタンで切り替え可能
   - 編集画面のスイッチから切り替え可能
   - 切り替え後、他の環境の設定は自動的に無効になる

---

## トラブルシューティング

### 問題1: 500 Internal Server Error

**原因**: ビューキャッシュが古い、または構文エラー

**対処方法**:
1. `bootstrap/cache/config.php` を削除
2. `storage/framework/views/` 内のファイルをすべて削除
3. ブラウザのキャッシュをクリア

### 問題2: 環境切り替えが動作しない

**原因**: ルートが正しく登録されていない

**対処方法**:
1. `routes/web.php` に `fregi-configs.switch` ルートが追加されているか確認
2. キャッシュをクリア

### 問題3: 本番環境の設定が保存できない

**原因**: バリデーションエラー

**対処方法**:
1. 初回保存時は接続パスワードを必ず入力
2. すべての必須項目を入力
3. URLが正しい形式であることを確認

### 問題4: URLに `/billing/billing/` が表示される

**原因**: 既存のデータベースレコードに古いURLが保存されている

**対処方法**:
1. ステップ3のSQLを実行して既存レコードを修正
2. または、画面から手動でURLを修正して保存

---

## デプロイ後の確認項目

- [ ] 一覧画面が正常に表示される
- [ ] 環境切り替えスイッチが表示される
- [ ] テスト環境の設定を登録・編集できる
- [ ] 本番環境の設定を登録・編集できる
- [ ] 環境切り替えが正常に動作する
- [ ] URLのデフォルト値が正しい
- [ ] 既存の決済処理が正常に動作する

---

## 参考資料

- `AIdocs/FTPデプロイ手順書.md`
- `AIdocs/本番環境デプロイ手順書_最新版.md`
- `AIdocs/F-REGI設定URL修正SQL.sql`
