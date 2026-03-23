# Billing System - 決済管理

Laravel 10 と請求管理ロボ（決済会社API）を連携した Billing（決済・契約管理）アプリケーション

## 概要

このアプリケーションは、請求管理ロボのAPIと連携し、契約申込から決済処理までを一元管理するシステムです。
- **契約申込フォーム**（企業情報入力、プラン・商品選択）
- **契約プラン・商品管理**
- **決済フロー**（請求管理ロボ API：請求先登録・クレジットカード登録・請求情報登録・請求書発行／即時決済）
- **契約・決済状態の管理**

## 技術スタック

- **フレームワーク**: Laravel 10.50.0
- **PHP**: 8.1.34
- **データベース**: MySQL 8.0
- **インフラ**: Docker Compose (Nginx, PHP-FPM, MySQL, Redis)
- **決済連携**: 請求管理ロボ API（請求先登録更新・クレジットカード登録・請求情報登録・請求書発行／即時決済）

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

# 管理者ユーザー作成（未作成の場合）
docker compose exec app bash -lc "cd /var/www && php artisan db:seed --class=AdminUserSeeder"
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

**管理者アカウント:**
- メールアドレス: `kanri@dschatbot.ai` / パスワード: `cs20051101`
- メールアドレス: `dsbrand@example.com` / パスワード: `cs20051101`

### API
- **請求管理ロボ 通知**（キックバック）: `/billing/api/robotpayment/notify-initial`, `/billing/api/robotpayment/notify-recurring`

## データベース構造

### テーブル一覧
1. **users** - 管理者ユーザー
2. **contract_plans** - 契約プラン（料金・商品紐付け）
3. **contracts** - 契約情報（申込企業情報、契約内容）
4. **payments** - 決済情報
5. **payment_events** - 決済イベントログ

## 主要機能

### 1. 認証機能（Laravel Breeze）
- **ログイン/ログアウト**: 管理画面へのアクセス制御
- **パスワードリセット**: メール経由でのパスワード再設定
- **プロフィール編集**: 管理者情報の更新
- **初期管理者**: dsbrand / cs20051101

### 2. 契約申込フロー（公開ページ）
- **申込フォーム**: 企業情報入力、プラン・商品選択、利用開始日設定
- **確認画面**: 入力内容の確認
- **決済連携**: 請求管理ロボ（トークン方式・3Dセキュア）によるクレジットカード決済
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

### 6. 決済フロー
- **決済開始**: 請求管理ロボのトークン取得・クレジットカード登録API連携
- **通知受領**: キックバック（冪等処理）
- **決済ステータス管理**: リアルタイム更新

### 7. セキュリティ
- **認証ミドルウェア**: 管理画面全体を保護
- **チェックサム検証**: 請求管理ロボ通知の正当性確認
- **CSRF保護**: 全フォームにトークン必須
- **ログマスク**: 秘密情報の自動マスク
- **カード情報**: 当システムでは扱わない（PCI DSS準拠）

## ディレクトリ構造

```
billing/
├── app/                      # Laravelプロジェクト
│   ├── app/
│   │   ├── Http/Controllers/
│   │   │   ├── Admin/
│   │   │   ├── ContractController.php
│   │   │   └── RobotPaymentController.php
│   │   ├── Models/
│   │   │   ├── Contract.php
│   │   │   ├── ContractPlan.php
│   │   │   ├── Payment.php
│   │   │   └── PaymentEvent.php
│   │   └── Services/
│   │       └── RobotPayment/
│   └── resources/views/
├── docker/
├── docker-compose.yml
└── AIdocs/                   # 仕様書（api_documents: 請求管理ロボAPI等）
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
# 請求管理ロボ（デモ/本番）: BILLING_ROBO_BASE_URL, BILLING_ROBO_USER_ID, BILLING_ROBO_ACCESS_KEY
# ROBOT PAYMENT（決済ゲートウェイ）: ROBOTPAYMENT_* （.env.example 参照）
```

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

- **前提**: Plesk・**SSH 不可**・サブディレクトリ `https://dschatbot.ai/webroot/billing`（[deploy/AIdocs/deploy_rules.md](deploy/AIdocs/deploy_rules.md)）
- **パッケージ生成**: `scripts/build_deploy_webroot_billing.sh` → `deploy/webroot_billing/`（vendor は [deploy/BUILD_NOTES.md](deploy/BUILD_NOTES.md) 参照）
- **DBスキーマのみ**: `deploy/billing_schema_no_data.sql`（[deploy/README_SCHEMA.md](deploy/README_SCHEMA.md)）
- **`.env` テンプレート**: [AIdocs/本番環境.envテンプレート.txt](AIdocs/本番環境.envテンプレート.txt)
- **ROBOT PAYMENT CP チェックリスト**: [AIdocs/deploy_robot_payment_cp_production_checklist.md](AIdocs/deploy_robot_payment_cp_production_checklist.md)
- デプロイ方法: **FTP**（Plesk ファイルマネージャ可）

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

- [AIdocs/api_documents/](AIdocs/api_documents/) … 請求管理ロボ API 仕様・実行計画・デモ接続情報

