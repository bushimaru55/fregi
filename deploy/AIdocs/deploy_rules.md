# deploy_rules.md
作成日: 2026-01-16
対象: Laravel 10 + Filament / サブディレクトリ運用 / Plesk（SSH/CLI不可）

---

## 0. このプロジェクトの最重要前提（絶対に忘れない）
- 本番サーバは **Plesk** 運用
- **SSH/ターミナル不可、php artisan不可（PHP CLI不可）**
- よって本番DBは **migrateではなく、スキーマSQLをインポート**して作る
- アプリはサブディレクトリ運用:
  - 公開URL: `https://dschatbot.ai/webroot/billing`
  - 配置先: `/httpdocs/webroot/billing`
- デプロイは FTPアップロード（Pleskファイルマネージャで確認）

---

## 1. ディレクトリ構成（ローカル）
- `app/` : Laravelアプリ本体（開発対象）
- `docker-compose.yml` : ローカル開発用
- `deploy/webroot_billing/` : **本番投入用の成果物**
  - `index.php`, `.htaccess`, `build/`, `css/`, `js/`, `robots.txt`, `favicon.ico`
  - `app/`（Laravel本体、vendor含む、.env本番用）
  - `build/manifest.json` が 200で見えること

---

## 2. デプロイ手順（標準）
### 2.1 ローカルで開発・動作確認
- Dockerで `migrate`/`seed` を実行してUIを完成させる
- ルーティング・画面・Filament管理画面が動くこと
- F-REGI連携の疎通（notify/return）が期待通りに動くこと

### 2.2 本番投入用成果物を作る（deploy/）
- `deploy/webroot_billing/` に **本番で必要なものだけ**を複製
- `.env` は本番用にする（APP_ENV=production, APP_DEBUG=false, DB_* は本番）
- `.htaccess` の RewriteBase は `/webroot/billing/` に合わせる

### 2.3 FTPアップロード
- `/httpdocs/webroot/billing` に成果物をアップロード
- まず `https://dschatbot.ai/webroot/billing/build/manifest.json` が 200になることを確認

---

## 3. 本番DB構築（migrate不可のためSQLインポート）
### 3.1 ローカルDBからスキーマSQLを作成（データなし）
- 例（Dockerのdbからdump）:
  - `mysqldump --no-data --routines --triggers ... billing > billing_schema.sql`
- **データは入れない**（--no-data）

### 3.2 Pleskでbilling_schema.sqlをインポート
- Plesk > データベース > `billing_prod`
- 「ダンプをインポート」
- 可能なら「データベースを再作成」にチェック（初回のみ）
- インポート後、phpMyAdminでテーブルが増えることを確認

---

## 4. 初期データ（管理者・マスター）
- 本番は `seed` が打てない前提
- 必要な初期データは以下いずれかで投入:
  1) 事前に「初期データSQL」を用意してphpMyAdminで流す
  2) Filament管理画面から登録（※ログインできる管理者が必要）

---

## 5. ルーティング確認（F-REGI）
- 通知URL（POST想定）:
  - `https://dschatbot.ai/webroot/billing/api/fregi/notify`
  - ブラウザでGETすると 405 になってもOK（ルート存在のサイン）
- 戻りURL:
  - `https://dschatbot.ai/webroot/billing/return/success`
  - `https://dschatbot.ai/webroot/billing/return/cancel`

---

## 6. キャッシュ・トラブル時の基本
- Pleskで CLI が無いので artisan clear ができない
- 変更反映が怪しい時は、必要に応じて以下を削除して再生成させる:
  - `app/bootstrap/cache/*.php`（存在する場合）
  - `app/storage/framework/cache/*`（権限注意）
  - `app/storage/framework/views/*`

---

## 7. ロールバック方針
- DB: インポート前にバックアップ（Pleskのエクスポート）
- ファイル: ディレクトリ退避 or 別名にリネームして戻せる状態を作る

---

## 8. 実運用での絶対ルール
- 本番で「migrate前提」の手順は書かない（できない）
- 本番で必要な初期データは、必ず "投入方法" をセットで用意する
- 変更時は「どのファイルがdeploy成果物に入るか」を必ず確認する

---

## 9. 必須環境変数：FREGI_SECRET_KEY

### 概要
F-REGI設定の接続パスワードを暗号化するために使用する秘密鍵です。**ローカル・本番ともに必須**です。

### キーの生成方法

以下のいずれかのコマンドで32バイトのBase64エンコードされたキーを生成できます：

**macOS/Linux:**
```bash
# opensslを使用（推奨）
openssl rand -base64 32

# Pythonを使用
python3 - <<'PY'
import os
import base64
print(base64.b64encode(os.urandom(32)).decode('utf-8'))
PY
```

**生成例:**
```
Y1AExhmfYZYSwOJ24QRTEV1YoD87NxzwBNLmMdZsUSc=
```

### .envへの設定

生成したキーを`.env`ファイルに追加：

```env
FREGI_SECRET_KEY=Y1AExhmfYZYSwOJ24QRTEV1YoD87NxzwBNLmMdZsUSc=
```

### 本番環境での設定

本番環境（Plesk）では、`deploy/webroot_billing/app/.env`に設定してください。

**重要：** 本番環境の`FREGI_SECRET_KEY`は**パスワードと同等の機密情報**として管理してください。

### 重要な注意事項

1. **環境間でのキー共有**
   - 本番とローカルで**同じキーを使う必要があるか？**
     - 現仕様では、`connect_password_enc`を復号してF-REGI APIに送信するため、**本番DBの復号には本番キーが必要**です
     - 環境ごとに別キーにすることは可能ですが、**DBを移送する場合は注意**が必要です
   - 推奨：本番とローカルで**別キーを使用**し、本番DBをローカルに移行する場合は、一時的に本番キーを設定して復号・再暗号化を行う

2. **キーローテーション時の注意**
   - キーを変更すると、既存の`connect_password_enc`を復号できなくなる可能性があります
   - ローテーション時は、以下の手順が必要です：
     1. 旧キーで既存の`connect_password_enc`を復号
     2. 新キーで再暗号化
     3. 管理画面からF-REGI設定を再保存（パスワードを再入力）

3. **未設定時のエラー**
   - `FREGI_SECRET_KEY`が未設定のままF-REGI設定を保存しようとすると、管理画面に以下のエラーが表示されます：
     - `F-REGI暗号化キー（FREGI_SECRET_KEY）が未設定です。.env に設定してから再度保存してください。`
   - 500エラーではなく、分かりやすいエラーメッセージが表示されます

4. **キーの保管**
   - 本番環境の`FREGI_SECRET_KEY`は安全なパスワードマネージャー等に保管してください
   - バックアップ・移行時に使用できるようにしておいてください

---

## 10. F-REGI接続先の制御：FREGI_ENV

### 概要
F-REGI APIへの接続先を制御する環境変数です。`APP_ENV`とは独立して設定できます。

- **`FREGI_ENV=test`**: テスト環境（`https://ssl.f-regi.com/connecttest/authm.cgi`）
- **`FREGI_ENV=prod`**: 本番環境（`https://ssl.f-regi.com/connect/authm.cgi`）

**重要**: 本番サーバ（`APP_ENV=production`）でも、テスト接続を行う場合は`FREGI_ENV=test`を設定してください。

### .envへの設定

```env
# F-REGI接続先（test: テスト環境, prod: 本番環境）
FREGI_ENV=test
```

### 本番環境（Plesk）での設定手順

1. **`.env`ファイルに追記**
   - ファイルパス: `/httpdocs/webroot/billing/app/.env`
   - 追記内容:
     ```env
     FREGI_ENV=test
     ```
   - 注意: 既存の`.env`ファイルに追記してください（上書きしない）

2. **Config Cacheの再生成（重要）**
   - Pleskでは`php artisan`コマンドが実行できないため、以下の方法で対応：
   
   **方法1: Config Cacheファイルを削除**
   - ファイルパス: `/httpdocs/webroot/billing/app/bootstrap/cache/config.php`
   - このファイルを削除すると、次回アクセス時に自動的に再生成されます
   - Pleskファイルマネージャーから削除可能
   
   **方法2: ブラウザでアクセスして再生成**
   - 管理画面にアクセスすると、config cacheが自動的に再生成されます
   - ただし、既存のcacheファイルがある場合は反映されないため、**方法1を推奨**

3. **設定確認**
   - ログファイル（`storage/logs/laravel.log`）で以下を確認：
     - `F-REGIオーソリ処理送信前`ログに`fregi_env: test`と`auth_url: https://ssl.f-regi.com/connecttest/authm.cgi`が記録されていること
   - ログ確認方法（Pleskファイルマネージャー）:
     - `/httpdocs/webroot/billing/app/storage/logs/laravel.log`を開く
     - 最新の`F-REGIオーソリ処理送信前`ログを確認

### トラブルシューティング

**問題**: `.env`に`FREGI_ENV=test`を設定したが、まだ`connect/authm.cgi`に接続している

**原因**: Config Cacheが古いまま

**対処**:
1. `/httpdocs/webroot/billing/app/bootstrap/cache/config.php`を削除
2. 管理画面にアクセスしてconfig cacheを再生成
3. 再度F-REGI決済を実行してログを確認

**問題**: ログに`fregi_env`や`auth_url`が表示されない

**原因**: 古いコードがデプロイされている可能性

**対処**:
1. `FregiApiService.php`が最新版であることを確認
2. ファイルの更新日時を確認
3. 必要に応じて再デプロイ

### 注意事項

- `FREGI_ENV`は`APP_ENV`とは独立しています
- 本番サーバ（`APP_ENV=production`）でも`FREGI_ENV=test`を設定することで、テスト環境に接続できます
- `FREGI_ENV`を変更した場合は、必ずconfig cacheを再生成してください
- ログには`fregi_env`と`auth_url`が記録されるため、接続先を確認できます
