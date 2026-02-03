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
- **デプロイ方式（ルールとして継続）**: **deploy ディレクトリに事前準備を行い、FTP アップロードする**。本番投入用成果物は `deploy/webroot_billing/` にローカルで組み立て、その内容を FTP（または Plesk ファイルマネージャ）で `/httpdocs/webroot/billing` にアップロードする。

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
- （決済は、今後追加される仕様書に従って実装・確認する）

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

## 5. ルーティング確認（決済）
- 決済通知・戻りURLは、今後追加される決済仕様書（トークン方式+3DS2.0・方式A）で定義される。現時点では AIdocs に決済仕様は存在しない。

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

