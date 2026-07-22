# 本番デプロイ手順（2026-07-15）— 請求書払い（銀行振込）／年額プラン対応

**前提**: Plesk / **SSH・`php artisan` 不可** / DBは phpMyAdmin で手動SQL / コードは FTP のみ
**デプロイ元**: `deploy/webroot_billing/`
**アップロード先**: `/var/www/vhosts/dschatbot.ai/httpdocs/webroot/billing/`
**公開URL**: `https://dschatbot.ai/webroot/billing`

---

## 1. 今回の変更概要

| 区分 | 内容 |
| --- | --- |
| 決済回収方法の追加 | `card`（クレジット）に加え **`bank_transfer`（請求書払い・銀行振込）** を追加。プラン単位で選択可 |
| 年額課金 | `contract_plans.billing_type` に **`yearly`** を追加（月額=`monthly` と同様に継続発行、請求書は申込1年後発行） |
| 申込フロー | 振込プランは**クレジット決済ページを経由せず**、確認画面→申込保存（API1/API3）で受付 |
| 管理画面 | プラン・製品の「決済タイプ」に クレジット/請求書払い × 月額/年額 を選択可能に |
| 通知メール | 振込申込時、管理者通知メールの件名を **「（請求書払い）」** 付きに切替 |
| フロント（Vite） | **変更なし**（`build/` 再ビルド不要） |

**重要**: 既存の**クレジット決済フローは不変**。新規列は default `card` のため既存データ・既存導線に影響しない。

---

## 2. 反映順序（必ず厳守）

新コードは `contracts.payment_collection_method` / `contract_plans.payment_collection_method` 列の存在を前提に動作する。
**列が無い状態でコードだけ上げると、既存のクレジット申込が SQL エラーで失敗する。**

> **手順 3（DB・phpMyAdmin）→ 手順 4（コード・FTP）→ 手順 5（キャッシュ削除）の順で実施すること。**

作業前に **DBバックアップ**（Plesk のエクスポート）を必ず取得する。

---

## 3. DB 変更（phpMyAdmin・migrate は実行しない）

対象DB: 本番 billing DB（例: `billing_prod`）。phpMyAdmin の「SQL」タブで実行する。

### 3.1 事前確認（現状把握）

```sql
-- billing_type に yearly が既にあるか
SHOW COLUMNS FROM contract_plans LIKE 'billing_type';
-- payment_collection_method 列が既にあるか（2テーブル）
SHOW COLUMNS FROM contract_plans LIKE 'payment_collection_method';
SHOW COLUMNS FROM contracts LIKE 'payment_collection_method';
```

- `billing_type` の Type に `'yearly'` が**含まれていなければ** 3.2 を実行。
- `payment_collection_method` が**存在しなければ**（0 件なら）3.3 を実行。
- 既に存在する場合は、その ALTER を**スキップ**する（重複実行するとエラーになる）。

### 3.2 billing_type に yearly を追加（未適用の場合のみ）

```sql
ALTER TABLE contract_plans
  MODIFY COLUMN billing_type ENUM('one_time','monthly','yearly') NOT NULL DEFAULT 'one_time'
  COMMENT '決済タイプ（one_time: 一回限り, monthly: 月額課金, yearly: 年額課金）';
```

### 3.3 payment_collection_method を追加（未適用の場合のみ）

```sql
ALTER TABLE contract_plans
  ADD COLUMN payment_collection_method VARCHAR(20) NOT NULL DEFAULT 'card'
  COMMENT '回収方法（card: クレジット, bank_transfer: 請求書払い・銀行振込）' AFTER billing_type;

ALTER TABLE contracts
  ADD COLUMN payment_collection_method VARCHAR(20) NOT NULL DEFAULT 'card'
  COMMENT '申込時点の回収方法（card / bank_transfer）' AFTER billing_robo_mode;
```

### 3.4 事後確認

```sql
SHOW COLUMNS FROM contract_plans LIKE 'payment_collection_method';  -- Default: card
SHOW COLUMNS FROM contracts LIKE 'payment_collection_method';       -- Default: card
SHOW COLUMNS FROM contract_plans LIKE 'billing_type';               -- enum に yearly
```

> `contracts` に `billing_robo_mode` 列が無い環境では `AFTER billing_robo_mode` を外して実行する（列位置は動作に影響しない）。

---

## 4. FTP アップロード一覧（`deploy/webroot_billing/` 配下 → 本番同一パス）

### 4.1 必須（本機能）

```
app/app/Http/Controllers/ContractController.php
app/app/Http/Controllers/Admin/ContractPlanController.php
app/app/Http/Controllers/Admin/ProductController.php
app/app/Mail/ContractNotificationMail.php
app/app/Models/Contract.php
app/app/Models/ContractPlan.php
app/app/Models/Product.php
app/app/Services/BillingRobo/BillingRoboBillingService.php
app/app/Services/BillingRobo/BillingRoboBulkRegisterService.php
app/app/Services/BillingRobo/BillingRoboDemandService.php
app/app/Services/BillingRobo/ContractToBillingLinesMapper.php
app/app/Services/RobotPayment/RobotPaymentService.php
app/app/Services/RobotPayment/PurchasePatternService.php
app/config/billing_robo.php
app/resources/views/contracts/create.blade.php
app/resources/views/contracts/confirm.blade.php
app/resources/views/contracts/payment.blade.php
app/resources/views/contracts/complete.blade.php
app/resources/views/admin/contract-plans/_form.blade.php
app/resources/views/admin/contract-plans/index.blade.php
app/resources/views/admin/contract-forms/index.blade.php
app/resources/views/admin/products/_form.blade.php
app/resources/views/admin/products/index.blade.php
```

例: ローカル `deploy/webroot_billing/app/app/Models/Contract.php`
　→ 本番 `.../webroot/billing/app/app/Models/Contract.php`

### 4.2 任意（デバッグ計装のクリーンアップ）

過去のデバッグ計装（`localhost:7244` への `fetch`）を本番から除去したい場合のみ上書き。動作影響は軽微（本番では失敗して無視されるだけ）だが、クリーンにするため推奨。

```
app/resources/views/admin/site-settings/index.blade.php
```

> `contracts/payment.blade.php` も同計装を除去済み（4.1 に含む）。`token_created_ms` / `er584_*` の機能コードは温存済みで、クレジット決済動作に影響なし。

### 4.3 アップロードしないもの

- `app/.env`（本番専用。上書き禁止）
- `app/storage/framework/views/`（コンパイル済みキャッシュ。**アップロードせず、本番側を削除して再生成**）
- `app/storage/logs/`・セッション
- `build/`（今回変更なし）
- `app/database/migrations/2026_07_07_*`, `2026_07_09_*`
  （本番は migrate を実行しないため**不要**。DBは手順3の手動SQLで対応。リポジトリ整合のため上げてもよいが実行はされない）

---

## 5. デプロイ後作業（SSH 不要）

### 5.1 Blade / OPcache 反映

FTP で次を削除（存在するもののみ）。`artisan view:clear` の代替。

```
app/storage/framework/views/*.php
```

### 5.2 設定キャッシュ（任意）

`config/billing_robo.php` を上げたので、設定が古いままなら FTP で削除して再生成。

```
app/bootstrap/cache/config.php
```

### 5.3 請求書払いパターンコード（任意 / 振込運用で必要な場合）

請求管理ロボ側で振込パターンコードの指定が必要な運用なら、本番 `.env` に追記し 5.2 を実施。

```
BILLING_ROBO_BANK_TRANSFER_PATTERN_CODE=（請求管理ロボで発番されたコード）
```

> 未設定でも API1 は成功する（`payment_method=0`＝銀行振込で登録。パターンコードは付与されないだけ）。運用要件に応じて設定。

---

## 6. 動作確認

| # | 操作 | 期待 |
| --- | --- | --- |
| 1 | `https://dschatbot.ai/webroot/billing/contract/create` | 画面が表示され、既存クレジットプランが従来どおり選択可能 |
| 2 | クレジットプランで申込→確認→決済ページ | **従来どおり決済ページへ遷移**しカード決済できる（回帰なし） |
| 3 | 管理画面でプラン作成：決済タイプ「月額課金（請求書払い）」等を選択・保存 | 一覧に「請求書払い」バッジ表示、保存後も値が保持される |
| 4 | 振込プランで申込→確認画面→申込 | 決済ページを経由せず申込完了。契約の `payment_collection_method = bank_transfer` |
| 5 | 振込申込時の管理者通知メール | 件名に **（請求書払い）** が付く |
| 6 | 請求管理ロボ側 | API1: 支払方法=銀行振込で請求先登録 / API3: 月次(月額)・年次(年額)で継続発行予約（年額は申込1年後発行） |

---

## 7. ロールバック

### 7.1 コード
- FTP でアップロード前のファイルへ戻す（`deploy/webroot_billing_backup_*` または事前退避）。
- `app/storage/framework/views/*.php` を削除。

### 7.2 DB
- 原則、追加列は default `card` で既存挙動に影響しないため**戻す必要は薄い**。
- どうしても戻す場合（振込プラン未作成が前提）:

```sql
ALTER TABLE contracts DROP COLUMN payment_collection_method;
ALTER TABLE contract_plans DROP COLUMN payment_collection_method;
-- yearly を使うプランが無いことを確認してから:
ALTER TABLE contract_plans
  MODIFY COLUMN billing_type ENUM('one_time','monthly') NOT NULL DEFAULT 'one_time'
  COMMENT '決済タイプ（one_time: 一回限り, monthly: 月額課金）';
```

> コードを戻さずに列だけ削除すると、新コードが列参照で失敗する。**ロールバックは「コード→DB」の順**で戻すこと。

---

## 8. 補足

- `deploy/billing_schema_no_data.sql`（新規構築用スキーマ）は本対応の2列を反映済み。初回フル構築時はこれをインポートすれば手順3は不要。
- 本 runbook の手動SQLは、既存本番DBへの**差分適用専用**。
