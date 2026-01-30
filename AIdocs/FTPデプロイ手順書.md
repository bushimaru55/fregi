# FTPデプロイ手順書

作成日: 2026-01-15  
最終更新: 申込受付・通知メール仕様（1フォルダパッケージ）

## 前提条件

- FTP接続情報を取得済み
- 本番環境のディレクトリ構造を理解している
- Basic認証がかかっている状態で動作確認を行う

---

## 本番環境のディレクトリ構成

デプロイは **1フォルダ** で行います。`deploy/webroot_billing/` の中身をそのまま `/httpdocs/webroot/billing/` にアップロードします。

```
/var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/
├── index.php         # スクリプトが生成（index.production.php のリネームは不要）
├── .htaccess
├── build/
├── css/
├── js/
├── favicon.ico
├── robots.txt
└── app/              # Laravel 本体
    ├── app/
    ├── bootstrap/
    ├── config/
    ├── database/
    ├── resources/
    ├── routes/
    ├── storage/
    ├── vendor/
    ├── .env          # 本番用（手動で配置。スクリプトではコピーされない）
    ├── artisan
    ├── composer.json
    └── composer.lock
```

---

## デプロイ手順

### ステップ1: ローカルで準備

#### 1.1 デプロイパッケージの再生成（重要）

**重要**: デプロイパッケージ（`deploy/webroot_billing/`）が最新のコードを反映していることを確認してください。

**手順**:
1. プロジェクトルートで `./scripts/build_deploy_webroot_billing.sh` を実行
2. スクリプトはローカルの `app/public/build/` を変更せず、一時ディレクトリで Vite ビルドし、成果物のみ `deploy/webroot_billing/` に出力する
3. 生成後、`deploy/webroot_billing/build/manifest.json` が存在することを確認

**デプロイパッケージが古い場合**: 上記スクリプトを再実行してからアップロードしてください。

#### 1.2 .envファイルの準備

本番用 `.env` は **スクリプトではコピーされません**。手動で準備します。

1. `AIdocs/本番環境.envテンプレート.txt` をコピー
2. **deploy/webroot_billing/app/.env** として保存
3. 本番の値を設定：
   - `APP_KEY`（ローカルで `php artisan key:generate` で生成した値）
   - データベース接続情報（`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`）
   - `APP_URL=https://dschatbot.ai/webroot/billing`
   - `SESSION_PATH=/webroot/billing`
   - 申込受付通知・送信テスト用の `MAIL_*`（本番メールサーバに合わせる）
   - `APP_DEBUG=false`, `LOG_LEVEL=error`

**重要**: `index.php` はスクリプトが生成するため、`index.production.php` のリネーム手順は不要です。

---

### ステップ2: FTP接続とアップロード

**アップロード先（1箇所）**: `/httpdocs/webroot/billing/`（Plesk の実際のパスは環境に応じて確認）

**アップロードする内容**: **deploy/webroot_billing/** の **中身すべて** を上記ディレクトリにアップロード

- `index.php`（スクリプトが生成済み）
- `.htaccess`
- `build/`, `css/`, `js/`, `favicon.ico`, `robots.txt` 等
- `app/` ディレクトリ一式（Laravel 本体。その中に本番用 `.env` を配置済みであること）

**注意**:
- `.git/` はアップロードしない（deploy パッケージには含まれない想定）
- 本番用 `.env` は事前に `deploy/webroot_billing/app/.env` に配置してからアップロードする

---

### ステップ3: ファイルパーミッションの設定

FTPクライアントまたはPleskファイルマネージャで、以下を可能な範囲で設定：

- `app/storage/`: `775`
- `app/bootstrap/cache/`: `775`
- `app/storage/logs/`: `775`

**注意**: FTPクライアントでパーミッションを設定できない場合は、サーバー管理者に依頼してください。

---

### ステップ4: データベースのセットアップ

#### 4.1 データベースのバックアップ（必須）

**重要**: マイグレーション実行前に必ずデータベースのバックアップを取得してください。

1. Pleskにログイン
2. データベース一覧を表示
3. `billing_prod` を選択
4. 「エクスポート」をクリック
5. 完全バックアップをダウンロード

#### 4.2 オプション製品機能のマイグレーション実行

**本番環境ではSSH/CLIが使えないため、SQLファイルを直接実行します。**

**手順**:
1. `AIdocs/オプション商品機能_本番環境マイグレーションSQL.sql` の内容を確認
2. Pleskにログイン
3. データベース一覧から `billing_prod` を選択
4. 「phpMyAdminで開く」をクリック
5. SQLタブを選択
6. SQLファイルの内容をコピーして貼り付け
7. 「実行」ボタンをクリック
8. エラーがないことを確認

**詳細な手順**: `AIdocs/オプション商品機能_本番環境マイグレーション手順.md` を参照してください。

#### 4.3 マイグレーション実行後の確認

phpMyAdminで以下のSQLを実行して、テーブル構造を確認：

```sql
-- productsテーブルの構造確認
DESCRIBE `products`;

-- contract_itemsテーブルの構造確認
DESCRIBE `contract_items`;

-- contractsテーブルの構造確認
DESCRIBE `contracts`;

-- contract_plan_option_productsテーブルの存在確認
DESCRIBE `contract_plan_option_products`;
```

**確認項目**:
- `products`テーブルに `type`, `description`, `is_active`, `display_order` カラムが存在するか
- `contract_items`テーブルに `contract_plan_id` カラムが存在し、`product_id` が nullable か
- `contracts`テーブルの `payment_id` が nullable か
- `contract_plan_option_products` テーブルが存在するか

#### 4.2 管理者アカウントの作成

SSH接続が可能な場合：

```bash
php artisan db:seed --class=AdminUserSeeder
```

SSH接続が不可能な場合：
- データベースに直接接続して、管理者アカウントを手動で作成
- または、初回ログイン時にパスワードリセット機能を使用

---

### ステップ5: 動作確認

#### 5.1 基本動作確認

1. `https://dschatbot.ai/webroot/billing/` にアクセス
   - Basic認証を通過
   - 新規申込フォーム（またはトップ）が表示されるか確認

2. 静的ファイルの確認
   - `https://dschatbot.ai/webroot/billing/build/manifest.json` が 200 で返るか確認
   - CSS、JSが正しく読み込まれるか確認
   - ブラウザの開発者ツールでエラーがないか確認

#### 5.2 管理画面の確認

1. `https://dschatbot.ai/webroot/billing/login` にアクセス
   - ログイン画面が表示されるか確認

2. ログイン
   - 管理者アカウントでログイン
   - ダッシュボードが表示されるか確認

3. 各管理画面の確認
   - 契約管理（申込一覧・契約詳細）
   - 契約プラン管理
   - 管理者管理・送信先メールアドレス設定・送信テスト
   - サイト管理

#### 5.3 申込・通知の確認

1. **申込フロー**
   - `https://dschatbot.ai/webroot/billing/contract/create` にアクセス
   - 申込フォーム〜申込受付完了まで正常に動作することを確認

2. **送信先メール**
   - 管理画面「管理者管理」から送信先メールアドレスを設定・保存
   - 送信テストが実行できること（本番 MAIL_* 設定後）

#### 5.4 ルーティングの確認

- 404エラーが発生しないか確認
- 各ページが正しく表示されるか確認

#### 5.5 ログの確認

1. **ログファイルの場所**
   - `/httpdocs/webroot/billing/app/storage/logs/contract-payment-YYYY-MM-DD.log`

2. **ログの確認方法**
   - Pleskファイルマネージャーからログファイルを開く
   - エラーログがないか確認
   - オプション製品取得APIのログを確認（`オプション製品取得` または `オプション製品取得エラー` で検索）

**詳細**: `AIdocs/ログ機能説明.md` を参照してください。

---

## トラブルシューティング

### 問題1: 500エラーが発生する

**確認事項**:
- `.env` ファイルが正しく配置されているか
- `APP_KEY` が設定されているか
- ファイルパーミッションが正しいか
- エラーログを確認（`storage/logs/laravel.log`）

### 問題2: 404エラーが発生する

**確認事項**:
- `.htaccess` ファイルが正しく配置されているか（`RewriteBase /webroot/billing/` が含まれるか）
- `index.php` が正しく配置されているか（スクリプトが生成したものをアップロードしているか）
- Apacheの `mod_rewrite` が有効になっているか

### 問題3: 静的ファイルが読み込まれない

**確認事項**:
- ファイルが正しくアップロードされているか
- ファイルパスが正しいか
- ファイルパーミッションが正しいか

### 問題4: データベース接続エラー

**確認事項**:
- `.env` のデータベース接続情報が正しいか
- データベースサーバーが起動しているか
- データベースのユーザー名・パスワードが正しいか

---

## デプロイ後の確認事項

### 基本動作確認

- [ ] `https://dschatbot.ai/webroot/billing/` で公開ページが正常に表示される
- [ ] `https://dschatbot.ai/webroot/billing/build/manifest.json` が 200 で返る
- [ ] 管理画面が正常に表示され、ログインが動作する
- [ ] データベース接続が正常に動作する
- [ ] 静的ファイルが正しく読み込まれる
- [ ] ルーティングが正しく動作する

### 申込・通知の確認

- [ ] 申込フォーム〜申込受付完了まで正常に動作する
- [ ] 送信先メールアドレスの設定・保存ができる
- [ ] 送信テストが実行できる（本番 MAIL_* 設定後）

### ログ機能の確認

- [ ] ログファイルが作成されている（`app/storage/logs/` 配下。`laravel.log`, `mail-YYYY-MM-DD.log` 等）
- [ ] ログファイルのパーミッションが適切に設定されている（775）
- [ ] エラーログに問題がない

---

## 参考資料

### デプロイ関連

- `AIdocs/本番環境デプロイ準備チェックリスト.md` - デプロイ前の準備確認
- `AIdocs/本番環境デプロイチェックリスト.md` - デプロイ後の確認事項
- `AIdocs/本番環境デプロイ準備_3回確認結果.md` - デプロイ準備の確認結果

### マイグレーション関連

- `AIdocs/オプション商品機能_本番環境マイグレーションSQL.sql` - マイグレーションSQL
- `AIdocs/オプション商品機能_本番環境マイグレーション手順.md` - マイグレーション手順

### 機能説明

- `AIdocs/オプション商品_管理画面操作手順_統合版.md` - オプション製品の管理方法
- `AIdocs/ログ機能説明.md` - ログ機能の説明と確認方法

### その他

- `AIdocs/本番環境整合性確認レポート.md` - 整合性確認結果
- `AIdocs/Apache設定確認レポート.md` - Apache設定
- `AIdocs/AIdocs_Cursor指示書_Billingアプリ_ローカルDocker開始.md` - ローカル環境のセットアップ
