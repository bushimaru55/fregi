# Billing System - F-REGI決済管理

Laravel 10とF-REGIを統合したBilling（決済管理）アプリケーション

## 概要

このアプリケーションは、F-REGI決済システムと連携し、契約申込から決済処理までを一元管理するシステムです。
- **契約申込フォーム**（企業情報入力、プラン選択）
- **契約プラン管理**（学習ページ数 50〜300、6プラン）
- **F-REGI設定のDB管理**（接続パスワードの暗号化保存）
- **決済フロー**（リダイレクト方式、クレジットカード決済）
- **通知受領**（受付CGI）の冪等処理
- **契約・決済状態の管理**

## 技術スタック

- **フレームワーク**: Laravel 10.50.0
- **PHP**: 8.1.34
- **データベース**: MySQL 8.0
- **インフラ**: Docker Compose (Nginx, PHP-FPM, MySQL, Redis)
- **決済システム**: F-REGI（リダイレクト方式）

## 環境構築

### 前提条件
- Docker Desktop
- Docker Compose

### 起動方法

```bash
# コンテナ起動
docker compose up -d --build

# 動作確認
curl http://localhost:8080/billing/
```

### 初回セットアップ（既に完了済み）

```bash
# マイグレーション
docker compose exec app bash -lc "cd /var/www && php artisan migrate"

# FREGI_SECRET_KEYの生成（.envに設定済み）
php -r "echo base64_encode(random_bytes(32)) . PHP_EOL;"
```

## アクセスURL

### 公開ページ
- **トップページ**: http://localhost:8080/billing/
- **新規申込フォーム**: http://localhost:8080/billing/contract/create

### 管理画面（要ログイン）
- **ログイン**: http://localhost:8080/billing/login
- **ダッシュボード**: http://localhost:8080/billing/admin/dashboard
- **契約管理**: http://localhost:8080/billing/admin/contracts
- **契約プラン管理**: http://localhost:8080/billing/admin/contract-plans
- **F-REGI設定**: http://localhost:8080/billing/admin/fregi-configs

**管理者アカウント:**
- メールアドレス: `kanri@dschatbot.ai` / パスワード: `cs20051101`
- メールアドレス: `dsbrand@example.com` / パスワード: `cs20051101`

### API
- **F-REGI通知受領**: http://localhost:8080/billing/api/fregi/notify

## データベース構造

### テーブル一覧
1. **users** - 管理者ユーザー
2. **contract_plans** - 契約プラン（学習ページ数、料金）
3. **contracts** - 契約情報（申込企業情報、契約内容）
4. **fregi_configs** - F-REGI接続設定
5. **fregi_config_versions** - 設定変更履歴
6. **payments** - 決済情報
7. **payment_events** - 決済イベントログ

## 主要機能

### 1. 認証機能（Laravel Breeze）
- **ログイン/ログアウト**: 管理画面へのアクセス制御
- **パスワードリセット**: メール経由でのパスワード再設定
- **プロフィール編集**: 管理者情報の更新
- **初期管理者**: dsbrand / cs20051101

### 2. 契約申込フロー（公開ページ）
- **申込フォーム**: 企業情報入力、プラン選択、利用開始日設定
- **確認画面**: 入力内容の確認
- **決済連携**: F-REGIへのリダイレクト、クレジットカード決済
- **完了画面**: 契約・決済情報の表示

### 3. 契約プラン管理（管理画面）
- **プランCRUD**: 作成、一覧表示、編集、削除
- **学習ページ数**: プランごとの学習ページ数設定
- **料金設定**: 各プランの税込料金
- **有効/無効管理**: プランの公開/非公開制御
- **表示順管理**: 申込フォームでの表示順序設定

### 4. 契約管理（管理画面）
- **契約一覧表示**: ステータス別色分け、ページネーション
- **契約詳細表示**: 企業情報、決済情報、イベント履歴
- **ステータス管理**: 下書き、決済待ち、有効、キャンセル、期限切れ
- **契約明細**: 商品情報のスナップショット保存

### 5. ダッシュボード（管理画面）
- **統計カード**: 契約数、有効契約数、契約プラン数、決済完了数
- **最近の契約**: 最新5件の契約表示
- **クイックアクション**: 主要機能へのショートカット

### 6. F-REGI設定管理（管理画面）
- **設定CRUD**: 作成、一覧表示、編集、削除
- **接続パスワードの暗号化保存**: AES-GCM方式
- **変更履歴の自動保存**: 設定変更の完全な履歴管理
- **環境管理**: test/prodごとの設定

### 7. 決済フロー
- **決済開始**: F-REGIへのリダイレクト
- **通知受領**: 冪等処理で重複防止
- **戻りURL**: 成功/キャンセル/失敗ページ
- **決済ステータス管理**: リアルタイム更新

### 8. セキュリティ
- **認証ミドルウェア**: 管理画面全体を保護
- **AES-GCM暗号化**: 接続パスワードの安全な保存
- **チェックサム検証**: F-REGI通知の正当性確認
- **CSRF保護**: 全フォームにトークン必須
- **ログマスク**: 秘密情報の自動マスク
- **カード情報**: 当システムでは扱わない（PCI DSS準拠）

## ディレクトリ構造

```
billing/
├── app/                      # Laravelプロジェクト
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Admin/FregiConfigController.php
│   │   │   │   ├── Api/FregiNotifyController.php
│   │   │   │   ├── ContractController.php
│   │   │   │   ├── PaymentController.php
│   │   │   │   └── ReturnController.php
│   │   │   └── Requests/
│   │   │       ├── ContractRequest.php
│   │   │       └── FregiConfigRequest.php
│   │   ├── Models/
│   │   │   ├── Contract.php
│   │   │   ├── ContractPlan.php
│   │   │   ├── FregiConfig.php
│   │   │   ├── FregiConfigVersion.php
│   │   │   ├── Payment.php
│   │   │   └── PaymentEvent.php
│   │   └── Services/
│   │       ├── EncryptionService.php
│   │       ├── FregiConfigService.php
│   │       └── FregiPaymentService.php
│   └── resources/views/
│       ├── layouts/app.blade.php
│       ├── contracts/
│       │   ├── create.blade.php
│       │   ├── confirm.blade.php
│       │   └── complete.blade.php
│       ├── admin/
│       │   ├── contracts/
│       │   │   ├── index.blade.php
│       │   │   └── show.blade.php
│       │   └── fregi-configs/
│       ├── payment/
│       └── return/
├── docker/
│   ├── nginx/default.conf
│   └── php/
│       ├── Dockerfile
│       └── php.ini
├── docker-compose.yml
└── AIdocs/                   # 仕様書（開発ガイド）
    ├── AIdocs_Cursor指示書_Billingアプリ_ローカルDocker開始.md
    ├── AIdocs_Cursor指示書_FREGI設定DB管理.md
    └── AIdocs_申込フォーム仕様.md
```

## 環境変数

`.env` ファイルの重要な設定:

```env
APP_URL=http://localhost:8080/billing
DB_HOST=db
DB_DATABASE=billing
DB_USERNAME=billing
DB_PASSWORD=billing_pass
SESSION_COOKIE=billing_session
SESSION_PATH=/billing
FREGI_SECRET_KEY=Y1AExhmfYZYSwOJ24QRTEV1YoD87NxzwBNLmMdZsUSc=
```

### FREGI_SECRET_KEY（必須）

F-REGI設定の接続パスワードを暗号化するために使用する秘密鍵です。**ローカル・本番ともに必須**です。

#### キーの生成方法

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

#### .envへの設定

生成したキーを`.env`ファイルに追加：

```env
FREGI_SECRET_KEY=Y1AExhmfYZYSwOJ24QRTEV1YoD87NxzwBNLmMdZsUSc=
```

#### 重要な注意事項

1. **本番環境でのキー管理**
   - 本番環境の`FREGI_SECRET_KEY`は**パスワードと同等の機密情報**として管理してください
   - 安全なパスワードマネージャー等に保管し、バックアップ・移行時に使用できるようにしておいてください

2. **環境間でのキー共有**
   - 本番とローカルで**同じキーを使う必要があるか？**
     - 現仕様では、`connect_password_enc`を復号してF-REGI APIに送信するため、**本番DBの復号には本番キーが必要**です
     - 環境ごとに別キーにすることは可能ですが、**DBを移送する場合は注意**が必要です
   - 推奨：本番とローカルで**別キーを使用**し、本番DBをローカルに移行する場合は、一時的に本番キーを設定して復号・再暗号化を行う

3. **キーローテーション時の注意**
   - キーを変更すると、既存の`connect_password_enc`を復号できなくなる可能性があります
   - ローテーション時は、以下の手順が必要です：
     1. 旧キーで既存の`connect_password_enc`を復号
     2. 新キーで再暗号化
     3. 管理画面からF-REGI設定を再保存（パスワードを再入力）

4. **未設定時のエラー**
   - `FREGI_SECRET_KEY`が未設定のままF-REGI設定を保存しようとすると、管理画面に以下のエラーが表示されます：
     - `F-REGI暗号化キー（FREGI_SECRET_KEY）が未設定です。.env に設定してから再度保存してください。`

## 開発ガイド

### コーディング規約
- PSR-12準拠
- サービス層の活用
- リレーションの明確化
- 秘密情報のログ出力禁止

### コマンド

```bash
# Artisanコマンド実行
docker compose exec app bash -lc "cd /var/www && php artisan [command]"

# ルート一覧
docker compose exec app bash -lc "cd /var/www && php artisan route:list"

# マイグレーション
docker compose exec app bash -lc "cd /var/www && php artisan migrate"

# ログ確認
docker compose logs app --tail=50
```

## 本番デプロイ

本番環境は `/billing` パスで動作します。
- 公開ディレクトリ: `httpdocs/webroot/billing/`
- Laravel本体: `httpdocs/laravel_billing/`
- デプロイ方法: FTPアップロード

詳細は `AIdocs/AIdocs_Cursor指示書_Billingアプリ_ローカルDocker開始.md` を参照してください。

## トラブルシューティング

### コンテナが起動しない
```bash
docker compose down
docker compose up -d --build
```

### データベース接続エラー
```bash
# DBコンテナの状態確認
docker compose ps db
docker compose logs db
```

### 404エラー
- Nginx設定を確認: `docker/nginx/default.conf`
- Webコンテナを再起動: `docker compose restart web`

## ライセンス

Proprietary

## 参考資料

- [AIdocs/AIdocs_Cursor指示書_Billingアプリ_ローカルDocker開始.md](AIdocs/AIdocs_Cursor指示書_Billingアプリ_ローカルDocker開始.md)
- [AIdocs/AIdocs_Cursor指示書_FREGI設定DB管理.md](AIdocs/AIdocs_Cursor指示書_FREGI設定DB管理.md)

