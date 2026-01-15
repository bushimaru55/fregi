# FTPデプロイ手順書

作成日: 2026-01-15

## 前提条件

- FTP接続情報を取得済み
- 本番環境のディレクトリ構造を理解している
- Basic認証がかかっている状態で動作確認を行う

---

## 本番環境のディレクトリ構成

```
/var/www/vhosts/dschatbot.ai/httpdocs/
├── laravel_billing/          # Laravel本体（非公開）
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/               # この中身は使わない（webrootに配置）
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/               # Composer依存関係
│   ├── .env                  # 本番環境用設定ファイル
│   └── ...
└── webroot/
    └── billing/              # 公開ディレクトリ（Laravel public）
        ├── index.php         # 本番環境用（絶対パス設定）
        ├── .htaccess
        ├── css/
        ├── js/
        └── ...
```

---

## デプロイ手順

### ステップ1: ローカルで準備

#### 1.1 本番環境用index.phpの準備

`app/public/index.production.php` を `app/public/index.php` として本番環境にアップロードします。

**注意**: ローカルの `app/public/index.php` は相対パスを使用しているため、本番環境では `index.production.php` を使用してください。

#### 1.2 .envファイルの準備

本番環境用の `.env` ファイルを準備します。

```env
APP_NAME="Billing System"
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://dschatbot.ai/billing

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_COOKIE=billing_session
SESSION_PATH=/billing

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"

# F-REGI暗号化キー（必ず設定）
FREGI_SECRET_KEY=your_secret_key_here
```

**重要**: 
- `APP_KEY` は `php artisan key:generate` で生成した値を使用
- `FREGI_SECRET_KEY` はローカルと同じ値を使用（既存の暗号化データと互換性を保つため）

#### 1.3 ファイルの準備

以下のファイル・ディレクトリを準備します：

1. **Laravel本体**（`app/` ディレクトリ全体）
   - `vendor/` ディレクトリを含む（Composer依存関係）
   - `.env` ファイル（本番環境用）

2. **公開ディレクトリ**（`app/public/` の内容）
   - `index.production.php` を `index.php` として配置
   - `.htaccess`
   - `css/`, `js/`, `fonts/`, `images/` などの静的ファイル

---

### ステップ2: FTP接続とアップロード

#### 2.1 Laravel本体のアップロード

FTPクライアントで以下のディレクトリにアップロード：

**アップロード先**: `/var/www/vhosts/dschatbot.ai/httpdocs/laravel_billing/`

**アップロードする内容**:
- `app/` ディレクトリ（`public/` を除く）
- `bootstrap/`
- `config/`
- `database/`
- `resources/`
- `routes/`
- `storage/`（ディレクトリ構造のみ、中身は空でOK）
- `vendor/`
- `.env`（本番環境用）
- `artisan`
- `composer.json`
- `composer.lock`

**注意**: 
- `public/` ディレクトリはアップロードしない（次のステップで別途アップロード）
- `.git/` ディレクトリはアップロードしない

#### 2.2 公開ディレクトリのアップロード

FTPクライアントで以下のディレクトリにアップロード：

**アップロード先**: `/var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/`

**アップロードする内容**:
- `index.production.php` → `index.php` として配置
- `.htaccess`
- `css/` ディレクトリとその中身
- `js/` ディレクトリとその中身
- `fonts/` ディレクトリとその中身（存在する場合）
- `images/` ディレクトリとその中身（存在する場合）

**重要**: `index.production.php` を `index.php` としてアップロードしてください。

---

### ステップ3: ファイルパーミッションの設定

FTPクライアントまたはSSH（可能な場合）で以下のパーミッションを設定：

```bash
# storage ディレクトリの書き込み権限
chmod -R 775 /var/www/vhosts/dschatbot.ai/httpdocs/laravel_billing/storage
chown -R www-data:www-data /var/www/vhosts/dschatbot.ai/httpdocs/laravel_billing/storage

# bootstrap/cache ディレクトリの書き込み権限
chmod -R 775 /var/www/vhosts/dschatbot.ai/httpdocs/laravel_billing/bootstrap/cache
chown -R www-data:www-data /var/www/vhosts/dschatbot.ai/httpdocs/laravel_billing/bootstrap/cache
```

**注意**: FTPクライアントでパーミッションを設定できない場合は、サーバー管理者に依頼してください。

---

### ステップ4: データベースのセットアップ

#### 4.1 マイグレーションの実行

SSH接続が可能な場合：

```bash
cd /var/www/vhosts/dschatbot.ai/httpdocs/laravel_billing
php artisan migrate --force
```

SSH接続が不可能な場合：
- データベースに直接接続して、マイグレーションファイルのSQLを実行
- または、管理画面から初期データを登録

#### 4.2 管理者アカウントの作成

SSH接続が可能な場合：

```bash
php artisan db:seed --class=AdminUserSeeder
```

SSH接続が不可能な場合：
- データベースに直接接続して、管理者アカウントを手動で作成
- または、初回ログイン時にパスワードリセット機能を使用

#### 4.3 F-REGI設定の登録

管理画面（`https://dschatbot.ai/billing/admin/fregi-configs`）から：
- 本番環境用のF-REGI設定を登録
- `environment=prod` を選択
- SHOPID、接続パスワード等を設定

---

### ステップ5: 動作確認

#### 5.1 基本動作確認

1. `https://dschatbot.ai/billing/` にアクセス
   - Basic認証を通過
   - 新規申込フォームが表示されるか確認

2. 静的ファイルの確認
   - CSS、JSが正しく読み込まれるか確認
   - ブラウザの開発者ツールでエラーがないか確認

#### 5.2 管理画面の確認

1. `https://dschatbot.ai/billing/login` にアクセス
   - ログイン画面が表示されるか確認

2. ログイン
   - 管理者アカウントでログイン
   - ダッシュボードが表示されるか確認

3. 各管理画面の確認
   - 契約管理
   - 契約プラン管理
   - F-REGI設定
   - サイト管理

#### 5.3 ルーティングの確認

- 404エラーが発生しないか確認
- 各ページが正しく表示されるか確認

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
- `.htaccess` ファイルが正しく配置されているか
- `index.php` が正しく配置されているか（`index.production.php` を `index.php` として配置）
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

- [ ] 公開ページが正常に表示される
- [ ] 管理画面が正常に表示される
- [ ] ログインが正常に動作する
- [ ] データベース接続が正常に動作する
- [ ] 静的ファイルが正しく読み込まれる
- [ ] ルーティングが正しく動作する
- [ ] F-REGI設定が登録されている

---

## 参考資料

- `AIdocs/本番環境デプロイチェックリスト.md`
- `AIdocs/本番環境整合性確認レポート.md`
- `AIdocs/Apache設定確認レポート.md`
- `AIdocs/AIdocs_Cursor指示書_Billingアプリ_ローカルDocker開始.md`
