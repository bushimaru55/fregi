# 本番環境デプロイパッケージ

## 概要

このパッケージは、本番環境の `/httpdocs/webroot/billing/` ディレクトリにデプロイするためのファイル一式です。

**デプロイ先URL**: `https://dschatbot.ai/webroot/billing/`

---

## ディレクトリ構成

```
webroot_billing/
├── index.php              # エントリーポイント（app/を参照）
├── .htaccess              # Apache設定
├── build/                 # Viteビルド成果物
├── css/                   # CSSファイル
├── js/                    # JavaScriptファイル
├── favicon.ico            # ファビコン
├── robots.txt             # robots.txt
├── app/                   # Laravel本体（非公開）
│   ├── .htaccess         # アクセス禁止
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/          # 書き込み権限必要（775）
│   ├── vendor/
│   ├── artisan
│   ├── composer.json
│   └── .env              # 本番環境用設定ファイル
└── README.md             # このファイル
```

---

## FTPアップロード手順

### 1. FTP接続

FTPクライアント（FileZilla、Cyberduck等）を使用して本番サーバーに接続します。

### 2. アップロード先

**アップロード先**: `/var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/`

### 3. アップロードするファイル

この `webroot_billing/` ディレクトリ内の**すべてのファイルとディレクトリ**をアップロードします。

**アップロード時の注意:**
- `.htaccess` ファイルも必ずアップロードしてください
- `app/.htaccess` も必ずアップロードしてください
- ディレクトリ構造を保持したままアップロードしてください

### 4. ファイルパーミッションの設定

FTPクライアントまたはSSH接続で以下のパーミッションを設定します。

```bash
# ストレージディレクトリ（書き込み権限が必要）
chmod -R 775 /var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/app/storage
chmod -R 775 /var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/app/bootstrap/cache
```

**重要**: `storage/` と `bootstrap/cache/` は書き込み権限（775）が必要です。これらが正しく設定されていないと、アプリケーションが正常に動作しません。

### 5. 所有者の設定（必要に応じて）

サーバー環境によっては、所有者の設定が必要な場合があります。

```bash
chown -R www-data:www-data /var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/app/storage
chown -R www-data:www-data /var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/app/bootstrap/cache
```

**注意**: サーバー環境によっては、FTP接続でアップロードしたファイルの所有者が異なる場合があります。サーバー管理者に確認してください。

---

## 環境変数（.env）の設定

### 1. .envファイルの作成

`app/.env` ファイルを作成または編集します。

**重要**: 本番環境へアップロードする際、`app/.env` ファイルも含めてアップロードしてください。ただし、`APP_KEY` は本番環境固有の値として管理し、本番環境で生成したものを使用することを推奨します（セキュリティ上の理由）。

### 2. 必須設定項目

```env
APP_NAME="Billing System"
APP_ENV=production
APP_KEY=                    # 後で生成
APP_DEBUG=false
APP_URL=https://dschatbot.ai/webroot/billing

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=               # 本番環境のデータベース名
DB_USERNAME=               # 本番環境のデータベースユーザー名
DB_PASSWORD=               # 本番環境のデータベースパスワード

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_COOKIE=billing_session
SESSION_PATH=/webroot/billing

# F-REGI暗号化キー
FREGI_SECRET_KEY=          # ローカル環境の.envからコピー
```

### 3. APP_KEYの生成

**重要**: `APP_KEY` は本番環境固有の値として管理してください。セキュリティ上の理由から、本番環境で生成したキーを使用することを強く推奨します。

SSH接続が可能な場合：

```bash
cd /var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/app
php artisan key:generate
```

SSH接続が不可能な場合、ローカル環境で生成したキーを使用：

```bash
cd /path/to/local/project/app
php artisan key:generate --show
```

生成されたキーを `.env` の `APP_KEY` に設定します。

**注意**: デプロイパッケージに含まれる `.env` ファイルには、動作確認用の `APP_KEY` が含まれている場合があります。本番環境では、必ず本番環境で生成した新しい `APP_KEY` に置き換えてください。

---

## データベースのセットアップ

### マイグレーションの実行

SSH接続が可能な場合：

```bash
cd /var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/app
php artisan migrate --force
```

### 管理者アカウントの作成

SSH接続が可能な場合：

```bash
cd /var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/app
php artisan tinker
```

Tinker内で実行：

```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

User::create([
    'name' => '管理者',
    'email' => 'kanri@dschatbot.ai',
    'password' => Hash::make('cs20051101'),
]);
```

---

## 動作確認

### 1. 基本動作確認

1. **公開ページ**
   - `https://dschatbot.ai/webroot/billing/` にアクセス
   - 新規申込フォームが表示されることを確認
   - 404エラーが発生しないことを確認

2. **管理画面**
   - `https://dschatbot.ai/webroot/billing/login` にアクセス
   - ログインが正常に動作することを確認
   - ダッシュボードが表示されることを確認

3. **静的ファイル**
   - CSS、JS、画像が正しく読み込まれることを確認
   - ブラウザの開発者ツールでエラーがないか確認

### 2. セキュリティ確認

以下のURLにアクセスして、**403 Forbidden** が返されることを確認してください：

- ✅ `https://dschatbot.ai/webroot/billing/app/.env` → 403 Forbidden（正常）
- ✅ `https://dschatbot.ai/webroot/billing/app/vendor/` → 403 Forbidden（正常）
- ✅ `https://dschatbot.ai/webroot/billing/app/storage/` → 403 Forbidden（正常）
- ✅ `https://dschatbot.ai/webroot/billing/app/config/` → 403 Forbidden（正常）

**正常にアクセスできるURL（確認用）:**
- ✅ `https://dschatbot.ai/webroot/billing/build/manifest.json` → 200 OK（正常）

**注意**: `app/` 配下のURLが200 OKでアクセスできる場合は、セキュリティ上の問題があります。`app/.htaccess` の設定を確認してください。

### 3. エラーログの確認

問題が発生した場合：

```bash
# Laravelのエラーログ
tail -f /var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/app/storage/logs/laravel.log

# Apacheのエラーログ（サーバー管理者に確認）
```

---

## トラブルシューティング

### 500 Internal Server Error

**原因**: パーミッション、.env設定、APP_KEYの問題

**解決方法**:
1. `app/storage/` と `app/bootstrap/cache/` のパーミッションを確認（775）
2. `app/.env` ファイルが正しく設定されているか確認
3. `APP_KEY` が設定されているか確認

### 404 Not Found

**原因**: `.htaccess` または `mod_rewrite` の問題

**解決方法**:
1. `.htaccess` ファイルが正しく配置されているか確認
2. Apacheの `mod_rewrite` が有効になっているか確認
3. `RewriteBase /webroot/billing/` を追加してみる

### データベース接続エラー

**原因**: `.env` のデータベース設定が間違っている

**解決方法**:
1. `app/.env` の `DB_*` 設定を確認
2. データベースサーバーが起動しているか確認
3. データベースのユーザー名・パスワードが正しいか確認

### 静的ファイルが読み込まれない

**原因**: ファイルパスまたはパーミッションの問題

**解決方法**:
1. ファイルが正しくアップロードされているか確認
2. ファイルパーミッションを確認
3. ブラウザの開発者ツールで404エラーを確認

---

## 必要なファイルパーミッション

以下のディレクトリは**書き込み権限（775）**が必要です：

- `app/storage/` およびその配下のすべてのディレクトリ
- `app/bootstrap/cache/`

**設定コマンド**:

```bash
chmod -R 775 /var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/app/storage
chmod -R 775 /var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/app/bootstrap/cache
```

---

## 注意事項

1. **Basic認証**: 現在ドメイン全体にBasic認証がかかっているため、動作確認時もBasic認証を通過する必要があります。

2. **セキュリティ**: `app/.htaccess` により、`app/` ディレクトリへの直接アクセスは禁止されています。動作確認で403エラーが返されることを確認してください。

3. **バックアップ**: デプロイ前に既存のファイルやデータベースのバックアップを取得してください。

4. **.envファイル**: `app/.env` ファイルには機密情報が含まれています。FTPアップロード時は必ず暗号化された接続（FTPS/SFTP）を使用してください。

---

## 参考資料

- `AIdocs/本番環境セットアップガイド.md`
- `AIdocs/本番環境デプロイチェックリスト.md`
- `AIdocs/本番環境整合性確認レポート.md`
