# Cursor指示書（AIdocs）: Billing（決済連携）アプリ 開発開始（Dockerローカル）

最終更新: 2026-01-07  
本番URL: `https://dschatbot.ai/billing/`  
技術: **Laravel 10 / PHP 8.1**  
本番サーバ: Apache + FastCGI（公開ディレクトリは `httpdocs/webroot`）  
デプロイ: **SSHなし・Composerなし** → **ローカルで構築してFTPアップロード**（publicのみ公開、本体は公開領域外）

---

## 0. 目的（What）
ローカル（Docker）で開発を開始し、以下を満たす Billing アプリを実装する。

- 決済連携（**ROBOT PAYMENT** 等）で決済を開始し、**通知**で決済結果を確定
- 決済連携の接続設定を**DBまたは設定で保持**し、管理画面から変更可能
- 決済（payments）を**状態遷移**で管理し、通知は**冪等**に処理
- 本番が `/billing/` 配下のため、ローカルでも **`/billing` を意識**してURL生成・アセット参照が壊れないようにする

---

## 1. Laravelのセットアップ（Dockerローカル）

### 1.1 構成（推奨）
- `web`: Nginx（`/billing` ベースパス対応）
- `app`: PHP-FPM 8.1 + Composer（Laravel実行）
- `db`: MySQL 8（まずはMySQLで統一。必要ならPostgreSQLへ変更可）
- `redis`: 任意（キュー/キャッシュを使うなら）

### 1.2 ディレクトリ構成（リポジトリ例）
```
billing/
  app/                 # Laravelプロジェクト（artisanがある階層）
  docker/
    nginx/
      default.conf
    php/
      Dockerfile
      php.ini
  docker-compose.yml
  .env.example         # ローカル用
```

> ※ Laravel本体は `billing/app/` に置く前提で記載。すでに別構成なら適宜読み替え。

---

## 2. Dockerファイル（コピペ）

### 2.1 `docker-compose.yml`（例）
```yaml
services:
  web:
    image: nginx:1.25
    ports:
      - "8080:80"
    volumes:
      - ./app:/var/www:delegated
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
    depends_on:
      - app

  app:
    build:
      context: ./docker/php
    volumes:
      - ./app:/var/www:delegated
    environment:
      PHP_MEMORY_LIMIT: 512M
    depends_on:
      - db
      - redis

  db:
    image: mysql:8.0
    command: --default-authentication-plugin=mysql_native_password
    environment:
      MYSQL_DATABASE: billing
      MYSQL_USER: billing
      MYSQL_PASSWORD: billing_pass
      MYSQL_ROOT_PASSWORD: root_pass
      TZ: Asia/Tokyo
    ports:
      - "33060:3306"
    volumes:
      - dbdata:/var/lib/mysql

  redis:
    image: redis:7
    ports:
      - "63790:6379"

volumes:
  dbdata:
```

### 2.2 `docker/php/Dockerfile`（PHP 8.1 + Composer）
```dockerfile
FROM php:8.1-fpm

# 依存
RUN apt-get update && apt-get install -y   git unzip libzip-dev libonig-dev libicu-dev   && docker-php-ext-install pdo_mysql mbstring intl zip   && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 作業ディレクトリ
WORKDIR /var/www

# php.ini（任意）
COPY php.ini /usr/local/etc/php/conf.d/zz-local.ini
```

### 2.3 `docker/php/php.ini`（任意）
```ini
memory_limit=512M
upload_max_filesize=128M
post_max_size=128M
date.timezone=Asia/Tokyo
display_errors=On
log_errors=On
```

### 2.4 `docker/nginx/default.conf`（ローカルでも /billing を再現）
```nginx
server {
  listen 80;
  server_name localhost;

  # Laravelプロジェクトの public を root にする
  root /var/www/public;
  index index.php index.html;

  # /billing ベースパスで動かす（本番再現）
  location /billing {
    try_files $uri $uri/ /billing/index.php?$query_string;
  }

  # PHP
  location ~ \.php$ {
    include fastcgi_params;
    fastcgi_pass app:9000;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_param PATH_INFO $fastcgi_path_info;
  }

  # 静的ファイル（任意調整）
  location ~* \.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2)$ {
    expires 7d;
    add_header Cache-Control "public";
    try_files $uri =404;
  }
}
```

---

## 3. 初期化手順（Dockerローカル）

### 3.1 起動
```bash
docker compose up -d --build
```

### 3.2 Laravelの作成（appフォルダが未作成の場合）
`billing/app/` が空なら、コンテナ内Composerで作成する：
```bash
docker compose exec app bash -lc "composer create-project laravel/laravel /var/www . '10.*'"
```

### 3.3 .env 設定（ローカル）
```bash
cp app/.env.example app/.env
```

`app/.env`（重要項目）
- `APP_URL=http://localhost:8080/billing`
- `APP_ENV=local`
- `APP_DEBUG=true`
- `DB_CONNECTION=mysql`
- `DB_HOST=db`
- `DB_PORT=3306`
- `DB_DATABASE=billing`
- `DB_USERNAME=billing`
- `DB_PASSWORD=billing_pass`

Cookie衝突回避（本番同一ドメイン想定）
- `SESSION_COOKIE=billing_session`
- `SESSION_PATH=/billing`

### 3.4 APP_KEY 生成
```bash
docker compose exec app bash -lc "php artisan key:generate"
```

### 3.5 マイグレーション（初回）
```bash
docker compose exec app bash -lc "php artisan migrate"
```

### 3.6 動作確認
- `http://localhost:8080/billing` にアクセスして Laravel welcome が出る

---

## 4. アプリケーション仕様（Billing / 決済連携）

### 4.1 採用する決済方式（第一段階）
- **決済連携（ROBOT PAYMENT 等）のリダイレクト方式**を採用
- 決済確定は **受付CGI（通知）** によって行う
- 戻りURL（成功/キャンセル等）は画面表示のために利用し、**確定処理はしない**（DB状態参照）

### 4.2 URL設計（本番）
ベース: `https://dschatbot.ai/billing/`

- 管理画面（例）: `/billing/admin/`
- API（例）: `/billing/api/...`
- 決済通知受領: 実装する決済方式のエンドポイント（例: `/billing/api/robot-payment/notify`）
- 戻りURL:
  - `/billing/return/success`
  - `/billing/return/cancel`
  - `/billing/return/failure`

> ローカルも同形：`http://localhost:8080/billing/...`

### 4.3 主要フロー
1. 契約/申込（contract）に対して請求（payment）を生成
2. 決済開始APIで決済連携先へPOSTするためのパラメータを生成（署名等含む）
3. ブラウザが決済連携先の決済画面へ遷移（自動submit）
4. 決済連携先が **通知エンドポイントへPOST**（成功/失敗/キャンセル等）
5. 通知を検証（チェックサム）し、paymentを **paid/failed/canceled** へ遷移
6. 戻りURL画面はDBの状態に応じて表示（通知遅延なら「決済確認中」）

### 4.4 決済ステータス（推奨）
`payments.status`
- `created`（請求生成済み）
- `redirect_issued`（決済開始準備完了）
- `waiting_notify`（通知待ち）
- `paid`（通知で成功確定）
- `failed`（通知で失敗確定）
- `canceled`（通知でキャンセル確定）
- `expired`（通知が来ないまま期限切れ：バッチで遷移）

重要ルール
- **確定は通知のみ**（戻りURLでは確定しない）
- 通知は **冪等**（重複通知でも2回更新しない）

### 4.5 セキュリティ要件（必須）
- 受付CGI（notify）は
  - 送信元IP制限（可能ならWAF/サーバ側。ローカルはスキップ可）
  - チェックサム検証
  - レート制限（将来的に）
- 秘密情報（接続パスワード等）は **平文保存しない**（DB暗号化）
- ログにカード情報/秘密情報を出さない
- 管理画面の権限を最小限に

---

## 5. データベース仕様（最小セット）
既にDB定義書（Excel）に追加済みの前提だが、ローカル開発に必要なテーブル仕様をここに集約する。

### 5.1 決済連携の接続設定
目的: テナント/会社単位で、決済連携（ROBOT PAYMENT 等）の接続設定を保持し、管理画面から変更できるようにする。実装する決済方式に応じてテーブル名・カラムを定義する。

推奨カラム（例）
- `id` (PK)
- `company_id`（または `tenant_id`）
- `environment`（`test` / `prod`）
- `shop_id`
- `connect_password_enc`（暗号化済み。復号してチェックサム生成に使用）
- `notify_url`
- `return_url_success`
- `return_url_cancel`
- `is_active`（同一 company/environment でアクティブは1つ）
- `created_at`, `updated_at`, `updated_by`

推奨制約/インデックス
- `unique(company_id, environment, is_active)` はDBにより工夫が必要（partial unique等）
  - DB制約が難しい場合は **アプリ側でトランザクション保証**し、監視ログを残す

### 5.2 接続設定の変更履歴（任意）
目的: 監査/ロールバックのために履歴保持。

- `id` (PK)
- `config_id`（FK -> 決済連携設定テーブル）
- `version_no`
- `snapshot_json`（変更後のスナップショット推奨）
- `changed_at`, `changed_by`
- `change_reason`

### 5.3 `payments`（取引・請求）
目的: 1回の課金取引を表す中核テーブル。

- `id` (PK)
- `company_id` / `tenant_id`
- `contract_id`（既存の契約/申込と紐付け）
- `order_no`（自社採番: 外部公開OKな識別子）
- 決済連携先の発行番号・伝票番号等（決済方式に応じてカラム名を定義）
- `amount`（税込）
- `currency`（JPY固定でも可）
- `payment_method`（例: `card`）
- `status`（上記ステータス）
- `requested_at`, `notified_at`, `completed_at`
- `failure_reason`（任意）
- `raw_notify_payload`（通知payload。個人情報が含まれうるのでアクセス制御が必要）
- `created_at`, `updated_at`

推奨制約/インデックス
- `unique(company_id, order_no)`
- 決済連携先の伝票等のユニーク制約（決済方式に応じて定義）

### 5.4 `payment_events`（監査ログ）
目的: 重複通知/障害調査のためのイベント履歴。

- `id` (PK)
- `payment_id`（FK -> `payments.id`）
- `event_type`（`request`, `redirect`, `notify`, `return` 等）
- `payload`（マスク/制限必須）
- `created_at`

---

## 6. 実装スコープ（MVP）
### 6.1 必須
- 決済連携設定の管理（CRUD または編集のみ）
  - パスワードはマスク表示、変更時のみ入力
  - 保存時に暗号化してDBへ
  - 変更履歴を必ず保存（versions）
- 決済開始
  - DBから有効な決済連携設定を取得
  - チェックサム生成
  - 決済連携先へPOSTするためのフォーム生成（HTML）
- 通知受領（受付CGI）
  - 署名/チェックサム検証
  - 冪等更新（paid/failed/canceled）
- 戻りURL画面
  - DB状態を参照して表示
  - 通知遅延なら「確認中」

### 6.2 任意（後回しOK）
- expired バッチ
- レート制限/WAF設定
- 支払い照会/再送対応

---

## 7. 本番デプロイ（FTP）前提の重要メモ
本番は CakePHP4 が `/` にいるため、Laravelは `/billing` に分離して設置する。

想定パス（本番）
- 公開（Laravel public）: `/var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/`
- 本体（非公開）: `/var/www/vhosts/dschatbot.ai/httpdocs/laravel_billing/`

FTPアップロード方針
- `httpdocs/laravel_billing/` に Laravel本体（vendor含む）をアップロード
- `httpdocs/webroot/billing/` に `public/` のみアップロード
- `webroot/billing/index.php` から本体の `vendor/autoload.php` と `bootstrap/app.php` を参照するように書き換える（絶対パス推奨）

---

## 8. Cursor実行タスク（ToDo）
1. Dockerローカル環境（compose, nginx, php-fpm）を作成して起動
2. Laravel 10 を作成し、`/billing` ベースで表示できるようにする
3. DBマイグレーション作成
   - 決済連携設定テーブル（実装方式に応じて）
   - `payments`
   - `payment_events`
4. 暗号化ユーティリティ（AES-GCM推奨）を実装し、接続パスワードを暗号化保存
5. 管理画面（最小）
   - 決済連携設定の登録/更新（機密項目はマスク）
6. 決済開始（フォーム生成）
7. notify受領（冪等）
8. return画面（確認中含む）

---

## 9. 受け入れ条件（Acceptance Criteria）
- ローカル: `http://localhost:8080/billing` でアプリが動く
- 決済連携設定がDBに保存でき、機密項目は暗号化する
- 決済開始でフォーム生成できる（ダミーでもOK）
- notifyで支払い状態が確定し、冪等である
- 戻りURL画面はDB状態に従って表示できる
- 直書きリンクがなく、`/billing` 配下でもURL生成/アセット参照が崩れない

---

## 10. 注意（セキュリティ）
- phpinfo を公開URLに置かない（認証情報・環境情報が漏れる）
- Basic認証等の資格情報が漏れた場合は **必ずローテーション**する
