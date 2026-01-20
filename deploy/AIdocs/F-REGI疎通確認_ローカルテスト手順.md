# F-REGI疎通確認：ローカルテスト手順

作成日: 2026-01-16

---

## 概要

ローカル環境でF-REGI連携の疎通確認を行うためのサンプルデータ投入とテスト手順です。

---

## 前提条件

- Docker環境が起動していること
- `FREGI_SECRET_KEY` が `.env` に設定されていること
- F-REGI設定（`fregi_configs`）が保存されていること（`connect_password_enc` が埋まっている）

---

## 1. サンプルデータ投入

### 1.1 Seeder実行

```bash
docker compose exec app sh -lc "php artisan db:seed --class=LocalFregiDemoSeeder -v"
```

**実行結果の確認ポイント:**
- 各テーブルのIDとURLが出力されること
- 既存データがある場合は「更新完了」、新規の場合は「作成完了」と表示されること

### 1.2 作成されるサンプルデータ

Seeder実行後、以下のデータが作成されます：

#### contract_plan_masters
- **ID**: 1（既存の場合は既存IDを返す）
- **name**: `デモプランマスター`
- **description**: `ローカルF-REGI疎通確認用のプランマスター`
- **is_active**: `true`
- **display_order**: `1`

#### contract_plans（2件）

**1. 一回限り決済プラン**
- **ID**: 1（既存の場合は既存IDを返す）
- **item**: `DEMO_ONCE_10000`
- **name**: `デモプラン（一回限り）`
- **price**: `10000`
- **billing_type**: `one_time`
- **contract_plan_master_id**: `1`

**2. 月額課金プラン**
- **ID**: 2（既存の場合は既存IDを返す）
- **item**: `DEMO_MONTHLY_5000`
- **name**: `デモプラン（月額）`
- **price**: `5000`
- **billing_type**: `monthly`
- **contract_plan_master_id**: `1`

#### products
- **ID**: 1（既存の場合は既存IDを返す）
- **code**: `PROD_DEMO_001`
- **name**: `デモ商品`
- **unit_price**: `10000`

#### contract_form_urls
- **ID**: 1（既存の場合は既存IDを返す）
- **token**: `null`（申込フォームURLはtokenなし）
- **url**: `http://localhost:8080/billing/contract/create?plans=1,2`
- **plan_ids**: `[1, 2]`（JSON配列）
- **name**: `ローカルF-REGI疎通確認用申込フォーム`
- **expires_at**: 10年後（長期間有効）
- **is_active**: `true`

---

## 2. 疎通確認手順

### 2.1 申込フォームURLにアクセス

**URL**: `http://localhost:8080/billing/contract/create?plans=1,2`

または、`plans` パラメータを指定せずに：

**URL**: `http://localhost:8080/billing/contract/create`

ブラウザでアクセスし、申込フォームが表示されることを確認します。

### 2.2 申込フォーム入力

1. **企業情報入力**
   - 企業名
   - メールアドレス
   - 電話番号
   - 住所
   - その他必須項目

2. **プラン選択**
   - 一回限り決済プラン（10,000円）または月額課金プラン（5,000円/月）を選択

3. **利用開始日設定**
   - 希望する利用開始日を選択

4. **確認画面へ進む**

### 2.3 確認画面

入力内容を確認し、「申し込む」ボタンをクリックします。

### 2.4 決済処理開始

確認画面から決済処理が開始されます。

- **F-REGIへのオーソリ処理（authm.cgi）が実行される**
- **カード情報**（PAN1-4, CARDEXPIRY1-2, CARDNAME, SCODE）が必要
- **決済タイプ**に応じて `MONTHLY` パラメータが設定される

### 2.5 成功時の動作

- 決済が成功すると、契約・決済情報が保存されます
- 完了画面が表示されます

### 2.6 失敗時の動作

- エラーメッセージが表示されます
- ログにエラー情報が記録されます

---

## 3. 確認方法

### 3.1 アプリログの確認

```bash
# 最新120行を確認
docker compose exec app sh -lc "tail -n 120 /var/www/storage/logs/laravel.log"
```

**またはリアルタイムでログを確認:**
```bash
docker compose exec app sh -lc "tail -f /var/www/storage/logs/laravel.log"
```

**確認ポイント:**
- `F-REGIオーソリ処理送信前` - 送信URL、リクエストプレビュー
- `F-REGIオーソリ処理HTTPレスポンス受信` - HTTPステータス、レスポンスサイズ
- `F-REGIオーソリ処理レスポンス受信` - レスポンスの先頭200文字
- `F-REGIオーソリ処理成功` / `F-REGIオーソリ処理失敗` - 結果

**ログチャネル**: `contract_payment` にも記録されます

```bash
docker compose exec app bash -lc "tail -f /var/www/storage/logs/contract_payment.log"
```

### 3.2 DB確認SQL

#### 件数確認（Seeder実行後）

```bash
docker compose exec -T db sh -lc 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "
SELECT \"contract_plan_masters\" tbl, COUNT(*) cnt FROM contract_plan_masters
UNION ALL SELECT \"contract_plans\", COUNT(*) FROM contract_plans
UNION ALL SELECT \"products\", COUNT(*) FROM products
UNION ALL SELECT \"contract_form_urls\", COUNT(*) FROM contract_form_urls
;" billing'
```

**期待される結果:**
- `contract_plan_masters`: >= 1
- `contract_plans`: >= 2
- `products`: >= 1
- `contract_form_urls`: >= 1

#### payments テーブル

```sql
SELECT 
    id, 
    contract_id, 
    orderid, 
    amount, 
    status, 
    receiptno AS auth_code, 
    slipno AS seqno, 
    created_at 
FROM payments 
ORDER BY id DESC 
LIMIT 5;
```

**実行コマンド:**
```bash
docker compose exec -T db sh -lc 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "SELECT id, contract_id, orderid, amount, status, created_at FROM payments ORDER BY id DESC LIMIT 5;" billing'
```

**確認ポイント:**
- `status`: `paid`（成功時）、`failed`（失敗時）、`created`（処理中）
- `orderid`: 伝票番号（最大20文字）
- `auth_code`（`receiptno`）: 承認番号（成功時のみ）
- `seqno`（`slipno`）: 取引番号（成功時のみ）

#### payment_events テーブル

```sql
SELECT 
    id, 
    payment_id, 
    event_type, 
    created_at 
FROM payment_events 
ORDER BY id DESC 
LIMIT 20;
```

**実行コマンド:**
```bash
docker compose exec -T db sh -lc 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "SELECT id, payment_id, event_type, created_at FROM payment_events ORDER BY id DESC LIMIT 20;" billing'
```

**確認ポイント:**
- `event_type`: イベントタイプ
  - `fregi_authorize_request`: F-REGIオーソリ処理送信前
  - `fregi_authorize_response`: F-REGIオーソリ処理レスポンス受信後
  - `fregi_authorize_success`: オーソリ処理成功
  - `fregi_authorize_failed`: オーソリ処理失敗
- 決済1回あたり、最低3件（request, response, success/failed）が記録されること

#### contracts テーブル

```sql
SELECT 
    id, 
    contract_plan_id, 
    company_name, 
    email, 
    status, 
    payment_id, 
    customer_id,
    created_at 
FROM contracts 
ORDER BY id DESC 
LIMIT 5;
```

**確認ポイント:**
- `status`: `active`（成功時）、`pending_payment`（処理中）、`draft`（下書き）
- `payment_id`: 決済情報への参照
- `customer_id`: 月額課金の場合のみ設定

### 3.3 外部HTTP呼び出しの確認

F-REGIへのHTTPリクエストは以下のログで確認できます：

- **送信URL**: `https://ssl.f-regi.com/connecttest/authm.cgi`（テスト環境）
- **HTTPステータス**: ログに記録されます
- **レスポンスの先頭200文字**: ログに記録されます（安全な情報のみ）

**注意**: カード情報など機密情報はログに出力されません（マスク済み）

---

## 4. トラブルシューティング

### 4.1 FREGI_SECRET_KEY未設定エラー

**エラー**: `F-REGI暗号化キー（FREGI_SECRET_KEY）が未設定です。.env に設定してから再度保存してください。`

**対処**:
1. `.env` に `FREGI_SECRET_KEY` を設定
2. キー生成: `openssl rand -base64 32`
3. F-REGI設定を再度保存

### 4.2 F-REGI設定が見つからない

**エラー**: `F-REGI設定が見つかりません`

**対処**:
1. 管理画面からF-REGI設定を保存
2. `connect_password_enc` が埋まっていることを確認
3. DB確認: `SELECT id, shopid, LENGTH(connect_password_enc) AS enc_len FROM fregi_configs;`

### 4.3 HTTPリクエストが失敗

**エラー**: `HTTPリクエストが失敗しました: 500`

**対処**:
1. ログでエラー詳細を確認
2. F-REGI設定（SHOPID、接続パスワード）が正しいか確認
3. ネットワーク接続を確認

### 4.4 オーソリ処理が失敗

**エラー**: `決済処理に失敗しました: [エラーメッセージ]`

**対処**:
1. ログでエラー詳細を確認（`F-REGIオーソリ処理失敗`）
2. カード情報が正しいか確認（テストカード番号を使用）
3. F-REGIテスト環境の仕様を確認

---

## 5. 成功時の確認項目チェックリスト

- [ ] Seeder実行後、各テーブルの件数が期待値を満たす
  - [ ] `contract_plan_masters` >= 1
  - [ ] `contract_plans` >= 2
  - [ ] `products` >= 1
  - [ ] `contract_form_urls` >= 1
- [ ] 申込フォームが表示される
- [ ] プラン選択ができる
- [ ] 申込内容確認画面が表示される
- [ ] 決済処理が開始される（F-REGIへ送信される）
- [ ] ログに `F-REGIオーソリ処理送信前` が記録される
- [ ] ログに `F-REGIオーソリ処理HTTPレスポンス受信` が記録される
- [ ] ログに `F-REGIオーソリ処理レスポンス受信` が記録される
- [ ] ログに `F-REGIオーソリ処理成功` が記録される
- [ ] `payments` テーブルにレコードが作成される（`status='paid'`）
- [ ] `payment_events` テーブルに3件以上レコードが作成される
  - [ ] `fregi_authorize_request` が記録される
  - [ ] `fregi_authorize_response` が記録される
  - [ ] `fregi_authorize_success` が記録される
- [ ] `contracts` テーブルにレコードが作成される（`status='active'`）
- [ ] 完了画面が表示される

---

## 6. 変更ファイル一覧（deploy反映用）

```
app/database/seeders/LocalFregiDemoSeeder.php
app/app/Services/FregiApiService.php
AIdocs/F-REGI疎通確認_ローカルテスト手順.md
```

---

## 7. Seeder実行コマンド（再実行時）

```bash
# ローカル環境でのみ実行可能
docker compose exec app bash -lc "cd /var/www && php artisan db:seed --class=LocalFregiDemoSeeder"
```

**注意**: `APP_ENV=local` でない場合は実行されません（安全のため）

---

## 8. 作成されるサンプルデータの詳細

### contract_plan_masters
- **ID**: 1
- **name**: `デモプランマスター`
- **description**: `ローカルF-REGI疎通確認用のプランマスター`
- **is_active**: `true`
- **display_order**: `1`

### contract_plans（一回限り）
- **ID**: 1
- **item**: `DEMO_ONCE_10000`
- **name**: `デモプラン（一回限り）`
- **price**: `10000`
- **billing_type**: `one_time`
- **contract_plan_master_id**: `1`

### contract_plans（月額）
- **ID**: 2
- **item**: `DEMO_MONTHLY_5000`
- **name**: `デモプラン（月額）`
- **price**: `5000`
- **billing_type**: `monthly`
- **contract_plan_master_id**: `1`

### products
- **ID**: 1
- **code**: `PROD_DEMO_001`
- **name**: `デモ商品`
- **unit_price**: `10000`

### contract_form_urls
- **ID**: 1
- **url**: `http://localhost:8080/billing/contract/create?plans=1,2`
- **plan_ids**: `[1, 2]`
- **name**: `ローカルF-REGI疎通確認用申込フォーム`
- **expires_at**: 10年後
- **is_active**: `true`

---

## 9. 申込フォームURL

**ローカル環境**: `http://localhost:8080/billing/contract/create?plans=1,2`

このURLにブラウザでアクセスし、申込フォームを表示します。

---

## 10. 参考情報

- [F-REGI設定_最終成果物.md](./F-REGI設定_最終成果物.md) - F-REGI設定の詳細
- [deploy_rules.md](./deploy_rules.md) - デプロイルール
- [README.md](../README.md) - プロジェクト概要
