# FTPデプロイ手順書

作成日: 2026-01-15  
最終更新: 2026-01-21（オプション製品機能対応版）

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

#### 1.1 デプロイパッケージの再生成（重要）

**重要**: デプロイパッケージ（`deploy/webroot_billing/`）が最新のコードを反映していることを確認してください。

**確認項目**:
- `app/resources/views/contracts/create.blade.php` に最新の修正が含まれているか（`url()`ヘルパーを使用したURL生成）
- `app/app/Http/Controllers/ContractController.php` に `getOptionProducts()` メソッドが含まれているか
- `app/routes/web.php` に `contract.api.option-products` ルートが含まれているか

**デプロイパッケージが古い場合**:
1. 最新のコードを確認（コミット: `778ef57` 以降）
2. デプロイパッケージを再生成
3. 必要なファイルが最新であることを確認

#### 1.2 本番環境用index.phpの準備

`app/public/index.production.php` を `app/public/index.php` として本番環境にアップロードします。

**注意**: ローカルの `app/public/index.php` は相対パスを使用しているため、本番環境では `index.production.php` を使用してください。

#### 1.3 .envファイルの準備

本番環境用の `.env` ファイルを準備します。`AIdocs/本番環境.envテンプレート.txt` をコピーして使用してください。

**必須設定項目**:
- `APP_KEY`: `php artisan key:generate` で生成した値
- `FREGI_SECRET_KEY`: ローカル環境と同じ値（既存の暗号化データと互換性を保つため）
- `FREGI_ENV=prod`: 本番環境では `prod` を設定
- データベース接続情報（DB_DATABASE, DB_USERNAME, DB_PASSWORD）

**重要**: 
- `APP_DEBUG=false` を設定（本番環境）
- `LOG_LEVEL=error` を設定（ただし、`contract_payment`チャンネルは`info`レベルで記録されます）

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

#### 5.3 オプション製品機能の確認

1. **公開フォームでの確認**
   - `https://dschatbot.ai/webroot/billing/contract/create` にアクセス
   - 製品（契約プラン）を選択
   - オプション製品が動的に表示されることを確認
   - ブラウザの開発者ツール（コンソール）でエラーがないことを確認

2. **管理画面での確認**
   - 管理画面にログイン
   - 「製品管理」画面を開く
   - オプション製品を登録・編集できることを確認
   - ベース製品にオプション製品を紐づけられることを確認

#### 5.4 ルーティングの確認

- 404エラーが発生しないか確認
- 各ページが正しく表示されるか確認
- APIエンドポイント（`/contract/api/option-products/{id}`）が正常に動作するか確認

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

### 基本動作確認

- [ ] 公開ページが正常に表示される
- [ ] 管理画面が正常に表示される
- [ ] ログインが正常に動作する
- [ ] データベース接続が正常に動作する
- [ ] 静的ファイルが正しく読み込まれる
- [ ] ルーティングが正しく動作する
- [ ] F-REGI設定が登録されている

### オプション製品機能の確認

- [ ] マイグレーションSQLが正しく実行されている（テーブル構造確認）
- [ ] 公開フォームでオプション製品が動的に表示される
- [ ] 管理画面でオプション製品を登録・編集できる
- [ ] ベース製品にオプション製品を紐づけられる
- [ ] APIエンドポイント（`/contract/api/option-products/{id}`）が正常に動作する
- [ ] エラーログに問題がない

### ログ機能の確認

- [ ] ログファイルが作成されている（`contract-payment-YYYY-MM-DD.log`）
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
