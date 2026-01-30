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
- 申込フォーム〜申込受付完了フローが動くこと
- 申込受付時の通知メール送信・送信先設定・送信テストが動くこと

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

## 5. デプロイ後の確認
- 申込フォーム: `https://dschatbot.ai/webroot/billing/` または `/contract/create`
- 申込完了: 申込〜完了画面まで正常に動作すること
- 管理画面: ログイン・申込一覧・契約詳細が表示されること
- 送信先メール: 管理者管理から送信先メールアドレス設定・保存・送信テストができること（本番 MAIL_* 設定後）

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

## 9. 本番必須環境変数

本番用 `.env` は `deploy/webroot_billing/app/.env` に配置し、以下を必ず設定する。

- **APP_KEY**: `php artisan key:generate` で生成した値（ローカルで生成しコピー可）
- **DB_***: 本番DBの接続情報（DB_DATABASE, DB_USERNAME, DB_PASSWORD）
- **APP_URL**: `https://dschatbot.ai/webroot/billing`
- **SESSION_PATH**: `/webroot/billing`
- **MAIL_***: 申込受付通知・送信テスト用（MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS 等。本番メールサーバに合わせて設定）

`.env` 変更後、Pleskでは `php artisan config:clear` が使えないため、`/httpdocs/webroot/billing/app/bootstrap/cache/config.php` を削除すると次回アクセス時に再生成される。
